<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnsubscribeController extends Controller
{
    public function __construct()
    {
        // Apply signed URL middleware to protect the link
        $this->middleware('signed')->only('unsubscribe');
    }

    /**
     * GET /unsubscribe/{user_uuid}
     */
    public function unsubscribe(Request $request, $user_uuid)
    {
        $user = DB::table('list_users')
            ->where('user_uuid', $user_uuid)
            ->first();

        if (! $user) {
            return response()->make(<<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Unsubscribe Failed</title></head>
<body style="font-family:system-ui,-apple-system,BlinkMacSystemFont,sans-serif;max-width:600px;margin:40px auto;padding:20px;">
  <h2 style="color:#333;">Unsubscribe Failed</h2>
  <p style="font-size:16px;">Subscriber not found.</p>
</body>
</html>
HTML
            , 404);
        }

        try {
            DB::table('list_users')
                ->where('user_uuid', $user_uuid)
                ->update([
                    'is_active'  => 0,
                    'updated_at' => now(),
                ]);

            Log::info('Subscriber unsubscribed via link', [
                'user_uuid' => $user_uuid,
                'email'     => $user->email,
            ]);

            return response()->make(<<<HTML
<!DOCTYPE html>
<html>
<head><title>Unsubscribed</title></head>
<body style="font-family:system-ui,-apple-system,BlinkMacSystemFont,sans-serif;max-width:600px;margin:40px auto;padding:20px;">
  <h2 style="color:#333;">You have been unsubscribed</h2>
  <p style="font-size:16px;">{$user->email} is now inactive and will no longer receive emails.</p>
</body>
</html>
HTML
            , 200);
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe subscriber', [
                'user_uuid' => $user_uuid,
                'error'     => $e->getMessage(),
            ]);

            return response()->make(<<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Unsubscribe Failed</title></head>
<body style="font-family:system-ui,-apple-system,BlinkMacSystemFont,sans-serif;max-width:600px;margin:40px auto;padding:20px;">
  <h2 style="color:#333;">Unsubscribe Failed</h2>
  <p style="font-size:16px;">Could not unsubscribe. Please try again later.</p>
</body>
</html>
HTML
            , 500);
        }
    }
}
