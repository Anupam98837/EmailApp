<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes/middleware definition: ->middleware('check.role:admin') or ->middleware('check.role:user,admin')
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  mixed ...$roles  // expected 'admin' and/or 'user'
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        Log::info(__METHOD__ . ' - start', ['roles' => $roles]);

        $header = $request->header('Authorization', '');
        if (!$header) {
            Log::warning('Authorization header missing');
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }

        // support "Bearer <token>" and raw token
        if (stripos($header, 'Bearer ') === 0) {
            $token = trim(substr($header, 7));
        } else {
            $token = trim($header);
        }

        if (!$token) {
            Log::warning('Token missing after parsing Authorization header');
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }

        $hashed = hash('sha256', $token);
        Log::info(__METHOD__ . ' - looking up token', ['token_hash' => $hashed]);

        // Build allowed tokenable_type list from roles
        $allowedTypes = [];
        foreach ($roles as $role) {
            $roleLower = strtolower(trim($role));
            if ($roleLower === 'admin') {
                $allowedTypes[] = 'App\\Models\\Admin';
            } elseif ($roleLower === 'user') {
                $allowedTypes[] = 'App\\Models\\User';
            }
        }

        if (empty($allowedTypes)) {
            Log::warning('No valid role provided to middleware', ['roles' => $roles]);
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }

        $record = DB::table('personal_access_tokens')
            ->where('token', $hashed)
            ->whereIn('tokenable_type', $allowedTypes)
            ->first();

        if (!$record) {
            Log::warning('Token not found or role mismatch', ['token_hash' => $hashed, 'allowed_types' => $allowedTypes]);
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }

        // attach identity to request for downstream use
        $request->attributes->set('auth_tokenable_type', $record->tokenable_type);
        $request->attributes->set('auth_tokenable_id', $record->tokenable_id);

        Log::info(__METHOD__ . ' - authorized', [
            'tokenable_type' => $record->tokenable_type,
            'tokenable_id' => $record->tokenable_id,
        ]);

        return $next($request);
    }
}
