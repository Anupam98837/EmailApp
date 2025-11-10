<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /** FQCN used in personal_access_tokens.tokenable_type */
    private const USER_TYPE = 'App\\Models\\User';
    private const ADMIN_TYPE = 'App\\Models\\Admin';

    /**
     * Register a new user and issue a token.
     */
    public function register(Request $request)
    {
        Log::info('register: start', ['ip' => $request->ip()]);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Default status is inactive for self-registration
        $status = $request->input('status', 'inactive');

        $userId = DB::table('users')->insertGetId([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'password'   => Hash::make($data['password']),
            'status'     => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('register: user created', ['user_id' => $userId, 'status' => $status]);

        $plainToken = $this->issueToken($userId);
        Log::info('register: token issued', ['user_id' => $userId]);

        $user = DB::table('users')->where('id', $userId)->first();
        unset($user->password);

        return response()->json([
            'status'       => 'success',
            'message'      => 'User registered',
            'access_token' => $plainToken,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ], 201);
    }

    /**
     * Admin registers a new user
     */
    public function adminRegisterUser(Request $request)
    {
        Log::info('adminRegisterUser: start', ['ip' => $request->ip()]);

        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            Log::warning('adminRegisterUser: unauthorized');
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'status'   => 'sometimes|in:active,inactive',
        ]);

        // Default status is active for admin registration
        $status = $request->input('status', 'active');

        $userId = DB::table('users')->insertGetId([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'password'   => Hash::make($data['password']),
            'status'     => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('adminRegisterUser: user created', [
            'admin_id' => $admin->id,
            'user_id' => $userId,
            'status' => $status
        ]);

        $user = DB::table('users')->where('id', $userId)->first();
        unset($user->password);

        return response()->json([
            'status'  => 'success',
            'message' => 'User registered by admin',
            'user'    => $user,
        ], 201);
    }

    /**
     * Login user and issue a token.
     */
    public function login(Request $request)
    {
        Log::info('login: start', ['ip' => $request->ip()]);

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('login: failed', ['email' => $request->email]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            Log::warning('login: inactive account', ['user_id' => $user->id]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Your account is inactive. Please contact admin to activate your account.',
            ], 403);
        }

        $plainToken = $this->issueToken($user->id);
        Log::info('login: token issued', ['user_id' => $user->id]);

        unset($user->password);

        return response()->json([
            'status'       => 'success',
            'message'      => 'Login successful',
            'access_token' => $plainToken,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ]);
    }

    /**
     * Toggle user status (admin only)
     */
    public function toggleUserStatus(Request $request, $userId)
    {
        Log::info('toggleUserStatus: start', ['ip' => $request->ip(), 'user_id' => $userId]);

        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            Log::warning('toggleUserStatus: unauthorized');
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            Log::warning('toggleUserStatus: user not found', ['user_id' => $userId]);
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';

        DB::table('users')->where('id', $userId)->update([
            'status' => $newStatus,
            'updated_at' => now(),
        ]);

        Log::info('toggleUserStatus: status changed', [
            'admin_id' => $admin->id,
            'user_id' => $userId,
            'new_status' => $newStatus
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User status updated',
            'data'    => [
                'user_id' => $userId,
                'new_status' => $newStatus
            ],
        ]);
    }

    /**
     * Admin updates user information
     */
    // App/Http/Controllers/API/UserController.php

    public function adminUpdateUser(Request $request, $userId)
    {
        Log::info('adminUpdateUser: start', ['ip' => $request->ip(), 'user_id' => $userId]);

        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            Log::warning('adminUpdateUser: unauthorized');
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            Log::warning('adminUpdateUser: user not found', ['user_id' => $userId]);
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        // NOTE: password is optional; only enforced if present.
        $data = $request->validate([
            'name'                  => 'sometimes|required|string|max:255',
            'email'                 => 'sometimes|required|email|unique:users,email,'.$userId,
            'phone'                 => 'sometimes|nullable|string|max:20',
            'status'                => 'sometimes|in:active,inactive',
            'password'              => 'sometimes|nullable|string|min:6|confirmed',
            // <-- expects `password_confirmation` alongside `password`
        ]);

        $update = [
            'updated_at' => now(),
        ];

        if (array_key_exists('name', $data))   $update['name']   = $data['name'];
        if (array_key_exists('email', $data))  $update['email']  = $data['email'];
        if (array_key_exists('phone', $data))  $update['phone']  = $data['phone'];
        if (array_key_exists('status', $data)) $update['status'] = $data['status'];

        // If admin typed a new password, hash and store it.
        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        DB::table('users')->where('id', $userId)->update($update);

        $updated = DB::table('users')->where('id', $userId)->first();
        unset($updated->password);

        Log::info('adminUpdateUser: user updated', [
            'admin_id' => $admin->id,
            'user_id'  => $userId,
            'pwd'      => !empty($data['password']) ? 'updated' : 'unchanged'
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User updated successfully',
            'user'    => $updated,
        ]);
    }


    /**
     * Logout (revoke current token).
     */
    public function logout(Request $request)
    {
        Log::info('logout: start', ['ip' => $request->ip()]);

        $plain = $this->extractToken($request);
        if (!$plain) {
            Log::warning('logout: token missing');
            return response()->json([
                'status'  => 'error',
                'message' => 'Token not provided',
            ], 401);
        }

        $deleted = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::USER_TYPE)
            ->delete();

        Log::info('logout: token revoked', ['deleted' => $deleted]);

        return response()->json([
            'status'  => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Logged out successfully' : 'Invalid token',
        ], $deleted ? 200 : 401);
    }

    /**
     * View authenticated user profile.
     */
    /**
 * View authenticated user profile.
 */
