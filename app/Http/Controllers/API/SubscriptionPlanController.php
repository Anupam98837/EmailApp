<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionPlanController extends Controller
{
    /**
     * Validation rules for creating/updating plans.
     */
    private function rules(): array
    {
        return [
            'title'                        => 'required|string',
            'description'                  => 'nullable|string',
            'price'                        => 'required|numeric|min:0',
            'billing_cycle'                => 'required|in:monthly,yearly',
            'mailer_settings_admin_ids'    => 'required|array|min:1',
            'mailer_settings_admin_ids.*'  => 'integer|exists:mailer_settings_admin,id',
            'template_limit'              => 'nullable|integer|min:0',
            'send_limit'                  => 'nullable|integer|min:0',
            'list_limit'                  => 'nullable|integer|min:0',
            'discount'                    => 'nullable|numeric|min:0|max:100',
            'status'                     => 'sometimes|required|in:active,inactive',
            'can_add_mailer'             => 'sometimes|boolean',
        ];
    }

    /**
     * GET /api/plans
     */
    public function index()
    {
        Log::info('Listing subscription plans');
        $plans = DB::table('subscription_plans')->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Subscription plans retrieved.',
            'data'    => $plans,
        ], 200);
    }

    /**
     * POST /api/plans
     */
    public function store(Request $request)
    {
        Log::info('Creating subscription plan', ['payload' => $request->all()]);

        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $payload = [
            'title'                         => $request->input('title'),
            'description'                   => $request->input('description'),
            'price'                         => $request->input('price'),
            'billing_cycle'                => $request->input('billing_cycle'),
            'mailer_settings_admin_ids'     => json_encode($request->input('mailer_settings_admin_ids')),
            'template_limit'               => $request->input('template_limit'),
            'send_limit'                   => $request->input('send_limit'),
            'list_limit'                   => $request->input('list_limit'),
            'discount'                     => $request->input('discount'),
            'status'                       => $request->input('status', 'active'),
            'can_add_mailer'               => $request->input('can_add_mailer', false),
            'created_at'                   => now(),
            'updated_at'                   => now(),
        ];

        try {
            $id = DB::table('subscription_plans')->insertGetId($payload);
            $plan = DB::table('subscription_plans')->where('id', $id)->first();

            return response()->json([
                'status'  => 'success',
                'message' => 'Subscription plan created.',
                'data'    => $plan,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Error creating subscription plan', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not create plan.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/plans/{id}
     */
    public function show($id)
    {
        $plan = DB::table('subscription_plans')->where('id', $id)->first();
        if (! $plan) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Subscription plan not found.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Subscription plan retrieved.',
            'data'    => $plan,
        ], 200);
    }

    /**
     * PUT /api/plans/{id}
     */
    public function update(Request $request, $id)
    {
        if (! DB::table('subscription_plans')->where('id', $id)->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Subscription plan not found.',
            ], 404);
        }

        Log::info('Updating subscription plan', ['id' => $id, 'payload' => $request->all()]);

        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $payload = [
            'title'                         => $request->input('title'),
            'description'                   => $request->input('description'),
            'price'                         => $request->input('price'),
            'billing_cycle'                => $request->input('billing_cycle'),
            'mailer_settings_admin_ids'     => json_encode($request->input('mailer_settings_admin_ids')),
            'template_limit'               => $request->input('template_limit'),
            'send_limit'                   => $request->input('send_limit'),
            'list_limit'                   => $request->input('list_limit'),
            'discount'                     => $request->input('discount'),
            'status'                       => $request->input('status', 'active'),
            'can_add_mailer'               => $request->input('can_add_mailer', false),
            'updated_at'                   => now(),
        ];

        try {
            DB::table('subscription_plans')->where('id', $id)->update($payload);
            $plan = DB::table('subscription_plans')->where('id', $id)->first();

            return response()->json([
                'status'  => 'success',
                'message' => 'Subscription plan updated.',
                'data'    => $plan,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Error updating subscription plan', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not update plan.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/plans/{id}/status
     * Toggle active/inactive
     */
    public function toggleStatus($id)
    {
        $plan = DB::table('subscription_plans')->where('id', $id)->first();
        if (! $plan) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Subscription plan not found.',
            ], 404);
        }

        $new = $plan->status === 'active' ? 'inactive' : 'active';

        try {
            DB::table('subscription_plans')->where('id', $id)
              ->update(['status' => $new, 'updated_at' => now()]);

            return response()->json([
                'status'  => 'success',
                'message' => "Status changed to {$new}.",
                'data'    => ['id' => $id, 'status' => $new],
            ], 200);
        } catch (Throwable $e) {
            Log::error('Error toggling plan status', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not change status.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/plans/{id}/mailers
     * Replace associated mailer_settings_admin_ids array.
     */
    public function changeMailers(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'mailer_settings_admin_ids'    => 'required|array|min:1',
            'mailer_settings_admin_ids.*'  => 'integer|exists:mailer_settings_admin,id',
        ]);
        if ($v->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $plan = DB::table('subscription_plans')->where('id', $id)->first();
        if (! $plan) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Subscription plan not found.',
            ], 404);
        }

        try {
            DB::table('subscription_plans')->where('id', $id)
              ->update([
                  'mailer_settings_admin_ids' => json_encode($request->input('mailer_settings_admin_ids')),
                  'updated_at' => now()
              ]);
            return response()->json([
                'status'  => 'success',
                'message' => 'Mailers updated for plan.',
                'data'    => ['id' => $id, 'mailer_settings_admin_ids' => $request->input('mailer_settings_admin_ids')],
            ], 200);
        } catch (Throwable $e) {
            Log::error('Error changing plan mailers', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not change mailers.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * POST /api/plans/{id}/assign
     * Assign this plan to the specified user and sync all associated mailer templates into user mailer settings.
     */
    public function assign(Request $request, $id)
    {
        Log::info("Assigning subscription plan {$id} to user", $request->all());

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }
        $userId = $request->input('user_id');

        $plan = DB::table('subscription_plans')->where('id', $id)->first();
        if (! $plan) {
            Log::warning("Plan not found: {$id}");
            return response()->json(['status' => 'error', 'message' => 'Subscription plan not found.'], 404);
        }

        // Prevent assigning if user already has an active, non-expired subscription
        $existingActiveSub = DB::table('user_subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'desc')
            ->first();

        if ($existingActiveSub) {
            return response()->json([
                'status' => 'error',
                'message' => 'User already has a current active subscription plan.',
                'data' => [
                    'current_plan_id' => $existingActiveSub->plan_id,
                    'expires_at' => $existingActiveSub->expires_at,
                ],
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1) Update user subscription plan fields on users table
            DB::table('users')->where('id', $userId)->update([
                'subscription_plan_id'    => $plan->id,
                'subscription_plan_title' => $plan->title,
                'updated_at'              => now(),
            ]);
            Log::info("Updated user {$userId} with plan {$plan->id} ({$plan->title})");

            // 2) Calculate amount after discount
            $base = floatval($plan->price);
            if (! empty($plan->discount)) {
                $base = $base * ((100 - floatval($plan->discount)) / 100);
            }
            $amount = round($base, 2); // canonical amount_decimal

            // 3) Create fresh subscription (since no active one exists)
            $now = now();
            $cycle = $plan->billing_cycle; // 'monthly' or 'yearly'

            $startedAt = $now;
            $expiresAt = (clone $now);
            if ($cycle === 'yearly') {
                $expiresAt->addYear();
            } else {
                $expiresAt->addMonth();
            }

            $subscriptionId = DB::table('user_subscriptions')->insertGetId([
                'user_id'        => $userId,
                'plan_id'        => $plan->id,
                'billing_cycle'  => $cycle,
                'amount_decimal' => $amount,
                'currency'       => 'INR',
                'started_at'     => $startedAt,
                'expires_at'     => $expiresAt,
                'status'         => 'active',
                'payment_id'     => null, // no payment linked for direct assign
                'metadata'       => json_encode([
                    'created_via_plan_assign' => true,
                    'created_at' => $now->toISOString(),
                ]),
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            // 4) Resolve mailer_settings_admin_ids (support both new array field and legacy single id)
            $mailerAdminIds = [];
            if (!empty($plan->mailer_settings_admin_ids)) {
                $decoded = json_decode($plan->mailer_settings_admin_ids, true);
                if (is_array($decoded)) {
                    $mailerAdminIds = array_values(array_filter($decoded, fn($v) => is_numeric($v)));
                }
            } elseif (!empty($plan->mailer_settings_admin_id)) {
                // backward compatibility
                $mailerAdminIds = [ $plan->mailer_settings_admin_id ];
            }

            $assignedMailerSettings = [];
            $defaultSet = false;

            foreach ($mailerAdminIds as $mailerAdminId) {
                $template = DB::table('mailer_settings_admin')
                    ->where('id', $mailerAdminId)
                    ->first();
                if (! $template) {
                    Log::warning("Mailer template not found for admin id {$mailerAdminId}, skipping.");
                    continue;
                }

                $isDefault = false;
                if (! $defaultSet) {
                    $isDefault = true;
                    $defaultSet = true;
                }

                $payload = [
                    'mailer'       => $template->mailer,
                    'host'         => $template->host,
                    'port'         => $template->port,
                    'username'     => $template->username,
                    'password'     => $template->password,
                    'encryption'   => $template->encryption,
                    'from_address' => $template->from_address,
                    'from_name'    => $template->from_name,
                    'user_id'      => $userId,
                    'is_default'   => $isDefault ? 1 : 0,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];

                $existingMailer = DB::table('mailer_settings')
                    ->where('user_id', $userId)
                    ->where('mailer', $template->mailer)
                    ->where('username', $template->username)
                    ->first();

                if ($existingMailer) {
                    if ($isDefault) {
                        DB::table('mailer_settings')
                            ->where('user_id', $userId)
                            ->update(['is_default' => 0]);
                    }
                    DB::table('mailer_settings')->where('id', $existingMailer->id)->update($payload);
                    $assignedMailerSettings[] = ['id' => $existingMailer->id, 'action' => 'updated', 'is_default' => $isDefault];
                } else {
                    if ($isDefault) {
                        DB::table('mailer_settings')
                            ->where('user_id', $userId)
                            ->update(['is_default' => 0]);
                    }
                    $newId = DB::table('mailer_settings')->insertGetId($payload);
                    $assignedMailerSettings[] = ['id' => $newId, 'action' => 'created', 'is_default' => $isDefault];
                }
            }

            DB::commit();

            $subscription = DB::table('user_subscriptions')->where('id', $subscriptionId)->first();

            return response()->json([
                'status'  => 'success',
                'message' => 'Plan assigned, subscription created, and mailer settings synced to user.',
                'data'    => [
                    'user_id' => $userId,
                    'plan_id' => $plan->id,
                    'subscription' => $subscription,
                    'mailers' => $assignedMailerSettings,
                ],
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error assigning plan to user', ['exception' => $e->getMessage(), 'payload' => $request->all()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not assign plan.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ... (keep all your existing methods above)

    /**
     * POST /api/plans/{id}/renew
     * Renew user's existing subscription plan
     */
    public function renewPlan(Request $request, $id)
    {
        Log::info("Renewing subscription plan {$id} for user", $request->all());

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }
        
        $userId = $request->input('user_id');

        // Check if plan exists
        $plan = DB::table('subscription_plans')->where('id', $id)->first();
        if (!$plan) {
            Log::warning("Plan not found for renewal: {$id}");
            return response()->json([
                'status' => 'error', 
                'message' => 'Subscription plan not found.'
            ], 404);
        }

        // Check if user exists and has this plan assigned
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        if ($user->subscription_plan_id != $id) {
            return response()->json([
                'status' => 'error',
                'message' => 'User does not have this plan assigned. Use assign instead.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get the latest subscription for this user and plan
            $latestSubscription = DB::table('user_subscriptions')
                ->where('user_id', $userId)
                ->where('plan_id', $id)
                ->orderBy('expires_at', 'desc')
                ->first();

            $now = now();
            
            // Calculate new start and expiry dates
            $startedAt = $now;
            $expiresAt = (clone $now);
            
            // If there's an existing active subscription, extend from expiry date
            // Otherwise, start from now
            if ($latestSubscription && $latestSubscription->expires_at > $now) {
                $startedAt = \Carbon\Carbon::parse($latestSubscription->expires_at);
                $expiresAt = (clone $startedAt);
            }
            
            // Add billing cycle duration
            if ($plan->billing_cycle === 'yearly') {
                $expiresAt->addYear();
            } else {
                $expiresAt->addMonth();
            }

            // Calculate amount after discount
            $base = floatval($plan->price);
            if (!empty($plan->discount)) {
                $base = $base * ((100 - floatval($plan->discount)) / 100);
            }
            $amount = round($base, 2);

            // Create new subscription record for renewal
            $subscriptionId = DB::table('user_subscriptions')->insertGetId([
                'user_id'        => $userId,
                'plan_id'        => $plan->id,
                'billing_cycle'  => $plan->billing_cycle,
                'amount_decimal' => $amount,
                'currency'       => 'INR',
                'started_at'     => $startedAt,
                'expires_at'     => $expiresAt,
                'status'         => 'active',
                'payment_id'     => null,
                'metadata'       => json_encode([
                    'renewed_from_subscription_id' => $latestSubscription->id ?? null,
                    'renewed_at' => $now->toISOString(),
                    'renewal_type' => 'manual_renewal',
                ]),
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            // Update user's subscription info (in case plan details changed)
            DB::table('users')->where('id', $userId)->update([
                'subscription_plan_title' => $plan->title, // Update title in case it changed
                'updated_at'              => $now,
            ]);

            // Sync mailer settings if they've been updated in the plan
            $this->syncMailerSettingsForRenewal($userId, $plan);

            DB::commit();

            $newSubscription = DB::table('user_subscriptions')->where('id', $subscriptionId)->first();

            Log::info('Plan renewed successfully', [
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'subscription_id' => $subscriptionId,
                'expires_at' => $expiresAt->toISOString()
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Subscription plan renewed successfully.',
                'data'    => [
                    'user_id' => $userId,
                    'plan_id' => $plan->id,
                    'subscription' => $newSubscription,
                    'renewal_details' => [
                        'previous_expiry' => $latestSubscription->expires_at ?? null,
                        'new_expiry' => $expiresAt->toISOString(),
                        'extended_by' => $plan->billing_cycle,
                    ],
                ],
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error renewing plan for user', [
                'exception' => $e->getMessage(), 
                'user_id' => $userId,
                'plan_id' => $id
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not renew plan.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync mailer settings during renewal - only add new ones, don't override existing
     */
    protected function syncMailerSettingsForRenewal(int $userId, object $plan): void
    {
        // Resolve mailer_settings_admin_ids
        $mailerAdminIds = [];
        if (!empty($plan->mailer_settings_admin_ids)) {
            $decoded = json_decode($plan->mailer_settings_admin_ids, true);
            if (is_array($decoded)) {
                $mailerAdminIds = array_values(array_filter($decoded, fn($v) => is_numeric($v)));
            }
        } elseif (!empty($plan->mailer_settings_admin_id)) {
            $mailerAdminIds = [$plan->mailer_settings_admin_id];
        }

        $addedMailers = [];
        
        foreach ($mailerAdminIds as $mailerAdminId) {
            $template = DB::table('mailer_settings_admin')
                ->where('id', $mailerAdminId)
                ->first();
                
            if (!$template) {
                Log::warning("Mailer template not found for admin id {$mailerAdminId} during renewal, skipping.");
                continue;
            }

            // Check if user already has this mailer configuration
            $existingMailer = DB::table('mailer_settings')
                ->where('user_id', $userId)
                ->where('mailer', $template->mailer)
                ->where('username', $template->username)
                ->first();

            // Only add if it doesn't exist
            if (!$existingMailer) {
                $payload = [
                    'mailer'       => $template->mailer,
                    'host'         => $template->host,
                    'port'         => $template->port,
                    'username'     => $template->username,
                    'password'     => $template->password,
                    'encryption'   => $template->encryption,
                    'from_address' => $template->from_address,
                    'from_name'    => $template->from_name,
                    'user_id'      => $userId,
                    'is_default'   => 0, // Don't change default during renewal
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];

                $newId = DB::table('mailer_settings')->insertGetId($payload);
                $addedMailers[] = ['id' => $newId, 'mailer' => $template->mailer];
            }
        }

        if (!empty($addedMailers)) {
            Log::info('Added new mailer settings during renewal', [
                'user_id' => $userId,
                'added_mailers' => $addedMailers
            ]);
        }
    }

    /**
     * POST /api/plans/{id}/upgrade
     * Upgrade user to a different plan (optional enhancement)
     */
    public function upgradePlan(Request $request, $newPlanId)
    {
        Log::info("Upgrading user to plan {$newPlanId}", $request->all());

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $userId = $request->input('user_id');

        // Check if new plan exists
        $newPlan = DB::table('subscription_plans')->where('id', $newPlanId)->first();
        if (!$newPlan) {
            return response()->json([
                'status' => 'error',
                'message' => 'New subscription plan not found.'
            ], 404);
        }

        // Get user's current plan
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        DB::beginTransaction();
        try {
            // End current active subscription
            DB::table('user_subscriptions')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'updated_at' => now()]);

            // Assign new plan (this will create new subscription)
            // You can call your existing assign method or replicate the logic
            $this->assign(new Request(['user_id' => $userId]), $newPlanId);

            DB::commit();

            Log::info('Plan upgraded successfully', [
                'user_id' => $userId,
                'from_plan_id' => $user->subscription_plan_id,
                'to_plan_id' => $newPlanId
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Plan upgraded successfully.',
                'data'    => [
                    'user_id' => $userId,
                    'previous_plan_id' => $user->subscription_plan_id,
                    'new_plan_id' => $newPlanId,
                ],
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error upgrading plan', [
                'exception' => $e->getMessage(),
                'user_id' => $userId,
                'new_plan_id' => $newPlanId
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not upgrade plan.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


public function myPlan(Request $request)
{
    Log::info('myPlan started', ['headers' => $request->headers->all()]);

    // 1. Authenticate user via bearer token
    $header = $request->header('Authorization');
    if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $m)) {
        Log::warning('Authorization header missing or malformed');
        return response()->json(['status' => 'error', 'message' => 'Token not provided'], 401);
    }
    $rawToken = $m[1];
    $tokenHash = hash('sha256', $rawToken);
    $record = DB::table('personal_access_tokens')
        ->where('token', $tokenHash)
        ->where('tokenable_type', 'App\\Models\\User')
        ->first();
    if (! $record) {
        Log::warning('Invalid token during myPlan lookup', ['token_hash' => $tokenHash]);
        return response()->json(['status' => 'error', 'message' => 'Invalid token'], 401);
    }
    $userId = $record->tokenable_id;
    Log::info('Authenticated user resolved from token', ['user_id' => $userId]);

    // 2. Load user and assigned plan
    $user = DB::table('users')->where('id', $userId)->first();
    if (! $user) {
        Log::error('User not found in myPlan', ['user_id' => $userId]);
        return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
    }

    if (empty($user->subscription_plan_id)) {
        Log::info('User has no subscription plan assigned', ['user_id' => $userId]);
        return response()->json([
            'status' => 'error',
            'message' => 'No subscription plan assigned to user.'
        ], 404);
    }

    $plan = DB::table('subscription_plans')
        ->where('id', $user->subscription_plan_id)
        ->first();

    if (! $plan) {
        Log::error('Assigned subscription plan not found', ['plan_id' => $user->subscription_plan_id]);
        return response()->json([
            'status' => 'error',
            'message' => 'Assigned subscription plan not found.'
        ], 404);
    }

    Log::info('Plan fetched', ['plan_id' => $plan->id, 'title' => $plan->title]);

    // 3. Resolve mailer_settings_admin_ids (support both new array and legacy single id)
    $mailerAdminIds = [];
    if (!empty($plan->mailer_settings_admin_ids)) {
        $decoded = json_decode($plan->mailer_settings_admin_ids, true);
        if (is_array($decoded)) {
            $mailerAdminIds = array_values(array_filter($decoded, fn($v) => is_numeric($v)));
            Log::info('Resolved mailer_settings_admin_ids (array)', ['ids' => $mailerAdminIds]);
        } else {
            Log::warning('mailer_settings_admin_ids present but failed to decode as array', ['raw' => $plan->mailer_settings_admin_ids]);
        }
    } elseif (!empty($plan->mailer_settings_admin_id)) {
        // backward compatibility if legacy single field exists
        $mailerAdminIds = [(int)$plan->mailer_settings_admin_id];
        Log::info('Resolved legacy mailer_settings_admin_id', ['id' => $plan->mailer_settings_admin_id]);
    } else {
        Log::info('No mailer templates associated with plan', ['plan_id' => $plan->id]);
    }

    // 4. Fetch associated mailer templates
    $mailers = [];
    if (!empty($mailerAdminIds)) {
        $rawMailers = DB::table('mailer_settings_admin')
            ->whereIn('id', $mailerAdminIds)
            ->get();
        Log::info('Fetched mailer_settings_admin records', ['count' => $rawMailers->count(), 'requested_ids' => $mailerAdminIds]);

        $mailers = $rawMailers->map(function ($m) {
            return [
                'id'            => $m->id,
                'mailer'        => $m->mailer,
                'host'          => $m->host,
                'port'          => $m->port,
                'username'      => $m->username,
                'encryption'    => $m->encryption,
                'from_address'  => $m->from_address,
                'from_name'     => $m->from_name,
                'status'        => $m->status ?? null,
                'created_at'    => $m->created_at,
                'updated_at'    => $m->updated_at,
            ];
        })->toArray();
    }

    // 5. Normalize limits (null means unlimited)
    $limits = [
        'template_limit' => is_null($plan->template_limit) ? 'unlimited' : (int)$plan->template_limit,
        'send_limit'     => is_null($plan->send_limit) ? 'unlimited' : (int)$plan->send_limit,
        'list_limit'     => is_null($plan->list_limit) ? 'unlimited' : (int)$plan->list_limit,
    ];
    Log::info('Normalized limits', ['limits' => $limits]);

    // 6. Fetch latest subscription for this user + plan
    $subscription = DB::table('user_subscriptions')
        ->where('user_id', $userId)
        ->where('plan_id', $plan->id)
        ->orderBy('expires_at', 'desc')
        ->first();

    $now = now();
    $subscriptionInfo = null;
    if ($subscription) {
        $expiresAt = \Carbon\Carbon::parse($subscription->expires_at);
        $startedAt = \Carbon\Carbon::parse($subscription->started_at);
        $isExpired = $expiresAt->isPast();
        $isActive = $subscription->status === 'active' && ! $isExpired;
        $remainingDays = $isExpired ? 0 : $now->diffInDays($expiresAt);
        $subscriptionInfo = [
            'id' => $subscription->id,
            'billing_cycle' => $subscription->billing_cycle,
            'amount_decimal' => $subscription->amount_decimal,
            'currency' => $subscription->currency,
            'started_at' => $subscription->started_at,
            'expires_at' => $subscription->expires_at,
            'status' => $subscription->status,
            'is_active' => $isActive,
            'is_expired' => $isExpired,
            'remaining_days' => $remainingDays,
            'payment_id' => $subscription->payment_id,
            'metadata' => is_string($subscription->metadata) ? json_decode($subscription->metadata, true) : $subscription->metadata,
        ];
    } else {
        Log::info('No subscription record found for user/plan', ['user_id' => $userId, 'plan_id' => $plan->id]);
    }

    // 7. Build response
    $response = [
        'user' => [
            'id' => $user->id,
            'subscription_plan_id'    => $user->subscription_plan_id,
            'subscription_plan_title' => $user->subscription_plan_title ?? $plan->title,
        ],
        'plan' => [
            'id'                          => $plan->id,
            'title'                       => $plan->title,
            'description'                 => $plan->description,
            'price'                       => $plan->price,
            'billing_cycle'              => $plan->billing_cycle,
            'discount'                   => $plan->discount,
            'can_add_mailer'             => (bool)$plan->can_add_mailer,
            'status'                     => $plan->status,
            'limits'                     => $limits,
            'mailer_settings_admin_ids'  => $mailerAdminIds,
            'created_at'                 => $plan->created_at,
            'updated_at'                 => $plan->updated_at,
        ],
        'subscription' => $subscriptionInfo,
        'mailers' => $mailers,
    ];

    Log::info('myPlan response prepared', [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'mailer_count' => count($mailers),
        'has_subscription' => $subscriptionInfo ? true : false,
        'subscription_active' => $subscriptionInfo['is_active'] ?? false,
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Current user subscription plan retrieved.',
        'data'    => $response,
    ], 200);
}





}
