<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TemplateDraftController extends Controller
{
    /**
     * Extract authenticated user ID from Bearer token.
     */
    private function getAuthenticatedUserId(Request $request)
    {
        Log::info('TemplateDraftController::getAuthenticatedUserId - start');
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $m)) {
            Log::warning('Authorization header missing or malformed');
            abort(response()->json(['status'=>'error','message'=>'Token not provided'],401));
        }

        $tokenHash = hash('sha256', $m[1]);
        $record = DB::table('personal_access_tokens')
            ->where('token', $tokenHash)
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();

        if (!$record) {
            Log::warning('Invalid personal_access_token', ['token_hash'=>$tokenHash]);
            abort(response()->json(['status'=>'error','message'=>'Invalid token'],401));
        }

        return $record->tokenable_id;
    }

    /**
     * POST /api/drafts
     */
    /**
 * POST /api/drafts
 */
public function store(Request $request)
{
    $userId = $this->getAuthenticatedUserId($request);

    $data = $request->validate([
        'name'          => 'nullable|string|max:255',
        'subject'       => 'nullable|string|max:255',
        'body_html'     => 'nullable|string',
        'body_design'   => 'nullable|json',
        'editable_html' => 'nullable|string',   // <-- new
        'changelog'     => 'nullable|string',
    ]);

    return DB::transaction(function() use($data, $userId) {
        // retire any existing "current" draft
        DB::table('template_drafts')
            ->where('user_id', $userId)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // compute next version for this user
        $maxVersion  = DB::table('template_drafts')
            ->where('user_id', $userId)
            ->max('version') ?: 0;
        $nextVersion = $maxVersion + 1;

        // auto‑name/subject if none provided
        $name    = $data['name']    ?? "Draft #{$nextVersion}";
        $subject = $data['subject'] ?? "Draft #{$nextVersion}";

        // insert new draft
        DB::table('template_drafts')->insert([
            'template_id'   => null,
            'user_id'       => $userId,
            'draft_uuid'    => Str::uuid()->toString(),
            'name'          => $name,
            'subject'       => $subject,
            'body_html'     => $data['body_html']     ?? null,
            'body_design'   => $data['body_design']   ?? null,
            'editable_html' => $data['editable_html'] ?? null,   // <-- persisted here
            'version'       => $nextVersion,
            'status'        => 'draft',
            'is_current'    => true,
            'changelog'     => $data['changelog']     ?? null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // fetch and return it
        $draft = DB::table('template_drafts')
            ->where('user_id', $userId)
            ->where('version', $nextVersion)
            ->first();

        return response()->json(['draft' => $draft], 201);
    });
}

    /**
     * GET /api/drafts
     */
    public function index(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);

        $drafts = DB::table('template_drafts')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['drafts' => $drafts], 200);
    }

    /**
     * GET /api/drafts/{uuid}
     */
    public function preview(Request $request, $uuid)
    {
        $userId = $this->getAuthenticatedUserId($request);

        $draft = DB::table('template_drafts')
            ->where('draft_uuid', $uuid)
            ->where('user_id', $userId)
            ->first();

        if (! $draft) {
            return response()->json(['status'=>'error','message'=>'Draft not found'],404);
        }

        return response()->json(['draft' => $draft], 200);
    }

    /**
     * PATCH /api/drafts/{uuid}
     */
    public function update(Request $request, $uuid)
    {
        $userId = $this->getAuthenticatedUserId($request);

        $data = $request->validate([
            'name'        => 'nullable|string|max:255',
            'subject'     => 'nullable|string|max:255',
            'body_html'   => 'nullable|string',
            'body_design' => 'nullable|json',
            'changelog'   => 'nullable|string',
        ]);

        $exists = DB::table('template_drafts')
            ->where('draft_uuid', $uuid)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            return response()->json(['status'=>'error','message'=>'Draft not found'],404);
        }

        DB::table('template_drafts')
            ->where('draft_uuid', $uuid)
            ->update([
                'name'        => $data['name']        ?? DB::raw('name'),
                'subject'     => $data['subject']     ?? DB::raw('subject'),
                'body_html'   => $data['body_html']   ?? DB::raw('body_html'),
                'body_design' => $data['body_design'] ?? DB::raw('body_design'),
                'changelog'   => $data['changelog']   ?? DB::raw('changelog'),
                'updated_at'  => now(),
            ]);

        $updated = DB::table('template_drafts')
            ->where('draft_uuid', $uuid)
            ->first();

        return response()->json(['draft' => $updated], 200);
    }

    /**
     * DELETE /api/drafts/{uuid}
     */
    public function destroy(Request $request, $uuid)
    {
        $userId = $this->getAuthenticatedUserId($request);

        $deleted = DB::table('template_drafts')
            ->where('draft_uuid', $uuid)
            ->where('user_id', $userId)
            ->delete();

        if (! $deleted) {
            return response()->json(['status'=>'error','message'=>'Draft not found'],404);
        }

        return response()->json(['status'=>'success','message'=>'Draft deleted'],200);
    }

    /**
     * POST /api/drafts/{uuid}/copy
     */
    /**
 * POST /api/drafts/{uuid}/copy
 */
