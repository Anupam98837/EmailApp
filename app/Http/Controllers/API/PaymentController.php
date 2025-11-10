<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Throwable;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected const USER_TYPE = 'App\\Models\\User';

    /**
     * Create an order (Razorpay) and internal payment record.
     */
    public function createOrder(Request $request)
    {
        // Log::info('createOrder called', [
        //     'headers' => $this->redactHeaders($request->headers->all()),
        //     'payload' => $request->all()
        // ]);

        $user = $this->getAuthenticatedUser($request);
        if (! $user) {
            Log::warning('createOrder: unauthorized access attempt');
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        // Log::info('Authenticated user for createOrder', ['user_id' => $user->id]);

        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);
        // Log::info('Validation passed for createOrder', [
        //     'plan_id' => $request->input('plan_id'),
        //     'billing_cycle' => $request->input('billing_cycle')
        // ]);

        $userId = $user->id;
        $planId = $request->input('plan_id');
        $billingCycle = $request->input('billing_cycle');

        $plan = DB::table('subscription_plans')->where('id', $planId)->first();
        if (! $plan) {
            // Log::error('Plan not found in createOrder', ['plan_id' => $planId]);
            return response()->json(['status' => 'error', 'message' => 'Plan not found'], 404);
        }

        // Compute amount with billing cycle + discount
        $base = floatval($plan->price);
        if ($billingCycle === 'yearly') {
            $base *= 12;
        }
        if (! empty($plan->discount)) {
            $base = $base * ((100 - floatval($plan->discount)) / 100);
        }
        $amount = round($base, 2); // INR
        // Log::info('Computed amount', [
        //     'amount' => $amount,
        //     'billing_cycle' => $billingCycle,
        //     'discount' => $plan->discount,
        // ]);

        // Create internal payment record
        $paymentId = DB::table('subscription_payments')->insertGetId([
            'user_id' => $userId,
            'plan_id' => $planId,
            'gateway' => 'razorpay',
            'amount_decimal' => $amount,
            'billing_cycle' => $billingCycle,
            'currency' => 'INR',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Log::info('Internal payment record created', ['payment_record_id' => $paymentId]);

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $order = $api->order->create([
                'receipt' => "sub_pay_{$paymentId}",
                'amount' => intval($amount * 100), // paise
                'currency' => 'INR',
                'payment_capture' => 1,
                'notes' => [
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'billing_cycle' => $billingCycle,
                    'payment_record_id' => $paymentId,
                ],
            ]);
            // Log::info('Razorpay order created', ['order_id' => $order->id]);

            DB::table('subscription_payments')->where('id', $paymentId)
                ->update(['gateway_payment_id' => $order->id, 'updated_at' => now()]);
            // Log::info('Payment record updated with gateway order id', [
            //     'payment_record_id' => $paymentId,
            //     'gateway_payment_id' => $order->id
            // ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'payment_record_id' => $paymentId,
                    'order_id' => $order->id,
                    'amount' => $amount,
                    'currency' => 'INR',
                    'key' => config('services.razorpay.key'),
                ],
            ]);
        } catch (Throwable $e) {
            // Log::error('Razorpay order creation error', [
            //     'error' => $e->getMessage(),
            //     'payment_record_id' => $paymentId
            // ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Razorpay order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upgrade endpoint: user can upgrade to a new plan, applying credit for unused time on existing active subscription.
     */
    public function upgrade(Request $request)
    {
        // Log::info('upgrade called', ['payload' => $request->all()]);

        $user = $this->getAuthenticatedUser($request);
        if (! $user) {
            return response()->json(['status'=>'error','message'=>'Unauthorized'], 401);
        }

        $request->validate([
            'new_plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $userId = $user->id;
        $newPlanId = $request->input('new_plan_id');
        $billingCycle = $request->input('billing_cycle');

        $newPlan = DB::table('subscription_plans')->where('id', $newPlanId)->first();
        if (! $newPlan) {
            return response()->json(['status'=>'error','message'=>'New plan not found'], 404);
        }

        // Get existing active subscription if any
        $existingSub = DB::table('user_subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('expires_at','desc')
            ->first();

        // Compute price for new plan
        $newBase = floatval($newPlan->price);
        if ($billingCycle === 'yearly') $newBase *= 12;
        if (! empty($newPlan->discount)) {
            $newBase = $newBase * ((100 - floatval($newPlan->discount)) / 100);
        }
        $newPrice = round($newBase, 2);

        $credit = 0.0;
        if ($existingSub) {
            $now = Carbon::now();
            $expiresAt = Carbon::parse($existingSub->expires_at);
            $startedAt = Carbon::parse($existingSub->started_at);
            if ($expiresAt->isFuture()) {
                $totalSeconds = $expiresAt->diffInSeconds($startedAt);
                $remainingSeconds = $expiresAt->diffInSeconds($now);
                if ($totalSeconds > 0) {
                    $ratio = $remainingSeconds / $totalSeconds;
                    // credit proportionally from existing subscription amount
                    $credit = round(floatval($existingSub->amount_decimal) * $ratio, 2);
                }
            }
        }

        // Determine amount due after credit
        $due = $newPrice - $credit;
        if ($due < 0) $due = 0.0;

        // Log::info('Upgrade calculation', [
        //     'new_price' => $newPrice,
        //     'credit' => $credit,
        //     'amount_due' => $due,
        // ]);

        // Create payment order for difference if needed
        if ($due > 0) {
            $paymentId = DB::table('subscription_payments')->insertGetId([
                'user_id' => $userId,
                'plan_id' => $newPlanId,
                'gateway' => 'razorpay',
                'amount_decimal' => $due,
                'billing_cycle' => $billingCycle,
                'currency' => 'INR',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
                'metadata' => json_encode([
                    'upgrade_from_subscription_id' => $existingSub->id ?? null,
                    'credit_applied' => $credit,
                ]),
            ]);

            try {
                $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
                $order = $api->order->create([
                    'receipt' => "upgrade_pay_{$paymentId}",
                    'amount' => intval($due * 100),
                    'currency' => 'INR',
                    'payment_capture' => 1,
                    'notes' => [
                        'user_id' => $userId,
                        'plan_id' => $newPlanId,
                        'billing_cycle' => $billingCycle,
                        'payment_record_id' => $paymentId,
                        'upgrade_credit' => $credit,
                    ],
                ]);

                DB::table('subscription_payments')->where('id', $paymentId)
                    ->update(['gateway_payment_id' => $order->id, 'updated_at' => now()]);
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'payment_record_id' => $paymentId,
                        'order_id' => $order->id,
                        'amount_due' => $due,
                        'credit' => $credit,
                        'new_price' => $newPrice,
                        'currency' => 'INR',
                        'key' => config('services.razorpay.key'),
                    ]
                ]);
            } catch (Throwable $e) {
                // Log::error('Error creating upgrade Razorpay order', ['error' => $e->getMessage()]);
                return response()->json([
                    'status'=>'error',
                    'message'=>'Failed to create upgrade order',
                    'error'=>$e->getMessage(),
                ], 500);
            }
        } else {
            // No payment needed: directly assign new plan and adjust subscription
            DB::beginTransaction();
            try {
                // Expire existing subscription immediately
                if ($existingSub) {
                    DB::table('user_subscriptions')->where('id', $existingSub->id)->update([
                        'status' => 'expired',
                        'updated_at' => now(),
                    ]);
                }

                // Assign new plan (reuse plan controller logic)
                app(\App\Http\Controllers\API\SubscriptionPlanController::class)
                    ->assign(new Request(['user_id' => $userId]), $newPlanId);

                // Sync mailers and update user record explicitly
                $plan = DB::table('subscription_plans')->where('id', $newPlanId)->first();
                if ($plan) {
                    $this->syncMailersFromPlan($userId, $plan);
                    DB::table('users')->where('id', $userId)->update([
                        'subscription_plan_id' => $plan->id,
                        'subscription_plan_title' => $plan->title,
                        'updated_at' => now(),
                    ]);
                }

                DB::commit();
                return response()->json([
                    'status'=>'success',
                    'message'=>'Upgraded plan applied with credit; no additional payment required.',
                    'data'=>[
                        'credit' => $credit,
                        'new_price' => $newPrice,
                    ],
                ]);
            } catch (Throwable $e) {
                DB::rollBack();
                // Log::error('Upgrade assignment failure', ['error'=>$e->getMessage()]);
                return response()->json([
                    'status'=>'error',
                    'message'=>'Upgrade failed',
                    'error'=>$e->getMessage(),
                ], 500);
            }
        }
    }

    /**
     * Verify checkout payment signature (client-side) before finalizing on frontend.
     */
    public function verifyPayment(Request $request)
    {
        // Log::info('verifyPayment called', ['payload' => $request->all()]);

        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);
        // Log::info('Validation passed for verifyPayment');

        $orderId = $request->input('razorpay_order_id');
        $paymentId = $request->input('razorpay_payment_id');
        $signature = $request->input('razorpay_signature');

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ];
            $api->utility->verifyPaymentSignature($attributes);
            // Log::info('Client-side signature verified', ['order_id' => $orderId, 'payment_id' => $paymentId]);
        } catch (SignatureVerificationError $e) {
            // Log::warning('Client-side payment signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $record = DB::table('subscription_payments')->where('gateway_payment_id', $orderId)->first();
        if (! $record) {
            // Log::error('Payment record not found in verifyPayment', ['gateway_order_id' => $orderId]);
            return response()->json(['status' => 'error', 'message' => 'Payment record not found'], 404);
        }

        if ($record->status !== 'paid') {
            // Log::info('Finalizing verified payment (treating as captured)', ['payment_record_id' => $record->id]);
            DB::beginTransaction();
            try {
                $existingMeta = [];
                if (is_string($record->metadata) && $record->metadata !== '') {
                    $decoded = json_decode($record->metadata, true);
                    if (is_array($decoded)) {
                        $existingMeta = $decoded;
                    }
                } elseif (is_array($record->metadata)) {
                    $existingMeta = $record->metadata;
                }

                $mergedMeta = array_merge($existingMeta, [
                    'razorpay_payment_id' => $paymentId,
                    'verified_at' => Carbon::now()->toISOString(),
                ]);

                DB::table('subscription_payments')->where('id', $record->id)->update([
                    'status' => 'paid',
                    'updated_at' => now(),
                    'metadata' => json_encode($mergedMeta),
                ]);
                // Log::info('Marked payment as paid (via verifyPayment)', ['payment_record_id' => $record->id]);

                $subscription = $this->createOrExtendUserSubscription($record, $record->id);
                // Log::info('Subscription created/extended (via verifyPayment)', [
                //     'payment_record_id' => $record->id,
                //     'subscription_id' => $subscription->id ?? null
                // ]);

                $assignResponse = app(\App\Http\Controllers\API\SubscriptionPlanController::class)
                    ->assign(new Request(['user_id' => $record->user_id]), $record->plan_id);
                // Log::info('Assigned plan to user (via verifyPayment)', ['response' => $assignResponse->getContent()]);

                $plan = DB::table('subscription_plans')->where('id', $record->plan_id)->first();
                if ($plan) {
                    $this->syncMailersFromPlan($record->user_id, $plan);
                    DB::table('users')->where('id', $record->user_id)->update([
                        'subscription_plan_id' => $plan->id,
                        'subscription_plan_title' => $plan->title,
                        'updated_at' => now(),
                    ]);
                    // Log::info('User record updated with new plan and mailers synced', ['user_id' => $record->user_id, 'plan_id' => $plan->id]);
                }

                DB::commit();

                $record = DB::table('subscription_payments')->where('id', $record->id)->first();
                $subscription = DB::table('user_subscriptions')
                    ->where('user_id', $record->user_id)
                    ->where('plan_id', $record->plan_id)
                    ->where('status', 'active')
                    ->orderBy('expires_at', 'desc')
                    ->first();
            } catch (Throwable $e) {
                DB::rollBack();
                // Log::error('verifyPayment fulfillment failed', [
                //     'error' => $e->getMessage(),
                //     'payment_record' => $record,
                // ]);
                return response()->json(['status' => 'error', 'message' => 'Processing failure'], 500);
            }
        } else {
            // Log::info('verifyPayment: payment already marked paid, skipping fulfillment', ['payment_record_id' => $record->id]);
            $subscription = DB::table('user_subscriptions')
                ->where('user_id', $record->user_id)
                ->where('plan_id', $record->plan_id)
                ->where('status', 'active')
                ->orderBy('expires_at', 'desc')
                ->first();

            $plan = DB::table('subscription_plans')->where('id', $record->plan_id)->first();
            if ($plan) {
                $this->syncMailersFromPlan($record->user_id, $plan);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'payment_record' => $record,
                'subscription' => $subscription,
            ],
        ]);
    }

    public function webhook(Request $request)
    {
        // Log::info('webhook received', ['headers' => $this->redactHeaders($request->headers->all())]);
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        // Log::info('Webhook payload raw', ['event_payload' => $payload]);

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');
            if (! $webhookSecret) {
                // Log::warning('Webhook secret not configured');
                return response()->json(['status' => 'error', 'message' => 'Webhook secret missing'], 500);
            }
            $api->utility->verifyWebhookSignature($payload, $signature, $webhookSecret);
            // Log::info('Webhook signature verified');
        } catch (SignatureVerificationError $e) {
            // Log::warning('Invalid Razorpay webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        } catch (Throwable $e) {
            // Log::error('Error during webhook signature verification', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Verification failure'], 500);
        }

        $json = $request->json()->all();
        $event = $json['event'] ?? '';
        // Log::info('Webhook event type', ['event' => $event]);

        if ($event === 'payment.captured') {
            $paymentEntity = $json['payload']['payment']['entity'] ?? null;
            if (! $paymentEntity) {
                Log::warning('payment.captured webhook missing entity');
                return response()->json(['status' => 'error', 'message' => 'Malformed webhook'], 400);
            }

            $orderId = $paymentEntity['order_id'] ?? null;
            $status = $paymentEntity['status'] ?? '';

            $record = DB::table('subscription_payments')->where('gateway_payment_id', $orderId)->first();
            if (! $record) {
                // Log::error('Payment record not found for order in webhook', ['order_id' => $orderId]);
                return response()->json(['status' => 'error', 'message' => 'Record missing'], 404);
            }

            if ($status === 'captured' && $record->status !== 'paid') {
                // Log::info('Processing captured payment', ['payment_record_id' => $record->id]);
                DB::beginTransaction();
                try {
                    $existingMeta = [];
                    if (is_string($record->metadata) && $record->metadata !== '') {
                        $decoded = json_decode($record->metadata, true);
                        if (is_array($decoded)) {
                            $existingMeta = $decoded;
                        }
                    } elseif (is_array($record->metadata)) {
                        $existingMeta = $record->metadata;
                    }

                    $mergedMeta = array_merge($existingMeta, [
                        'webhook_captured_at' => Carbon::now()->toISOString(),
                        'payment_entity' => $paymentEntity,
                    ]);

                    DB::table('subscription_payments')->where('id', $record->id)->update([
                        'status' => 'paid',
                        'updated_at' => now(),
                        'metadata' => json_encode($mergedMeta),
                    ]);
                    // Log::info('Marked payment as paid', ['payment_record_id' => $record->id]);

                    $subscription = $this->createOrExtendUserSubscription($record, $record->id);
                    // Log::info('Subscription created/extended for payment', [
                    //     'payment_record_id' => $record->id,
                    //     'subscription_id' => $subscription->id ?? null
                    // ]);

                    $assignResponse = app(\App\Http\Controllers\API\SubscriptionPlanController::class)
                        ->assign(new Request(['user_id' => $record->user_id]), $record->plan_id);
                    // Log::info('Assign plan via webhook response', ['response' => $assignResponse->getContent()]);

                    $plan = DB::table('subscription_plans')->where('id', $record->plan_id)->first();
                    if ($plan) {
                        $this->syncMailersFromPlan($record->user_id, $plan);
                        DB::table('users')->where('id', $record->user_id)->update([
                            'subscription_plan_id' => $plan->id,
                            'subscription_plan_title' => $plan->title,
                            'updated_at' => now(),
                        ]);
                        // Log::info('User record updated with new plan via webhook and mailers synced', ['user_id' => $record->user_id, 'plan_id' => $plan->id]);
                    }

                    DB::commit();
                } catch (Throwable $e) {
                    DB::rollBack();
                    // Log::error('Webhook processing failed', [
                    //     'error' => $e->getMessage(),
                    //     'payment_record' => $record,
                    // ]);
                    return response()->json(['status' => 'error', 'message' => 'Processing failure'], 500);
                }
            } else {
                // Log::info('No action needed for payment.captured', [
                //     'status' => $status,
                //     'current_record_status' => $record->status
                // ]);
            }
        } elseif ($event === 'payment.failed') {
            $paymentEntity = $json['payload']['payment']['entity'] ?? null;
            $orderId = $paymentEntity['order_id'] ?? null;
            $record = DB::table('subscription_payments')->where('gateway_payment_id', $orderId)->first();
            if ($record && $record->status !== 'failed') {
                DB::table('subscription_payments')->where('id', $record->id)->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                    'metadata' => json_encode($paymentEntity),
                ]);
                // Log::info('Payment marked failed via webhook', ['payment_id' => $record->id]);
            } else {
                // Log::info('payment.failed received but no update required', ['order_id' => $orderId]);
            }
        } else {
            // Log::info('Webhook event ignored (not handled)', ['event' => $event]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Create or extend a user_subscriptions entry based on payment record.
     */
    protected function createOrExtendUserSubscription(object $paymentRecord, int $paymentId)
    {
        // Log::info('createOrExtendUserSubscription called', ['payment_record' => $paymentRecord]);
        $now = Carbon::now();
        $cycle = $paymentRecord->billing_cycle;

        $existing = DB::table('user_subscriptions')
            ->where('user_id', $paymentRecord->user_id)
            ->where('plan_id', $paymentRecord->plan_id)
            ->where('status', 'active')
            ->orderBy('expires_at', 'desc')
            ->first();

        if ($existing && Carbon::parse($existing->expires_at)->isFuture()) {
            // Log::info('Extending existing subscription', ['existing_subscription' => $existing]);
            $newExpires = Carbon::parse($existing->expires_at);
            if ($cycle === 'yearly') {
                $newExpires = $newExpires->addYear();
            } else {
                $newExpires = $newExpires->addMonth();
            }

            DB::table('user_subscriptions')->where('id', $existing->id)->update([
                'expires_at' => $newExpires,
                'amount_decimal' => $paymentRecord->amount_decimal,
                'payment_id' => $paymentId,
                'metadata' => json_encode([
                    'extended_from' => $existing->expires_at,
                    'extended_at' => $now->toISOString(),
                    'via_payment_id' => $paymentId,
                ]),
                'updated_at' => $now,
            ]);

            return DB::table('user_subscriptions')->where('id', $existing->id)->first();
        }

        // Log::info('Creating fresh subscription');
        $startedAt = $now;
        $expiresAt = (clone $now);
        if ($cycle === 'yearly') {
            $expiresAt->addYear();
        } else {
            $expiresAt->addMonth();
        }

        $newSubId = DB::table('user_subscriptions')->insertGetId([
            'user_id' => $paymentRecord->user_id,
            'plan_id' => $paymentRecord->plan_id,
            'billing_cycle' => $cycle,
            'amount_decimal' => $paymentRecord->amount_decimal,
            'currency' => $paymentRecord->currency,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'payment_id' => $paymentId,
            'metadata' => json_encode([
                'created_from_payment_id' => $paymentId,
                'created_at' => $now->toISOString(),
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('user_subscriptions')->where('id', $newSubId)->first();
    }

    /**
     * Sync mailer templates from a plan into the user's mailer_settings.
     */
    protected function syncMailersFromPlan(int $userId, object $plan)
    {
        // Log::info('syncMailersFromPlan called', ['user_id' => $userId, 'plan_id' => $plan->id]);

        $mailerAdminIds = [];
        if (!empty($plan->mailer_settings_admin_ids)) {
            $decoded = json_decode($plan->mailer_settings_admin_ids, true);
            if (is_array($decoded)) {
                $mailerAdminIds = array_values(array_filter($decoded, fn($v) => is_numeric($v)));
            }
        } elseif (!empty($plan->mailer_settings_admin_id)) {
            $mailerAdminIds = [(int)$plan->mailer_settings_admin_id];
        }

        if (empty($mailerAdminIds)) {
            // Log::info('No mailer admin ids to sync', ['plan_id' => $plan->id]);
            return;
        }

        $defaultSet = false;
        foreach ($mailerAdminIds as $adminId) {
            $template = DB::table('mailer_settings_admin')->where('id', $adminId)->first();
            if (! $template) {
                // Log::warning('Template not found during syncMailersFromPlan', ['mailer_settings_admin_id' => $adminId]);
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

            if ($isDefault) {
                DB::table('mailer_settings')->where('user_id', $userId)->update(['is_default' => 0]);
            }

            $existing = DB::table('mailer_settings')
                ->where('user_id', $userId)
                ->where('mailer', $template->mailer)
                ->where('username', $template->username)
                ->first();

            if ($existing) {
                DB::table('mailer_settings')->where('id', $existing->id)->update($payload);
                // Log::info('Updated existing mailer setting during sync', ['mailer_setting_id' => $existing->id, 'is_default' => $isDefault]);
            } else {
                $newId = DB::table('mailer_settings')->insertGetId($payload);
                // Log::info('Created new mailer setting during sync', ['mailer_setting_id' => $newId, 'is_default' => $isDefault]);
            }
        }
    }

    /**
     * Show a single payment record.
     */
    public function showPayment($id)
    {
        // Log::info('showPayment called', ['id' => $id]);
        $payment = DB::table('subscription_payments')->where('id', $id)->first();
        if (! $payment) {
            // Log::warning('Payment not found in showPayment', ['id' => $id]);
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $payment]);
    }

    /**
     * Get current active subscription for a user.
     */
    public function currentSubscription(Request $request)
    {
        // Log::info('currentSubscription called');
        $user = $this->getAuthenticatedUser($request);
        if (! $user) {
            // Log::warning('currentSubscription unauthorized');
            return response()->json(['status'=>'error','message'=>'Unauthorized'], 401);
        }
        $userId = $user->id;
        // Log::info('Fetching active subscription', ['user_id' => $userId]);

        $sub = DB::table('user_subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('expires_at', 'desc')
            ->first();

        if (! $sub) {
            // Log::info('No active subscription found', ['user_id' => $userId]);
            return response()->json(['status' => 'error', 'message' => 'No active subscription'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $sub]);
    }

    /**
     * Resolve authenticated user via personal access token.
     */
    protected function getAuthenticatedUser(Request $request): ?object
    {
        $header = $request->header('Authorization');
        if (! $header || ! preg_match('/Bearer\s(\S+)/', $header, $m)) {
            // Log::debug('Authorization header missing or malformed');
            return null;
        }
        $plain = $m[1];
        $hashed = hash('sha256', $plain);
        $rec = DB::table('personal_access_tokens')
            ->where('token', $hashed)
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        if (! $rec) {
            // Log::debug('Personal access token record not found or mismatched', ['hashed' => $hashed]);
            return null;
        }

        $user = DB::table('users')->where('id', $rec->tokenable_id)->first();
        if (! $user) {
            // Log::warning('Tokenable user missing', ['tokenable_id' => $rec->tokenable_id]);
            return null;
        }

        return $user;
    }

    /**
     * Redact sensitive headers for logs.
     */
    protected function redactHeaders(array $headers): array
    {
        $redacted = [];
        foreach ($headers as $k => $v) {
            if (strtolower($k) === 'authorization') {
                $redacted[$k] = ['REDACTED'];
            } else {
                $redacted[$k] = $v;
            }
        }
        return $redacted;
    }
    /**
 * Return combined payment + subscription history for the authenticated user,
 * including when each subscription was taken, its duration, amount, status, and remaining time.
 */
public function paymentSubscriptionHistory(Request $request)
{
    $user = $this->getAuthenticatedUser($request);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }
    $userId = $user->id;

    // pagination params
    $page = max(1, (int) $request->input('page', 1));
    $perPage = min(100, max(10, (int) $request->input('per_page', 20)));
    $offset = ($page - 1) * $perPage;

    // fetch payments for user (latest first)
    $payments = DB::table('subscription_payments')
        ->where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    // collect associated active/inactive subscriptions
    $history = [];
    foreach ($payments as $payment) {
        // find related subscription(s) for this payment
        $subscriptions = DB::table('user_subscriptions')
            ->where('user_id', $userId)
            ->where(function ($q) use ($payment) {
                $q->where('payment_id', $payment->id)
                  ->orWhere('plan_id', $payment->plan_id);
            })
            ->orderBy('started_at', 'desc')
            ->get();

        // plan info
        $plan = DB::table('subscription_plans')->where('id', $payment->plan_id)->first();

        // build entry even if no subscription exists yet (e.g., pending payment)
        if ($subscriptions->isEmpty()) {
            $history[] = [
                'payment' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount_decimal,
                    'currency' => $payment->currency,
                    'billing_cycle' => $payment->billing_cycle,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                    'gateway_payment_id' => $payment->gateway_payment_id,
                ],
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'title' => $plan->title,
                ] : null,
                'subscription' => null,
                'duration' => null,
                'remaining_days' => null,
                'used_days' => null,
            ];
            continue;
        }

        foreach ($subscriptions as $sub) {
            $started = $sub->started_at ? Carbon::parse($sub->started_at) : null;
            $expires = $sub->expires_at ? Carbon::parse($sub->expires_at) : null;
            $now = Carbon::now();
            $totalDays = $started && $expires ? $started->diffInDays($expires) : null;
            $usedDays = $started ? $started->diffInDays(min($now, $expires ?? $now)) : null;
            $remainingDays = ($expires && $now->lessThan($expires)) ? $now->diffInDays($expires) : 0;

            $history[] = [
                'payment' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount_decimal,
                    'currency' => $payment->currency,
                    'billing_cycle' => $payment->billing_cycle,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                    'gateway_payment_id' => $payment->gateway_payment_id,
                ],
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'title' => $plan->title,
                ] : null,
                'subscription' => [
                    'id' => $sub->id,
                    'status' => $sub->status,
                    'started_at' => $sub->started_at,
                    'expires_at' => $sub->expires_at,
                    'billing_cycle' => $sub->billing_cycle,
                    'amount' => $sub->amount_decimal,
                    'currency' => $sub->currency,
                ],
                'duration_days' => $totalDays,
                'used_days' => $usedDays,
                'remaining_days' => $remainingDays,
            ];
        }
    }

    // total count (for pagination metadata)
    $totalPayments = DB::table('subscription_payments')->where('user_id', $userId)->count();
    $totalPages = (int) ceil($totalPayments / $perPage);

    return response()->json([
        'status' => 'success',
        'meta' => [
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'total_payments' => $totalPayments,
        ],
        'data' => $history,
    ]);
}

}
