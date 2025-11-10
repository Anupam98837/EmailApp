<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCampaignDelivery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    /** Extract & validate Bearer token → user_id. */
    private function getAuthenticatedUserId(Request $request)
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $m)) {
            abort(response()->json(['status' => 'error', 'message' => 'Token not provided'], 401));
        }
        $tokenHash = hash('sha256', $m[1]);
        $record = DB::table('personal_access_tokens')
            ->where('token', $tokenHash)
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();
        if (!$record) {
            abort(response()->json(['status' => 'error', 'message' => 'Invalid token'], 401));
        }
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
     * Helper: get user's assigned plan and ensure it has an active, non-expired subscription.
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

    /** GET /api/campaign */
    public function index(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('Listing campaigns', ['user_id' => $userId]);

        $campaigns = DB::table('campaigns')
        ->join('lists', 'campaigns.list_id', '=', 'lists.id')
        ->join('templates', 'campaigns.template_id', '=', 'templates.id')
        ->where('campaigns.user_id', $userId)
        ->orderBy('campaigns.scheduled_at', 'desc')
        ->select([
            'campaigns.*',
            'lists.title       AS list_title',
            'templates.name    AS template_name',
            'templates.subject AS template_subject',
            // Add list_length as a subquery; preserves original query semantics
            DB::raw('(SELECT COUNT(*) FROM list_users WHERE list_users.list_id = lists.id) AS list_length'),
        ])
        ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $campaigns,
        ]);
    }

    /** GET /api/campaign/{id} */
    public function show(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);

        $campaign = DB::table('campaigns')
            ->join('lists', 'campaigns.list_id', '=', 'lists.id')
            ->join('templates', 'campaigns.template_id', '=', 'templates.id')
            ->where('campaigns.user_id', $userId)
            ->where('campaigns.id', $id)
            ->select([
                'campaigns.*',
                'lists.title       AS list_title',
                'templates.name    AS template_name',
                'templates.subject AS template_subject',
            ])
            ->first();

        if (!$campaign) {
            return response()->json(['status' => 'error', 'message' => 'Campaign not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $campaign,
        ], 200);
    }

    /** POST /api/campaign → create */
    public function store(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);

        $data = $request->validate([
            'list_id'          => ['required', 'exists:lists,id'],
            'template_id'      => ['required', 'exists:templates,id'],
            'title'            => ['required', 'string', 'max:255'],
            'subject'          => ['nullable', 'string', 'max:255'],
            'attachments'      => ['nullable', 'array'],
            'attachments.*'    => ['file', 'max:10240'],
            'schedule_option'  => ['required', Rule::in(['now', 'scheduled'])],
            'scheduled_at'     => ['exclude_unless:schedule_option,scheduled', 'required', 'date', 'after_or_equal:now'],
            'reply_to_address' => ['required', 'email'],
            'from_name'        => ['required', 'string', 'max:255'],
            'from_address'     => ['required', 'email'],
        ]);

        // Validate list ownership
        $list = DB::table('lists')->where('id', $data['list_id'])->first();
        if (! $list || $list->user_id !== $userId) {
            return response()->json(['status' => 'error', 'message' => 'List not found or not owned.'], 404);
        }

        $template = DB::table('templates')->find($data['template_id']);

        // enforce active/non-expired plan + subscription
        $planData = $this->getUserActivePlan($userId);
        if (! $planData) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active/non-expired subscription plan found for user.'
            ], 403);
        }
        $plan = $planData['plan'];

        // enforce send limit from user's plan BEFORE creating campaign
        $sendLimitRaw = $plan->send_limit;
        if ($sendLimitRaw !== null) {
            $sendLimit = (int)$sendLimitRaw;
        
            // count already committed campaigns for this user
            $alreadyCommitted = DB::table('campaigns')
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('has_run', 1)
                      ->orWhereIn('status', ['scheduled', 'waiting', 'running']);
                })
                ->count();
        
            // we’re adding one more campaign
            $wouldAdd = 1;
        
            if ($alreadyCommitted + $wouldAdd > $sendLimit) {
                Log::warning('Campaign send limit would be exceeded on creation', [
                    'user_id'           => $userId,
                    'limit'             => $sendLimit,
                    'already_committed' => $alreadyCommitted,
                    'would_add'         => $wouldAdd,
                ]);
                return response()->json([
                    'status'  => 'error',
                    'message' => "Send limit reached. Cannot create campaign because it would exceed the plan's send limit (max {$sendLimit})."
                ], 422);
            }
        }
        

        $utm = [
            'utm_source'   => 'email',
            'utm_medium'   => 'campaign',
            'utm_campaign' => Str::slug($data['title'], '_'),
            'utm_term'     => Str::slug($list->title ?? 'list', '_'),
            'utm_content'  => Str::slug($template->name ?? 'template', '_'),
        ];

        $isNow       = $data['schedule_option'] === 'now';
        $scheduledAt = $isNow ? Carbon::now() : Carbon::parse($data['scheduled_at']);

        // Decide initial status
        if ($isNow) {
            $anotherRunning = DB::table('campaigns')
                ->where('status', 'running')
                ->exists();
            $initialStatus = $anotherRunning ? 'waiting' : 'running';
        } else {
            $initialStatus = 'scheduled';
        }

        $campaignId = DB::table('campaigns')->insertGetId([
            'campaign_uuid'    => Str::uuid(),
            'user_id'          => $userId,
            'list_id'          => $data['list_id'],
            'template_id'      => $data['template_id'],
            'title'            => $data['title'],
            'subject_override' => $data['subject'] ?? null,
            'reply_to_address' => $data['reply_to_address'],
            'from_name'        => $data['from_name'],
            'from_address'     => $data['from_address'],
            'utm_source'       => $utm['utm_source'],
            'utm_medium'       => $utm['utm_medium'],
            'utm_campaign'     => $utm['utm_campaign'],
            'utm_term'         => $utm['utm_term'],
            'utm_content'      => $utm['utm_content'],
            'scheduled_at'     => $scheduledAt,
            'is_active'        => true,
            'has_run'          => false,
            'status'           => $initialStatus,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // attachments
        if ($request->hasFile('attachments')) {
            $paths   = [];
            $destDir = public_path("assets/campaign_attachments/{$userId}/{$campaignId}");
            File::ensureDirectoryExists($destDir, 0755, true);
            foreach ($request->file('attachments') as $file) {
                $fn = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move($destDir, $fn);
                $paths[] = "assets/campaign_attachments/{$userId}/{$campaignId}/{$fn}";
            }
            DB::table('campaigns')
                ->where('id', $campaignId)
                ->update(['attachments' => json_encode($paths)]);
        }

        $campaign = DB::table('campaigns')->where('id', $campaignId)->first();

        // Dispatch only if truly running (not waiting)
        if ($isNow && $campaign->status === 'running') {
            $this->dispatchCampaign($campaign, 'now');
        }

        return response()->json([
            'status'  => 'success',
            'message' => $isNow
                ? ($campaign->status === 'running'
                        ? 'Campaign created and dispatch started.'
                        : 'Campaign created and queued (waiting).')
                : 'Campaign scheduled successfully.',
            'data'    => $campaign,
        ], 201);
    }

    /** PUT /api/campaign/{id} */
    public function update(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        $orig = DB::table('campaigns')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$orig) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }
        if ($orig->has_run) {
            return response()->json(['status' => 'error', 'message' => 'Cannot update a campaign that has already run.'], 422);
        }

        $data = $request->validate([
            'title'            => ['sometimes', 'string', 'max:255'],
            'subject'          => ['nullable', 'string', 'max:255'],
            'attachments'      => ['nullable', 'array'],
            'attachments.*'    => ['file', 'max:10240'],
            'schedule_option'  => ['required', Rule::in(['now', 'scheduled'])],
            'scheduled_at'     => ['exclude_unless:schedule_option,scheduled', 'required', 'date', 'after_or_equal:now'],
            'reply_to_address' => ['sometimes', 'email'],
            'from_name'        => ['sometimes', 'string', 'max:255'],
            'from_address'     => ['sometimes', 'email'],
        ]);

        $isNow       = $data['schedule_option'] === 'now';
        $scheduledAt = $isNow ? Carbon::now() : Carbon::parse($data['scheduled_at']);

        // enforce active/non-expired plan + subscription
        $planData = $this->getUserActivePlan($userId);
        if (! $planData) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active/non-expired subscription plan found for user.'
            ], 403);
        }
        $plan = $planData['plan'];

        // enforce send limit before applying update (similar logic to store)
        $sendLimitRaw = $plan->send_limit;
        if ($sendLimitRaw !== null) {
            $sendLimit = (int)$sendLimitRaw;

            $existingCampaigns = DB::table('campaigns')
                ->where('user_id', $userId)
                ->where(function ($q) use ($id) {
                    $q->where('has_run', 1)
                      ->orWhereIn('status', ['scheduled', 'waiting', 'running']);
                })
                ->where('id', '!=', $id) // exclude self since it's being updated
                ->get();

            $alreadyCommitted = 0;
            foreach ($existingCampaigns as $c) {
                $count = DB::table('list_users')
                    ->where('list_id', $c->list_id)
                    ->where('is_active', 1)
                    ->count();
                $alreadyCommitted += $count;
            }

            // target list active subscriber count (assuming list_id immutable)
            $targetCount = DB::table('list_users')
                ->where('list_id', $orig->list_id)
                ->where('is_active', 1)
                ->count();

            if ($alreadyCommitted + $targetCount > $sendLimit) {
                Log::warning('Campaign send limit would be exceeded on update', [
                    'user_id' => $userId,
                    'limit' => $sendLimit,
                    'already_committed' => $alreadyCommitted,
                    'would_add' => $targetCount,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => "Send limit reached. Cannot update campaign because it would exceed the plan's send limit (max {$sendLimit})."
                ], 422);
            }
        }

        if ($isNow) {
            $anotherRunning = DB::table('campaigns')
                ->where('status', 'running')
                ->where('id', '!=', $id)
                ->exists();
            $newStatus = $anotherRunning ? 'waiting' : 'running';
        } else {
            $newStatus = 'scheduled';
        }

        DB::table('campaigns')->where('id', $id)->update([
            'title'            => $data['title'] ?? $orig->title,
            'subject_override' => array_key_exists('subject', $data)
                ? $data['subject']
                : $orig->subject_override,
            'scheduled_at'     => $scheduledAt,
            'reply_to_address' => $data['reply_to_address'] ?? $orig->reply_to_address,
            'from_name'        => $data['from_name'] ?? $orig->from_name,
            'from_address'     => $data['from_address'] ?? $orig->from_address,
            'status'           => $newStatus,
            'updated_at'       => now(),
        ]);

        if ($request->hasFile('attachments')) {
            $existing = json_decode($orig->attachments, true) ?: [];
            $destDir  = public_path("assets/campaign_attachments/{$userId}/{$id}");
            File::ensureDirectoryExists($destDir, 0755, true);
            foreach ($request->file('attachments') as $file) {
                $fn = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move($destDir, $fn);
                $existing[] = "assets/campaign_attachments/{$userId}/{$id}/{$fn}";
            }
            DB::table('campaigns')
                ->where('id', $id)
                ->update(['attachments' => json_encode($existing)]);
        }

        $campaign = DB::table('campaigns')->where('id', $id)->first();

        if ($isNow && $campaign->status === 'running') {
            $this->dispatchCampaign($campaign, 'now');
        }

        return response()->json([
            'status'  => 'success',
            'message' => $isNow
                ? ($campaign->status === 'running'
                        ? 'Campaign updated and dispatch started.'
                        : 'Campaign updated & queued (waiting).')
                : 'Campaign updated & rescheduled.',
            'data'    => $campaign,
        ], 200);
    }

    /** DELETE /api/campaign/{id} */
    public function destroy(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        $row = DB::table('campaigns')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }
        if ($row->has_run) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete a campaign that has already run.'], 422);
        }

        DB::table('campaigns')->where('id', $id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Campaign deleted successfully.'
        ], 200);
    }

    /**
     * Dispatch a campaign (queues one job per subscriber) IF status is running.
     */
    protected function dispatchCampaign($campaignRow, string $option)
    {
        $campaign = DB::table('campaigns')
            ->join('templates', 'campaigns.template_id', '=', 'templates.id')
            ->select(
                'campaigns.*',
                'templates.subject AS template_subject',
                'templates.body_html'
            )
            ->where('campaigns.id', $campaignRow->id)
            ->first();

        if (!$campaign) return;

        // Do not dispatch if waiting
        if ($campaign->status === 'waiting') {
            Log::info('dispatchCampaign skipped (waiting)', ['campaign_id' => $campaign->id]);
            return;
        }

        if ($campaign->has_run) {
            Log::info('dispatchCampaign skipped (has_run)', ['campaign_id' => $campaign->id]);
            return;
        }

        if ($campaign->status !== 'running') {
            DB::table('campaigns')
                ->where('id', $campaign->id)
                ->update([
                    'status'     => 'running',
                    'updated_at' => now(),
                ]);
            $campaign->status = 'running';
        }

        $subs = DB::table('list_users')
            ->where('list_id', $campaign->list_id)
            ->orderBy('id')
            ->get();

        foreach ($subs as $sub) {
            ProcessCampaignDelivery::dispatch(
                $campaign,
                $sub,
                $option === 'now'
            );
        }

        DB::table('campaigns')
            ->where('id', $campaign->id)
            ->update([
                'has_run'    => true,
                'updated_at' => now(),
            ]);

        Log::info('Campaign dispatched', ['campaign_id' => $campaign->id, 'jobs' => $subs->count()]);
    }

    
}
