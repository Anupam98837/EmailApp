<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ThemeController extends Controller
{
    /**
     * Extract authenticated user ID from Bearer token.
     */
    private function getAuthenticatedUserId(Request $request): int
    {
        $header = $request->header('Authorization');
        if (! $header || ! preg_match('/Bearer\s(\S+)/', $header, $m)) {
            abort(response()->json(['status'=>'error','message'=>'Token not provided'], 401));
        }
        $record = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $m[1]))
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();
        if (! $record) {
            abort(response()->json(['status'=>'error','message'=>'Invalid token'], 401));
        }
        return (int) $record->tokenable_id;
    }

    /**
     * GET /api/theme
     * Retrieve current user's theme.
     */
    public function getTheme(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('Fetching theme for user', ['user_id' => $userId]);

        $theme = DB::table('user_themes')
            ->where('user_id', $userId)
            ->first();

        return response()->json([
            'status'  => 'success',
            'message' => $theme
                ? 'Theme retrieved.'
                : 'No theme set; using default.',
            'data'    => $theme,
        ], 200);
    }

    /**
     * POST /api/theme
     * Select or update the user's theme.
     * Payload: { theme_name: string }
     */
    public function selectTheme(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('Selecting theme for user', ['user_id' => $userId, 'payload' => $request->all()]);

        $v = Validator::make($request->all(), [
            'theme_name' => 'required|string|max:255',
        ]);
        if ($v->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        try {
            $exists = DB::table('user_themes')->where('user_id', $userId)->exists();

            if ($exists) {
                DB::table('user_themes')
                    ->where('user_id', $userId)
                    ->update([
                        'theme_name' => $request->theme_name,
                        'updated_at' => now(),
                    ]);
                $message = 'Theme updated.';
            } else {
                DB::table('user_themes')->insert([
                    'user_id'    => $userId,
                    'theme_name' => $request->theme_name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $message = 'Theme set.';
            }

            $theme = DB::table('user_themes')->where('user_id', $userId)->first();

            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'data'    => $theme,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error selecting theme', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not set theme.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