public function copy(Request $request, $uuid)
{
    $userId = $this->getAuthenticatedUserId($request);

    $orig = DB::table('template_drafts')
        ->where('draft_uuid', $uuid)
        ->where('user_id', $userId)
        ->first();

    if (! $orig) {
        return response()->json(['status'=>'error','message'=>'Draft not found'],404);
    }

    return DB::transaction(function() use($orig, $userId) {
        // retire current
        DB::table('template_drafts')
            ->where('user_id', $userId)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // next version
        $maxVersion  = DB::table('template_drafts')
            ->where('user_id', $userId)
            ->max('version') ?: 0;
        $nextVersion = $maxVersion + 1;

        // new copy
        DB::table('template_drafts')->insert([
            'template_id'   => $orig->template_id,
            'user_id'       => $userId,
            'draft_uuid'    => Str::uuid()->toString(),
            'name'          => $orig->name . " (copy)",
            'subject'       => $orig->subject,
            'body_html'     => $orig->body_html,
            'body_design'   => $orig->body_design,
            'editable_html' => $orig->editable_html,    // <-- carry over editable_html
            'version'       => $nextVersion,
            'status'        => 'draft',
            'is_current'    => true,
            'changelog'     => "Copied from {$orig->draft_uuid}",
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $copy = DB::table('template_drafts')
            ->where('user_id', $userId)
            ->where('version', $nextVersion)
            ->first();

        return response()->json(['draft' => $copy], 201);
    });
}

    /**
 * POST /api/drafts/{uuid}/approve
 *
 * Publishes a draft by either updating its linked template
 * or creating a new one, then deletes the draft.
 */
/**
 * POST /api/drafts/{uuid}/approve
 *
 * Publishes a draft by either updating its linked template
 * or creating a new one, then deletes **that** draft.
 */
public function approve(Request $request, $uuid)
{
    $userId = $this->getAuthenticatedUserId($request);

    // Fetch the draft
    $draft = DB::table('template_drafts')
        ->where('draft_uuid', $uuid)
        ->where('user_id', $userId)
        ->first();

    if (! $draft) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Draft not found',
        ], 404);
    }

    // Load user's plan to enforce template limit (only matters if creating a new template)
    $plan = null;
    if ($draft->user_id) {
        $user = DB::table('users')->where('id', $draft->user_id)->first();
        if (! $user || empty($user->subscription_plan_id)) {
            return response()->json(['status' => 'error', 'message' => 'User or plan missing.'], 404);
        }
        $plan = DB::table('subscription_plans')->where('id', $user->subscription_plan_id)->first();
        if (! $plan) {
            return response()->json(['status' => 'error', 'message' => 'Assigned subscription plan not found.'], 404);
        }
    }

    // Enforce limit if creating new template (i.e., no linked template_id)
    if (!$draft->template_id && $plan && $plan->template_limit !== null) {
        $templateLimit = (int)$plan->template_limit;
        $activeCount = DB::table('templates')
            ->where('user_id', $draft->user_id)
            ->where('is_active', 1)
            ->count();
        if ($activeCount >= $templateLimit) {
            Log::warning('Cannot approve draft: active template limit reached', [
                'user_id' => $draft->user_id,
                'limit' => $templateLimit,
                'active_count' => $activeCount,
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => "Cannot publish draft. Active template limit reached (max {$templateLimit}).",
            ], 422);
        }
    }

    return DB::transaction(function () use ($draft) {
        $resultTemplate = null;

        if ($draft->template_id) {
            // Update existing template
            DB::table('templates')
                ->where('id', $draft->template_id)
                ->update([
                    'name'          => $draft->name,
                    'subject'       => $draft->subject,
                    'body_html'     => $draft->body_html,
                    'body_design'   => $draft->body_design,
                    'editable_html' => $draft->editable_html,
                    'updated_at'    => now(),
                ]);

            $resultTemplate = DB::table('templates')
                ->where('id', $draft->template_id)
                ->first();
        } else {
            // Create new template
            $newUuid = Str::uuid()->toString();
            $newId = DB::table('templates')->insertGetId([
                'template_uuid' => $newUuid,
                'user_id'       => $draft->user_id,
                'name'          => $draft->name,
                'subject'       => $draft->subject,
                'body_html'     => $draft->body_html,
                'body_design'   => $draft->body_design,
                'editable_html' => $draft->editable_html,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $resultTemplate = DB::table('templates')
                ->where('id', $newId)
                ->first();
        }

        // Delete only this draft
        DB::table('template_drafts')
            ->where('draft_uuid', $draft->draft_uuid)
            ->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Draft published and removed.',
            'data'    => $resultTemplate,
        ], 200);
    });
}



}
