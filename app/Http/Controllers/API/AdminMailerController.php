<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminMailerController extends Controller
{
    /**
     * Validation rules for admin mailer settings.
     */
    private function rules(): array
    {
        return [
            'mailer' => 'required|string',
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'required|string',
            'password' => 'required|string',
            'encryption' => 'nullable|string',
            'from_address' => 'required|email',
            'from_name' => 'required|string',
        ];
    }

    /**
     * Build payload for insert/update.
     */
    private function buildPayload(Request $request, bool $forUpdate = false): array
    {
        $base = [
            'mailer' => $request->input('mailer'),
            'host' => $request->input('host'),
            'port' => $request->input('port'),
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'encryption' => $request->input('encryption'),
            'from_address' => $request->input('from_address'),
            'from_name' => $request->input('from_name'),
        ];

        if ($forUpdate) {
            $base['updated_at'] = now();
        } else {
            $base['status'] = 'active';
            $base['created_at'] = now();
            $base['updated_at'] = now();
        }

        return $base;
    }

    /**
     * GET /api/admin/mailer
     */
    public function index()
    {
        // Log::info('Listing admin mailer settings');
        $list = DB::table('mailer_settings_admin')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Admin mailer settings retrieved.',
            'data' => $list,
        ], 200);
    }

    /**
     * POST /api/admin/mailer
     */
    public function store(Request $request)
    {
        // Log::info('Creating admin mailer setting', ['payload' => $request->all()]);

        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $v->errors(),
            ], 422);
        }

        $payload = $this->buildPayload($request, false);

        try {
            $id = DB::table('mailer_settings_admin')->insertGetId($payload);
            $setting = DB::table('mailer_settings_admin')->where('id', $id)->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Admin mailer setting created.',
                'data' => $setting,
            ], 201);
        } catch (Throwable $e) {
            // Log::error('Error creating admin mailer setting', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create setting.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/admin/mailer/{id}
     */
    public function show($id)
    {
        $setting = DB::table('mailer_settings_admin')->where('id', $id)->first();
        if (! $setting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin mailer setting not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Admin mailer setting retrieved.',
            'data' => $setting,
        ], 200);
    }

    /**
     * PUT /api/admin/mailer/{id}
     */
    public function update(Request $request, $id)
    {
        if (! DB::table('mailer_settings_admin')->where('id', $id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin mailer setting not found.',
            ], 404);
        }

        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $v->errors(),
            ], 422);
        }

        $payload = $this->buildPayload($request, true);

        try {
            DB::table('mailer_settings_admin')->where('id', $id)->update($payload);
            $setting = DB::table('mailer_settings_admin')->where('id', $id)->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Admin mailer setting updated.',
                'data' => $setting,
            ], 200);
        } catch (Throwable $e) {
            // Log::error('Error updating admin mailer setting', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Could not update setting.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/mailer/{id}
     */
    public function destroy($id)
    {
        $deleted = DB::table('mailer_settings_admin')->where('id', $id)->delete();

        if ($deleted) {
            return response()->json([
                'status' => 'success',
                'message' => 'Admin mailer setting deleted.',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Admin mailer setting not found.',
        ], 404);
    }

    /**
     * PUT /api/admin/mailer/{id}/status
     * Toggle active/inactive status
     */
    public function toggleStatus($id)
    {
        $setting = DB::table('mailer_settings_admin')->where('id', $id)->first();
        if (! $setting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin mailer setting not found.',
            ], 404);
        }

        $newStatus = $setting->status === 'active' ? 'inactive' : 'active';

        try {
            DB::table('mailer_settings_admin')->where('id', $id)->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Status updated to {$newStatus}.",
                'data' => ['id' => $id, 'status' => $newStatus],
            ], 200);
        } catch (Throwable $e) {
            // Log::error('Error toggling status', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Could not update status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/admin/mailer/assign-plan
     * Assign subscription plan to user and push related mailers to user.
     * Expects: user_id, plan_id
     */
    public function assignPlanToUser(Request $request)
    {
        $v = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'plan_id' => 'required|integer|exists:subscription_plans,id',
        ]);

        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $v->errors(),
            ], 422);
        }

        $userId = $request->input('user_id');
        $planId = $request->input('plan_id');

        DB::beginTransaction();
        try {
            // Fetch plan
            $plan = DB::table('subscription_plans')->where('id', $planId)->first();
            if (! $plan) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subscription plan not found.',
                ], 404);
            }

            // Attach plan to user (assumes users table has subscription_plan_id)
            DB::table('users')->where('id', $userId)->update([
                'subscription_plan_id' => $planId,
                'updated_at' => now(),
            ]);

            // If plan has associated mailer settings, push them to user_mailers
            $mailerIds = [];
            if (!empty($plan->mailer_settings_admin_ids)) {
                $decoded = json_decode($plan->mailer_settings_admin_ids, true);
                if (is_array($decoded)) {
                    $mailerIds = array_filter($decoded, fn($v) => is_numeric($v));
                }
            }

            foreach ($mailerIds as $mailerId) {
                // Insert if not exists (assumes user_mailers with user_id + mailer_settings_admin_id)
                $exists = DB::table('user_mailers')
                    ->where('user_id', $userId)
                    ->where('mailer_settings_admin_id', $mailerId)
                    ->exists();

                if (! $exists) {
                    DB::table('user_mailers')->insert([
                        'user_id' => $userId,
                        'mailer_settings_admin_id' => $mailerId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Plan assigned and mailers synced to user.',
                'data' => [
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'mailer_ids' => $mailerIds,
                ],
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            // Log::error('Error assigning plan to user', ['exception' => $e->getMessage(), 'payload' => $request->all()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Could not assign plan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
