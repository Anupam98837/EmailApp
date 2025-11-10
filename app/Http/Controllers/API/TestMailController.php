<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\MailTesting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TestMailController extends Controller
{
    /**
     * Extract authenticated user ID from Bearer token.
     */
    private function getAuthenticatedUserId(Request $request): int
    {
        Log::info('Step 1: Extracting Bearer token from Authorization header');
        $header = $request->header('Authorization');
        if (! $header || ! preg_match('/Bearer\s(\S+)/', $header, $m)) {
            Log::warning('Step 1: Authorization header missing or malformed');
            abort(response()->json(['status' => 'error','message' => 'Token not provided'], 401));
        }

        Log::info('Step 1: Token found, looking up personal_access_tokens');
        $record = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $m[1]))
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();

        if (! $record) {
            Log::warning('Step 1: No matching personal_access_token record');
            abort(response()->json(['status' => 'error','message' => 'Invalid token'], 401));
        }

        Log::info('Step 1: Authenticated user_id = '.$record->tokenable_id);
        return (int)$record->tokenable_id;
    }

    /**
     * Normalize both camelCase and snake_case inputs into the snake_case keys
     * our validator and mailer logic expect.
     */
    private function normalizeKeys(Request $request): void
    {
        $request->merge([
            // target => first choice, fallback
            'to'            => $request->input('to',            $request->input('to_address')),
            'from_address'  => $request->input('fromAddress',   $request->input('from_address')),
            'from_name'     => $request->input('fromName',      $request->input('from_name')),
            'reply_to'      => $request->input('replyTo',       $request->input('reply_to')),
            'subject'       => $request->input('subject',       $request->input('subject')),
            'html'          => $request->input('html',          $request->input('body_html')),
            'text'          => $request->input('text',          $request->input('body_text')),
        ]);
    }

    /**
     * POST /api/mailer/test
     */
    public function send(Request $request)
    {
        Log::info('--- TestMailController::send START ---');

        // Step 1: authenticate
        $userId = $this->getAuthenticatedUserId($request);

        // Step 2: normalize keys so validation sees the right field names
        $this->normalizeKeys($request);

        // Step 3: log the normalized payload
        Log::info('Step 2: Normalized payload for test send', [
            'user_id' => $userId,
            'payload' => $request->all(),
        ]);

        // Step 4: validate
        Log::info('Step 3: Validating input fields');
        $v = Validator::make($request->all(), [
            'to'           => 'required|email',
            'from_address' => 'required|email',
            'from_name'    => 'required|string|max:255',
            'reply_to'     => 'nullable|email',
            'subject'      => 'required|string|max:255',
            'html'         => 'required|string',
            'text'         => 'nullable|string',
        ]);

        if ($v->fails()) {
            Log::warning('Step 3: Validation failed', ['errors' => $v->errors()->all()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }
        Log::info('Step 3: Validation passed');

        // Step 5: extract
        $to          = $request->input('to');
        $fromAddress = $request->input('from_address');
        $fromName    = $request->input('from_name');
        $replyTo     = $request->input('reply_to');
        $subject     = $request->input('subject');
        $html        = $request->input('html');
        $text        = $request->input('text');
        Log::info('Step 4: Inputs extracted', compact('to','fromAddress','fromName','replyTo','subject'));

        // Step 6: load/override mailer settings
        $defaultFrom   = config('mail.from.address');
        $mailerSetting = null;

        if ($fromAddress !== $defaultFrom) {
            Log::info("Step 5: from_address '{$fromAddress}' != default '{$defaultFrom}', querying settings");
            $mailerSetting = DB::table('mailer_settings')
                ->where('user_id', $userId)
                ->where('from_address', $fromAddress)
                ->first();

            if (! $mailerSetting) {
                Log::error('Step 5: No matching mailer_settings record for from_address');
                return response()->json([
                    'status'  => 'error',
                    'message' => 'from_address not found in your mailer settings.',
                ], 404);
            }

            Log::info('Step 5: mailer_settings loaded', [
                'id'         => $mailerSetting->id,
                'mailer'     => $mailerSetting->mailer,
                'host'       => $mailerSetting->host,
                'port'       => $mailerSetting->port,
                'encryption' => $mailerSetting->encryption,
            ]);

            Log::info('Step 6: Overriding mail.smtp config');
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host',       $mailerSetting->host);
            Config::set('mail.mailers.smtp.port',       $mailerSetting->port);
            Config::set('mail.mailers.smtp.encryption', $mailerSetting->encryption);
            Config::set('mail.mailers.smtp.username',   $mailerSetting->username);
            Config::set('mail.mailers.smtp.password',   $mailerSetting->password);
        } else {
            Log::info('Step 5: Using env-default mailer (no override)');
        }

        $testUuid = (string) Str::uuid();
        Log::info("Step 7: Generated test_uuid = {$testUuid}");

        try {
            // Step 7: build mailable
            Log::info('Step 8: Instantiating MailTesting mailable');
            $mailable = new MailTesting([
                'from_address' => $fromAddress,
                'from_name'    => $fromName,
                'reply_to'     => $replyTo,
                'subject'      => $subject,
                'html'         => $html,
                'text'         => $text,
            ]);

            // Step 8: dispatch
            Log::info("Step 9: Sending test mail to {$to}");
            Mail::to($to)->send($mailable);

            Log::info('Step 10: Mail sent successfully', [
                'test_uuid'    => $testUuid,
                'to'           => $to,
                'from_address' => $fromAddress,
            ]);
            Log::info('--- TestMailController::send END (success) ---');

            return response()->json([
                'status'  => 'success',
                'message' => 'Test email dispatched.',
                'data'    => [
                    'test_uuid'    => $testUuid,
                    'to'           => $to,
                    'from_address' => $fromAddress,
                    'from_name'    => $fromName,
                    'subject'      => $subject,
                    'mailer_used'  => $mailerSetting->mailer ?? 'smtp (env)',
                    'queued'       => false,
                ],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Step 11: Mail send failed', [
                'test_uuid' => $testUuid,
                'error'     => $e->getMessage(),
            ]);
            Log::info('--- TestMailController::send END (error) ---');

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send test email.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