public function profile(Request $request)
{
    Log::info('profile: start', ['ip' => $request->ip()]);

    // 1) Authenticate the user
    $plain = $this->extractToken($request);
    if (! $plain) {
        Log::warning('profile: token missing');
        return response()->json(['status'=>'error','message'=>'Unauthorized'], 401);
    }

    $rec = DB::table('personal_access_tokens')
        ->where('token', hash('sha256', $plain))
        ->where('tokenable_type', self::USER_TYPE)
        ->first();
    if (! $rec) {
        Log::warning('profile: invalid token');
        return response()->json(['status'=>'error','message'=>'Unauthorized'], 401);
    }

    // 2) Fetch user + plan title in one query
    $user = DB::table('users')
        ->leftJoin('subscription_plans', 'users.subscription_plan_id', '=', 'subscription_plans.id')
        ->select([
            'users.id',
            'users.name',
            'users.email',
            'users.phone',
            'users.status',
            'users.photo',
            'users.created_at',
            'users.updated_at',
            'subscription_plans.title as subscription_plan_title',
        ])
        ->where('users.id', $rec->tokenable_id)
        ->first();

    if (! $user) {
        Log::warning('profile: user not found', ['id' => $rec->tokenable_id]);
        return response()->json(['status'=>'error','message'=>'Unauthorized'], 401);
    }

    // 3) Hide password and return
    unset($user->password);

    return response()->json([
        'status' => 'success',
        'user'   => $user,
    ], 200);
}


    /**
     * Update user profile (name, phone).
     */
    public function updateProfile(Request $request)
    {
        Log::info('updateProfile: start', ['ip' => $request->ip()]);
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            Log::warning('updateProfile: unauthorized');
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
        ]);

        $data['updated_at'] = now();
        DB::table('users')->where('id', $user->id)->update($data);

        $updated = DB::table('users')->where('id', $user->id)->first();
        unset($updated->password);

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated',
            'user'    => $updated,
        ]);
    }

    /**
     * Change user password.
     */
    public function updatePassword(Request $request)
    {
        Log::info('updatePassword: start', ['ip' => $request->ip()]);
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            Log::warning('updatePassword: unauthorized');
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            Log::warning('updatePassword: wrong current password', ['user_id' => $user->id]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Current password does not match',
            ], 422);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password'   => Hash::make($data['new_password']),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * GET /api/admin/users
     * Fetch all users with statistics (admin only)
     */
    public function adminGetUsers(Request $request)
{
    // First verify this is an admin request
    $admin = $this->getAuthenticatedAdmin($request);
    if (!$admin) {
        Log::warning('Admin access denied', ['ip' => $request->ip()]);
        return response()->json([
            'status'  => 'error',
            'message' => 'Unauthorized',
        ], 401);
    }

    Log::info('Admin fetching user statistics', ['admin_id' => $admin->id]);

    try {
        $users = DB::table('users')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.status',
                'users.created_at',
                'users.subscription_plan_id',
                'users.subscription_plan_title',
                DB::raw('(SELECT COUNT(*) FROM templates WHERE templates.user_id = users.id) AS template_count'),
                DB::raw('(SELECT COUNT(*) FROM lists WHERE lists.user_id = users.id) AS list_count'),
                DB::raw('(SELECT COUNT(*) FROM campaigns WHERE campaigns.user_id = users.id) AS campaign_count'),
                DB::raw('(SELECT COUNT(*) FROM campaigns WHERE campaigns.user_id = users.id AND campaigns.status = "completed") AS completed_campaigns'),
                DB::raw('(SELECT COUNT(*) FROM list_users 
                          WHERE list_users.list_id IN 
                            (SELECT id FROM lists WHERE lists.user_id = users.id)
                         ) AS total_subscribers'),
                // Subscription status fields
                DB::raw('(SELECT status FROM user_subscriptions 
                          WHERE user_id = users.id 
                          ORDER BY created_at DESC LIMIT 1) AS subscription_status'),
                DB::raw('(SELECT expires_at FROM user_subscriptions 
                          WHERE user_id = users.id 
                          ORDER BY created_at DESC LIMIT 1) AS subscription_expires_at'),
                DB::raw('(SELECT started_at FROM user_subscriptions 
                          WHERE user_id = users.id 
                          ORDER BY created_at DESC LIMIT 1) AS subscription_started_at'),
                DB::raw('(SELECT billing_cycle FROM user_subscriptions 
                          WHERE user_id = users.id 
                          ORDER BY created_at DESC LIMIT 1) AS subscription_billing_cycle'),
            ])
            ->orderBy('users.created_at', 'desc')
            ->get();

        // Process users to add subscription status information
        $now = now();
        $processedUsers = $users->map(function ($user) use ($now) {
            // Convert to array for manipulation
            $userArray = (array) $user;
            
            // Determine subscription status
            if (!$user->subscription_plan_id) {
                $userArray['subscription_info'] = [
                    'has_plan' => false,
                    'status' => 'no_plan',
                    'status_text' => 'No Plan',
                    'status_badge' => 'secondary',
                    'is_expired' => true,
                    'is_active' => false,
                    'remaining_days' => 0,
                    'expires_at' => null,
                    'started_at' => null,
                ];
            } elseif (!$user->subscription_expires_at) {
                $userArray['subscription_info'] = [
                    'has_plan' => true,
                    'status' => 'inactive',
                    'status_text' => 'Inactive',
                    'status_badge' => 'warning',
                    'is_expired' => false,
                    'is_active' => false,
                    'remaining_days' => 0,
                    'expires_at' => null,
                    'started_at' => null,
                ];
            } else {
                $expiresAt = \Carbon\Carbon::parse($user->subscription_expires_at);
                $startedAt = \Carbon\Carbon::parse($user->subscription_started_at);
                $isExpired = $expiresAt->isPast();
                $isActive = $user->subscription_status === 'active' && !$isExpired;
                $remainingDays = $isExpired ? 0 : $now->diffInDays($expiresAt);

                $status = $isActive ? 'active' : ($isExpired ? 'expired' : 'inactive');
                $statusText = $isActive ? 'Active' : ($isExpired ? 'Expired' : 'Inactive');
                $statusBadge = $isActive ? 'success' : ($isExpired ? 'danger' : 'warning');

                $userArray['subscription_info'] = [
                    'has_plan' => true,
                    'status' => $status,
                    'status_text' => $statusText,
                    'status_badge' => $statusBadge,
                    'is_expired' => $isExpired,
                    'is_active' => $isActive,
                    'remaining_days' => $remainingDays,
                    'expires_at' => $user->subscription_expires_at,
                    'started_at' => $user->subscription_started_at,
                    'billing_cycle' => $user->subscription_billing_cycle,
                    'plan_title' => $user->subscription_plan_title,
                ];
            }

            return (object) $userArray;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users' => $users->count(),
                'users' => $processedUsers,
            ],
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to fetch user statistics', [
            'admin_id' => $admin->id,
            'error' => $e->getMessage(),
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch user data',
        ], 500);
    }
}

    /**
     * GET /api/admin/users/{id}
     * Fetch detailed information about a specific user (admin only)
     */
    public function adminGetUserDetail(Request $request, $userId)
    {
        // First verify this is an admin request
        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            Log::warning('Admin access denied', ['ip' => $request->ip()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            // Get user basic info
            $user = DB::table('users')
                ->select([
                    'id',
                    'name',
                    'email',
                    'phone',
                    'status',
                    'created_at',
                    'updated_at',
                    'subscription_plan_title'
                ])
                ->where('id', $userId)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Get user statistics
            $statistics = [
                'templates' => DB::table('templates')->where('user_id', $userId)->count(),
                'lists' => DB::table('lists')->where('user_id', $userId)->count(),
                'campaigns' => [
                    'total' => DB::table('campaigns')->where('user_id', $userId)->count(),
                    'completed' => DB::table('campaigns')->where('user_id', $userId)->where('status', 'completed')->count(),
                ],
                'total_subscribers' => DB::table('list_users')
                    ->whereIn('list_id', function($query) use ($userId) {
                        $query->select('id')
                            ->from('lists')
                            ->where('user_id', $userId);
                    })
                    ->count(),
            ];

            // Get recent templates
            $templates = DB::table('templates')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get recent lists
            $lists = DB::table('lists')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get recent campaigns
            $campaigns = DB::table('campaigns')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
                    'statistics' => $statistics,
                    'recent_templates' => $templates,
                    'recent_lists' => $lists,
                    'recent_campaigns' => $campaigns,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch user detail', [
                'admin_id' => $admin->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user details',
            ], 500);
        }
    }

    /* =========================================================
       Helpers
       ========================================================= */

    /**
     * Issue a new personal access token (stores only the hash).
     */
    protected function issueToken(int $userId): string
    {
        $plain = bin2hex(random_bytes(40));

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => self::USER_TYPE,
            'tokenable_id'   => $userId,
            'name'           => 'auth_token',
            'token'          => hash('sha256', $plain),
            'abilities'      => json_encode(['*']),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $plain;
    }

    /**
     * Extract plain Bearer token from request.
     */
    protected function extractToken(Request $request): ?string
    {
        $h = $request->header('Authorization');
        if (!$h || !preg_match('/Bearer\s(\S+)/', $h, $m)) {
            return null;
        }
        return $m[1];
    }

    /**
     * Resolve authenticated user object or null.
     */
    protected function getAuthenticatedUser(Request $request): ?object
    {
        $plain = $this->extractToken($request);
        if (!$plain) return null;

        $rec = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        if (!$rec) return null;

        return DB::table('users')->where('id', $rec->tokenable_id)->first();
    }

    /**
     * Helper to get authenticated admin from token
     */
    protected function getAuthenticatedAdmin(Request $request): ?object
    {
        $plain = $this->extractToken($request);
        if (!$plain) return null;

        $rec = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::ADMIN_TYPE)
            ->first();

        if (!$rec) return null;

        return DB::table('admins')->where('id', $rec->tokenable_id)->first();
    }
}