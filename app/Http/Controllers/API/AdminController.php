<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /** FQCN used in personal_access_tokens.tokenable_type */
    private const ADMIN_TYPE = 'App\\Models\\Admin';

    /**
     * Admin Login
     */
    public function login(Request $request)
    {
        // Log::info('[Admin Login] Start', ['ip' => $request->ip()]);

        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Log::info('[Admin Login] Validation passed', ['email' => $validated['email']]);

        $admin = DB::table('admins')->where('email', $validated['email'])->first();

        if (! $admin) {
            // Log::warning('[Admin Login] Admin not found', ['email' => $validated['email']]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (! Hash::check($validated['password'], $admin->password)) {
            // Log::warning('[Admin Login] Password mismatch', ['admin_id' => $admin->id]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        $plainToken = $this->issueToken($admin->id);
        // Log::info('[Admin Login] Token issued', ['admin_id' => $admin->id]);

        unset($admin->password);

        // Log::info('[Admin Login] Success', ['admin_id' => $admin->id]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'Login successful',
            'access_token' => $plainToken,
            'token_type'   => 'Bearer',
            'admin'        => $admin,
        ]);
    }

    /**
     * Admin Logout
     */
    public function logout(Request $request)
    {
        // Log::info('[Admin Logout] Start', ['ip' => $request->ip()]);

        $plain = $this->extractToken($request);
        if (! $plain) {
            // Log::warning('[Admin Logout] Missing token');
            return response()->json([
                'status'  => 'error',
                'message' => 'Token not provided',
            ], 401);
        }

        // Log::info('[Admin Logout] Token extracted');

        $deleted = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::ADMIN_TYPE)
            ->delete();

        // Log::info('[Admin Logout] Token deleted status', ['deleted' => $deleted]);

        return response()->json([
            'status'  => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Logged out successfully' : 'Invalid token',
        ], $deleted ? 200 : 401);
    }

    /**
     * Get authenticated admin profile.
     */
    public function profile(Request $request)
    {
        // Log::info('[Admin Profile] Start', ['ip' => $request->ip()]);

        $admin = $this->getAuthenticatedAdmin($request);
        if (! $admin) {
            Log::warning('[Admin Profile] Unauthorized access');
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        unset($admin->password);

        // Log::info('[Admin Profile] Profile fetched', ['admin_id' => $admin->id]);

        return response()->json([
            'status' => 'success',
            'admin'  => $admin,
        ]);
    }

    /* ========== Helpers ========== */

    /**
     * Issue a personal access token
     */
    protected function issueToken(int $adminId): string
    {
        $plain = bin2hex(random_bytes(40));

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => self::ADMIN_TYPE,
            'tokenable_id'   => $adminId,
            'name'           => 'admin_token',
            'token'          => hash('sha256', $plain),
            'abilities'      => json_encode(['*']),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Log::info('[Issue Token] Token stored', ['admin_id' => $adminId]);

        return $plain;
    }

    /**
     * Extract plain token from request
     */
    protected function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (! $header || ! preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            // Log::warning('[Extract Token] Bearer token not found');
            return null;
        }
        // Log::info('[Extract Token] Token extracted successfully');
        return $matches[1];
    }

    /**
     * Get authenticated admin object
     */
    protected function getAuthenticatedAdmin(Request $request): ?object
    {
        $plain = $this->extractToken($request);
        if (! $plain) return null;

        $rec = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::ADMIN_TYPE)
            ->first();

        if (! $rec) {
            // Log::warning('[Get Authenticated Admin] Token not matched');
            return null;
        }

        $admin = DB::table('admins')->where('id', $rec->tokenable_id)->first();

        if ($admin) {
            // Log::info('[Get Authenticated Admin] Admin fetched', ['admin_id' => $admin->id]);
        }

        return $admin;
    }
}
