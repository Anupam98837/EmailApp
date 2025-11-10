<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TemplateController extends Controller
{
    /**
     * Extract authenticated user ID from Bearer token.
     */
    private function getAuthenticatedUserId(Request $request)
    {
        Log::info('TemplateController::getAuthenticatedUserId - start');
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $m)) {
            Log::warning('Authorization header missing or malformed');
            abort(response()->json(['status' => 'error', 'message' => 'Token not provided'], 401));
        }

        $tokenHash = hash('sha256', $m[1]);
        Log::info('Looking up personal_access_tokens', ['token_hash' => $tokenHash]);

        $record = DB::table('personal_access_tokens')
            ->where('token', $tokenHash)
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();

        if (!$record) {
            Log::warning('Invalid personal_access_token', ['token_hash' => $tokenHash]);
            abort(response()->json(['status' => 'error', 'message' => 'Invalid token'], 401));
        }

        Log::info('Authenticated user', ['user_id' => $record->tokenable_id]);
        return $record->tokenable_id;
    }

    /**
     * Helper: get active non-expired subscription for a user and plan.
     */
    protected function getValidSubscription(int $userId, int $planId)
    {
        return DB::table('user_subscriptions')
            ->where('user_id', $userId)
            ->where('plan_id', $planId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'desc')
            ->first();
    }

    /**
     * Helper: retrieve user's assigned plan and validate active/non-expired subscription.
     * Returns ['plan'=>..., 'subscription'=>...] or null.
     */
    protected function getUserActivePlan(int $userId)
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (! $user || empty($user->subscription_plan_id)) {
            return null;
        }

        $plan = DB::table('subscription_plans')->where('id', $user->subscription_plan_id)->first();
        if (! $plan) {
            return null;
        }

        $subscription = $this->getValidSubscription($userId, $plan->id);
        if (! $subscription) {
            return null;
        }

        return [
            'plan' => $plan,
            'subscription' => $subscription,
        ];
    }

    /**
     * GET /api/templates
     */
    public function index(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('TemplateController::index - fetching list', ['user_id' => $userId]);

        $templates = DB::table('templates')
            ->where('user_id', $userId)
            ->orderByDesc('is_active')    // active templates first
            ->orderByDesc('updated_at')
            ->get();

        Log::info('TemplateController::index - retrieved count', ['count' => $templates->count()]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Templates retrieved.',
            'data'    => $templates,
        ], 200);
    }

    /**
     * POST /api/templates
     */
    public function store(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('TemplateController::store - start', [
            'user_id' => $userId,
            'payload' => $request->all(),
        ]);

        $v = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'subject'       => 'required|string|max:255',
            'body_html'     => 'required|string',
            'body_design'   => 'nullable|json',
            'editable_html' => 'nullable|string',
        ]);

        if ($v->fails()) {
            Log::warning('TemplateController::store - validation failed', [
                'errors' => $v->errors()->all(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        // enforce active non-expired plan + subscription
        $planData = $this->getUserActivePlan($userId);
        if (! $planData) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active/non-expired subscription plan found for user.'
            ], 403);
        }
        $plan = $planData['plan'];

        $templateLimitRaw = $plan->template_limit;
        if ($templateLimitRaw !== null) {
            $templateLimit = (int)$templateLimitRaw;
            $activeCount = DB::table('templates')
                ->where('user_id', $userId)
                ->where('is_active', 1)
                ->count();
            if ($activeCount >= $templateLimit) {
                Log::warning('Template limit reached', [
                    'user_id' => $userId,
                    'limit' => $templateLimit,
                    'active_count' => $activeCount,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => "Template limit reached (max {$templateLimit} active templates).",
                ], 422);
            }
        }

        try {
            $uuid = (string) Str::uuid();
            DB::table('templates')->insert([
                'template_uuid' => $uuid,
                'user_id'       => $userId,
                'name'          => $request->name,
                'subject'       => $request->subject,
                'body_html'     => $request->body_html,
                'body_design'   => $request->input('body_design'),
                'editable_html' => $request->input('editable_html'),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            Log::info('TemplateController::store - inserted template', [
                'template_uuid' => $uuid,
            ]);

            $tpl = DB::table('templates')
                     ->where('template_uuid', $uuid)
                     ->first();

            return response()->json([
                'status'  => 'success',
                'message' => 'Template created.',
                'data'    => $tpl,
            ], 201);

        } catch (\Exception $e) {
            Log::error('TemplateController::store - error', [
                'exception' => $e->getMessage(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not create template.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/templates/{uuid}
     */
    public function show(Request $request, $uuid)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('TemplateController::show - start', ['user_id' => $userId, 'uuid' => $uuid]);

        $tpl = DB::table('templates')
            ->where('template_uuid', $uuid)
            ->where('user_id', $userId)
            ->first();

        if (!$tpl) {
            Log::warning('TemplateController::show - not found', ['uuid' => $uuid]);
            return response()->json(['status' => 'error', 'message' => 'Template not found.'], 404);
        }

        Log::info('TemplateController::show - found', ['uuid' => $uuid]);
        return response()->json(['status' => 'success', 'data' => $tpl], 200);
    }

    /**
     * PUT /api/templates/{uuid}
     */
    public function update(Request $request, $uuid)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('TemplateController::update - start', ['user_id' => $userId, 'uuid' => $uuid, 'payload' => $request->all()]);

        $v = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'subject'       => 'required|string|max:255',
            'body_html'     => 'required|string',
            'body_design'   => 'nullable|json',
            'editable_html' => 'nullable|string',
        ]);
        if ($v->fails()) {
            Log::warning('TemplateController::update - validation failed', ['errors' => $v->errors()->all()]);
            return response()->json(['status' => 'error', 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        $exists = DB::table('templates')
            ->where('template_uuid', $uuid)
            ->where('user_id', $userId)
            ->exists();
        if (!$exists) {
            Log::warning('TemplateController::update - not found', ['uuid' => $uuid]);
            return response()->json(['status' => 'error', 'message' => 'Template not found.'], 404);
        }

        try {
            DB::table('templates')->where('template_uuid', $uuid)->update([
                'name'          => $request->name,
                'subject'       => $request->subject,
                'body_html'     => $request->body_html,
                'body_design'   => $request->input('body_design'),
                'editable_html' => $request->input('editable_html'),
                'updated_at'    => now(),
            ]);
            Log::info('TemplateController::update - updated', ['uuid' => $uuid]);

            $tpl = DB::table('templates')->where('template_uuid', $uuid)->first();
            return response()->json(['status' => 'success', 'message' => 'Template updated.', 'data' => $tpl], 200);

        } catch (\Exception $e) {
            Log::error('TemplateController::update - error', ['exception' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Could not update template.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/templates/{uuid}
     */
    public function destroy(Request $request, $uuid)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('TemplateController::destroy - start', ['user_id' => $userId, 'uuid' => $uuid]);

        $tpl = DB::table('templates')
            ->where('template_uuid', $uuid)
            ->where('user_id', $userId)
            ->first();
        if (!$tpl) {
            Log::warning('TemplateController::destroy - not found', ['uuid' => $uuid]);
            return response()->json(['status' => 'error', 'message' => 'Template not found.'], 404);
        }

        try {
            DB::table('templates')->where('template_uuid', $uuid)->delete();
            Log::info('TemplateController::destroy - deleted template', ['uuid' => $uuid]);

            return response()->json(['status' => 'success', 'message' => 'Template deleted.'], 200);

        } catch (\Exception $e) {
            Log::error('TemplateController::destroy - error', ['exception' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Could not delete template.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/templates/{uuid}/toggle
     */
    public function toggle(Request $request, $uuid)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('TemplateController::toggle - start', ['user_id' => $userId, 'uuid' => $uuid]);

        $tpl = DB::table('templates')
            ->where('template_uuid', $uuid)
            ->where('user_id', $userId)
            ->first();
        if (!$tpl) {
            Log::warning('TemplateController::toggle - not found', ['uuid' => $uuid]);
            return response()->json(['status' => 'error', 'message' => 'Template not found.'], 404);
        }

        $isEnabling = ! (bool)$tpl->is_active;

        if ($isEnabling) {
            // enforce active non-expired plan/subscription and template limit
            $planData = $this->getUserActivePlan($userId);
            if (! $planData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active/non-expired subscription plan found for user.'
                ], 403);
            }
            $plan = $planData['plan'];

            $templateLimitRaw = $plan->template_limit;
            if ($templateLimitRaw !== null) {
                $templateLimit = (int)$templateLimitRaw;
                $activeCount = DB::table('templates')
                    ->where('user_id', $userId)
                    ->where('is_active', 1)
                    ->count();
                if ($activeCount >= $templateLimit) {
                    Log::warning('Cannot enable template; limit reached', [
                        'user_id' => $userId,
                        'limit' => $templateLimit,
                        'active_count' => $activeCount,
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => "Cannot enable template. Active template limit reached (max {$templateLimit}).",
                    ], 422);
                }
            }
        }

        $new = $isEnabling ? 1 : 0;
        DB::table('templates')->where('template_uuid', $uuid)
            ->update(['is_active' => $new, 'updated_at' => now()]);
        Log::info('TemplateController::toggle - status changed', ['uuid' => $uuid, 'is_active' => $new]);

        return response()->json([
            'status'  => 'success',
            'message' => $new ? 'Template enabled.' : 'Template disabled.',
        ], 200);
    }

    /**
     * GET /api/templates/{uuid}/preview
     */
    public function preview(Request $request, $uuid)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('TemplateController::preview - start', ['user_id' => $userId, 'uuid' => $uuid]);

        $tpl = DB::table('templates')
            ->where('template_uuid', $uuid)
            ->where('user_id', $userId)
            ->first();
        if (!$tpl) {
            Log::warning('TemplateController::preview - not found', ['uuid' => $uuid]);
            return response()->json(['status' => 'error', 'message' => 'Template not found.'], 404);
        }

        Log::info('TemplateController::preview - returning data', ['uuid' => $uuid]);
        return response()->json([
            'status' => 'success',
            'data'   => [
                'name'      => $tpl->name,
                'subject'   => $tpl->subject,
                'body_html' => $tpl->body_html,
            ],
        ], 200);
    }
}
