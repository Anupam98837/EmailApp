<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class ListManagementController extends Controller
{
    /**
     * Extract authenticated user ID from Bearer token.
     */
    private function getAuthenticatedUserId(Request $request)
    {
        $header = $request->header('Authorization');
        if (! $header || ! preg_match('/Bearer\s(\S+)/', $header, $m)) {
            abort(response()->json([
                'status'  => 'error',
                'message' => 'Token not provided',
            ], 401));
        }

        $record = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $m[1]))
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();

        if (! $record) {
            abort(response()->json([
                'status'  => 'error',
                'message' => 'Invalid token',
            ], 401));
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
     * Helper: Resolve and validate owner's assigned plan and its active subscription.
     * Returns array with 'plan' and 'subscription' or null if invalid.
     */
    protected function getOwnerActivePlan(int $ownerUserId)
    {
        $owner = DB::table('users')->where('id', $ownerUserId)->first();
        if (! $owner || empty($owner->subscription_plan_id)) {
            return null;
        }

        $plan = DB::table('subscription_plans')->where('id', $owner->subscription_plan_id)->first();
        if (! $plan) {
            return null;
        }

        $subscription = $this->getValidSubscription($ownerUserId, $plan->id);
        if (! $subscription) {
            return null;
        }

        return [
            'plan' => $plan,
            'subscription' => $subscription,
        ];
    }

    /**
     * GET /api/lists
     * List all email lists for the authenticated user.
     */
    public function index(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Listing lists for user', ['user_id' => $userId]);

        $lists = DB::table('lists')
            ->where('user_id', $userId)
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Lists retrieved.',
            'data'    => $lists,
        ], 200);
    }

    /**
     * POST /api/lists
     * Create a new list.
     */
    public function store(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('Creating list', ['user_id' => $userId, 'payload' => $request->all()]);

        $v = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        if ($v->fails()) {
            // Log::warning('List creation validation failed', ['errors' => $v->errors()->all()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        try {
            $id = DB::table('lists')->insertGetId([
                'user_id'     => $userId,
                'title'       => $request->title,
                'description' => $request->description,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $list = DB::table('lists')->where('id', $id)->first();
            // Log::info('List created', ['list' => (array)$list]);

            return response()->json([
                'status'  => 'success',
                'message' => 'List created.',
                'data'    => $list,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating list', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not create list.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/lists/{id}/users/import
     * Import subscribers from CSV, enforcing per-list limit from owner's active plan.
     */
    public function importUsers(Request $request, $listId)
    {
        // Log::info('Importing users to list with plan and expiry check', ['list_id' => $listId]);

        // Authenticate owner
        $userId = $this->getAuthenticatedUserId($request);

        // Ensure list exists and belongs to user
        $list = DB::table('lists')->where('id', $listId)->first();
        if (! $list || $list->user_id !== $userId) {
            return response()->json(['status' => 'error', 'message' => 'List not found or not owned.'], 404);
        }

        // Validate owner's active plan/subscription
        $ownerPlanData = $this->getOwnerActivePlan($userId);
        if (! $ownerPlanData) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active/non-expired subscription plan found for user.'
            ], 403);
        }
        $plan = $ownerPlanData['plan'];

        // 2) Validate CSV file
        if (! $request->hasFile('csv_file')) {
            return response()->json(['status' => 'error', 'message' => 'CSV file is required.'], 422);
        }
        $file = $request->file('csv_file');
        if (! $file->isValid()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid file upload.'], 422);
        }

        // 3) Determine per-list subscriber limit
        $listLimitRaw = $plan->list_limit;
        $currentCount = DB::table('list_users')
            ->where('list_id', $listId)
            ->where('is_active', 1)
            ->count();

        if ($listLimitRaw !== null) {
            $listLimit = (int)$listLimitRaw;
            if ($currentCount >= $listLimit) {
                // Log::warning('List subscriber limit already reached before import', ['list_id' => $listId, 'limit' => $listLimit]);
                return response()->json([
                    'status' => 'error',
                    'message' => "Subscriber limit reached for this list (max {$listLimit})."
                ], 422);
            }
        } else {
            $listLimit = null; // unlimited
        }

        // 4) Parse CSV
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return response()->json(['status' => 'error', 'message' => 'Could not open CSV file.'], 500);
        }

        $header = null;
        $added = 0;
        $skipped = [];
        $errors = [];
        $maxAllowed = is_null($listLimit) ? PHP_INT_MAX : ($listLimit - $currentCount);

        while (($row = fgetcsv($handle)) !== false) {
            if (! $header) {
                $header = array_map(fn($v) => strtolower(trim($v)), $row);
                continue;
            }
            if ($added >= $maxAllowed) {
                $errors[] = 'Reached list subscriber limit; further rows skipped.';
                break;
            }

            $data = array_combine($header, $row);
            if (! $data) {
                $skipped[] = ['row' => $row, 'reason' => 'Malformed row'];
                continue;
            }

            $name  = isset($data['name']) ? trim($data['name']) : null;
            $email = isset($data['email']) ? trim($data['email']) : null;
            $phone = isset($data['phone']) ? trim($data['phone']) : null;

            if (! $name || ! $email) {
                $skipped[] = ['row' => $row, 'reason' => 'Missing name or email'];
                continue;
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped[] = ['row' => $row, 'reason' => 'Invalid email'];
                continue;
            }

            $existing = DB::table('list_users')
                ->where('list_id', $listId)
                ->where('email', $email)
                ->first();
            if ($existing) {
                $skipped[] = ['email' => $email, 'reason' => 'Already exists'];
                continue;
            }

            $uuid = (string) Str::uuid();
            try {
                DB::table('list_users')->insert([
                    'list_id'    => $listId,
                    'user_uuid'  => $uuid,
                    'name'       => $name,
                    'email'      => $email,
                    'phone'      => $phone ?: null,
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $added++;
            } catch (Throwable $e) {
                // Log::error('Failed to insert subscriber during import', ['email' => $email, 'exception' => $e->getMessage()]);
                $skipped[] = ['email' => $email, 'reason' => 'DB error'];
            }
        }
        fclose($handle);

        $message = "Import completed. Added {$added} subscriber" . ($added === 1 ? '' : 's') . '.';
        if (!empty($skipped)) {
            $message .= " Skipped " . count($skipped) . " row" . (count($skipped) === 1 ? '' : 's') . '.';
        }

        // Log::info('Import summary', [
        //     'list_id' => $listId,
        //     'added' => $added,
        //     'skipped_count' => count($skipped),
        //     'errors' => $errors,
        // ]);

        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => [
                'added'         => $added,
                'skipped'       => $skipped,
                'errors'        => $errors,
                'current_total' => $currentCount + $added,
                'limit'         => is_null($listLimit) ? 'unlimited' : $listLimit,
            ],
        ], 200);
    }

    /**
     * GET /api/lists/{id}
     * Fetch a single list.
     */
    public function show(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Fetching list', ['user_id' => $userId, 'list_id' => $id]);

        $list = DB::table('lists')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $list) {
            return response()->json([
                'status'  => 'error',
                'message' => 'List not found.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'List retrieved.',
            'data'    => $list,
        ], 200);
    }

    /**
     * PUT /api/lists/{id}
     * Update an existing list.
     */
    public function update(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Updating list', ['user_id' => $userId, 'list_id' => $id, 'payload' => $request->all()]);

        $v = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $exists = DB::table('lists')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'List not found.',
            ], 404);
        }

        try {
            DB::table('lists')
                ->where('id', $id)
                ->update([
                    'title'       => $request->title,
                    'description' => $request->description,
                    'updated_at'  => now(),
                ]);

            $list = DB::table('lists')->where('id', $id)->first();
            // Log::info('List updated', ['list' => (array)$list]);

            return response()->json([
                'status'  => 'success',
                'message' => 'List updated.',
                'data'    => $list,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating list', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not update list.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/lists/{id}/toggle
     * Toggle the active status of a list.
     */
    public function toggle(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Toggling list status', ['user_id' => $userId, 'list_id' => $id]);

        $list = DB::table('lists')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $list) {
            return response()->json([
                'status'  => 'error',
                'message' => 'List not found.',
            ], 404);
        }

        $newStatus = ! (bool) $list->is_active;

        try {
            DB::table('lists')
                ->where('id', $id)
                ->update([
                    'is_active'  => $newStatus,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'status'  => 'success',
                'message' => $newStatus ? 'List activated.' : 'List deactivated.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not toggle list status.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/lists/{id}
     * Permanently delete a list.
     */
    public function destroy(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Deleting list', ['user_id' => $userId, 'list_id' => $id]);

        $deleted = DB::table('lists')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        if ($deleted) {
            return response()->json([
                'status'  => 'success',
                'message' => 'List deleted.',
            ], 200);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'List not found or already deleted.',
        ], 404);
    }

    //
    // —— Subscribers (list_users) CRUD below —— 
    //

    /**
     * POST /api/lists/{id}/users
     * Add a single subscriber, enforcing the owner's plan per-list limit and expiry.
     */
    public function addUser(Request $request, $listId)
    {
        // Log::info('Adding user to list with plan limit and expiry check', ['payload' => $request->all(), 'list_id' => $listId]);

        $userId = $this->getAuthenticatedUserId($request);

        // Ensure list exists and belongs to user
        $list = DB::table('lists')->where('id', $listId)->first();
        if (! $list || $list->user_id !== $userId) {
            return response()->json(['status' => 'error', 'message' => 'List not found or not owned.'], 404);
        }

        // Validate owner has active plan/subscription
        $ownerPlanData = $this->getOwnerActivePlan($userId);
        if (! $ownerPlanData) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active/non-expired subscription plan found for user.'
            ], 403);
        }
        $plan = $ownerPlanData['plan'];

        // Validate input
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        // Enforce per-list subscriber limit
        $listLimitRaw = $plan->list_limit;
        if ($listLimitRaw !== null) {
            $listLimit = (int)$listLimitRaw;
            $currentCount = DB::table('list_users')
                ->where('list_id', $listId)
                ->where('is_active', 1)
                ->count();
            if ($currentCount >= $listLimit) {
                // Log::warning('List subscriber limit reached', ['list_id' => $listId, 'limit' => $listLimit, 'current' => $currentCount]);
                return response()->json([
                    'status' => 'error',
                    'message' => "Subscriber limit reached for this list (max {$listLimit})."
                ], 422);
            }
        }

        // Prevent duplicate
        $existing = DB::table('list_users')
            ->where('list_id', $listId)
            ->where('email', $data['email'])
            ->first();
        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subscriber with that email already exists in this list.'
            ], 422);
        }

        // Insert subscriber
        $uuid = (string) Str::uuid();
        DB::table('list_users')->insert([
            'list_id'    => $listId,
            'user_uuid'  => $uuid,
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log::info('Subscriber successfully added', ['list_id' => $listId, 'email' => $data['email'], 'user_uuid' => $uuid]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Subscriber added.',
            'data'    => [
                'list_id'   => $listId,
                'user_uuid' => $uuid,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
            ],
        ], 201);
    }

    /**
     * GET /api/lists/{id}/users
     * View subscribers of a list.
     */
    public function viewUsers(Request $request, $listId)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Fetching subscribers', ['user_id' => $userId, 'list_id' => $listId]);

        if (! DB::table('lists')->where('id', $listId)->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'List not found.'], 404);
        }

        $users = DB::table('list_users')
            ->where('list_id', $listId)
            ->orderByDesc('is_active')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Subscribers retrieved.',
            'data'    => $users
        ], 200);
    }

    /**
     * PUT /api/lists/{listId}/users/{userUuid}
     * Update a subscriber.
     */
    public function editUser(Request $request, $listId, $userUuid)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Updating subscriber', ['user_id' => $userId, 'list_id' => $listId, 'user_uuid' => $userUuid]);

        if (! DB::table('lists')->where('id', $listId)->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'List not found.'], 404);
        }

        $v = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
        ]);
        if ($v->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors()
            ], 422);
        }

        $exists = DB::table('list_users')
            ->where('user_uuid', $userUuid)
            ->where('list_id', $listId)
            ->exists();

        if (! $exists) {
            return response()->json(['status' => 'error', 'message' => 'Subscriber not found.'], 404);
        }

        try {
            DB::table('list_users')
                ->where('user_uuid', $userUuid)
                ->update([
                    'name'       => $request->name,
                    'phone'      => $request->phone,
                    'email'      => $request->email,
                    'updated_at' => now(),
                ]);
            $sub = DB::table('list_users')->where('user_uuid', $userUuid)->first();
            return response()->json(['status' => 'success', 'message' => 'Subscriber updated.', 'data' => $sub], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not update subscriber.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/lists/{listId}/users/{userUuid}
     * Remove a subscriber.
     */
    public function deleteUser(Request $request, $listId, $userUuid)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Deleting subscriber', ['user_id' => $userId, 'list_id' => $listId, 'user_uuid' => $userUuid]);

        $deleted = DB::table('list_users')
            ->where('user_uuid', $userUuid)
            ->where('list_id', $listId)
            ->delete();

        if ($deleted) {
            return response()->json(['status' => 'success', 'message' => 'Subscriber deleted.'], 200);
        }

        return response()->json(['status' => 'error', 'message' => 'Subscriber not found.'], 404);
    }

    /**
     * PATCH /api/lists/{listId}/users/{userUuid}/toggle
     * Toggle a subscriber's active status with limit check.
     */
    public function toggleUser(Request $request, $listId, $userUuid)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Toggling subscriber status with plan limit and expiry check', [
        //     'user_id' => $userId,
        //     'list_id' => $listId,
        //     'user_uuid' => $userUuid
        // ]);

        // Ensure the list belongs to the authenticated user
        $list = DB::table('lists')
            ->where('id', $listId)
            ->where('user_id', $userId)
            ->first();
        if (! $list) {
            return response()->json(['status' => 'error', 'message' => 'List not found.'], 404);
        }

        // Validate owner has active plan/subscription
        $ownerPlanData = $this->getOwnerActivePlan($userId);
        if (! $ownerPlanData) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active/non-expired subscription plan found for user.'
            ], 403);
        }
        $plan = $ownerPlanData['plan'];

        $user = DB::table('list_users')
            ->where('user_uuid', $userUuid)
            ->where('list_id', $listId)
            ->first();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Subscriber not found.'], 404);
        }

        $isActivating = ! (bool)$user->is_active;

        if ($isActivating) {
            $listLimitRaw = $plan->list_limit;
            if ($listLimitRaw !== null) {
                $listLimit = (int)$listLimitRaw;
                $currentActive = DB::table('list_users')
                    ->where('list_id', $listId)
                    ->where('is_active', 1)
                    ->count();
                if ($currentActive >= $listLimit) {
                    //  Log::warning('Cannot activate subscriber: list limit reached', [
                    //     'list_id' => $listId,
                    //     'limit' => $listLimit,
                    //     'current_active' => $currentActive
                    // ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => "Cannot activate subscriber. Subscriber limit reached for this list (max {$listLimit})."
                    ], 422);
                }
            }
        }

        $new = $isActivating;
        DB::table('list_users')
            ->where('user_uuid', $userUuid)
            ->update(['is_active' => $new, 'updated_at' => now()]);

        return response()->json([
            'status'  => 'success',
            'message' => $new ? 'Subscriber activated.' : 'Subscriber deactivated.',
        ], 200);
    }

    /**
     * Empty the list.
     */
    public function empty(Request $request, $listId)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Empty list request', ['user_id' => $userId, 'list_id' => $listId]);

        // Confirm list ownership
        $list = DB::table('lists')
            ->where('id', $listId)
            ->where('user_id', $userId)
            ->first();

        if (! $list) {
            return response()->json([
                'status'  => 'error',
                'message' => 'List not found.',
            ], 404);
        }

        try {
            $deleted = 0;
            DB::transaction(function () use ($listId, &$deleted) {
                $deleted = DB::table('list_users')
                    ->where('list_id', $listId)
                    ->delete();
            });

            // Log::info('List emptied', [
            //     'list_id'       => $listId,
            //     'deleted_count' => $deleted,
            // ]);

            return response()->json([
                'status'        => 'success',
                'message'       => "Removed {$deleted} subscriber(s) from the list.",
                'deleted_count' => $deleted,
            ], 200);
        } catch (\Exception $e) {
            // Log::error('Failed to empty list', [
            //     'list_id' => $listId,
            //     'error'   => $e->getMessage(),
            // ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Could not empty the list.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
