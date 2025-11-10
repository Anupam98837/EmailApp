<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    /**
     * GET /api/track/open/{campaign_uuid}/{subscriber_id}
     * Record a single open event and return a 1×1 transparent GIF.
     */
    public function open(Request $request, $campaign_uuid, $subscriber_id)
    {
        // Validate input parameters
        $validator = Validator::make([
            'campaign_uuid' => $campaign_uuid,
            'subscriber_id' => $subscriber_id
        ], [
            'campaign_uuid' => 'required|uuid',
            'subscriber_id' => 'required|integer|exists:list_users,id'
        ]);

        if ($validator->fails()) {
            Log::warning('Invalid open tracking request', [
                'errors' => $validator->errors(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            abort(400, 'Invalid tracking parameters');
        }

        // Check if campaign exists
        $campaignExists = DB::table('campaigns')
            ->where('campaign_uuid', $campaign_uuid)
            ->exists();

        if (!$campaignExists) {
            Log::warning('Campaign not found', [
                'campaign_uuid' => $campaign_uuid,
                'ip' => $request->ip()
            ]);
            // Still return the pixel to prevent client retries
        }

        // Prepare log context
        $logContext = [
            'campaign_uuid' => $campaign_uuid,
            'subscriber_id' => $subscriber_id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer')
        ];

        Log::channel('tracking')->info('Open tracking request received', $logContext);

        try {
            // Check for existing open event
            $existingOpen = DB::table('campaign_events')
                ->where('campaign_uuid', $campaign_uuid)
                ->where('subscriber_id', $subscriber_id)
                ->where('type', 'open')
                ->exists();

            if (!$existingOpen) {
                // Record new open event
                DB::table('campaign_events')->insert([
                    'campaign_uuid' => $campaign_uuid,
                    'subscriber_id' => $subscriber_id,
                    'type' => 'open',
                    'ip_address' => $request->ip(),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 1000),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update campaign statistics if campaign exists
                if ($campaignExists) {
                    DB::table('campaigns')
                        ->where('campaign_uuid', $campaign_uuid)
                        ->increment('open_count');
                }

                Log::channel('tracking')->info('Open event recorded', $logContext);
            }
        } catch (\Exception $e) {
            Log::channel('tracking')->error('Failed to record open event', [
                'error' => $e->getMessage(),
                'context' => $logContext
            ]);
        }

        // Return 1×1 transparent GIF
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');

        return response($gif, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * GET /api/track/click/{campaign_uuid}/{subscriber_id}
     * Record a click, ensure an open, then redirect.
     */
    public function click(Request $request, $campaign_uuid, $subscriber_id)
    {
        // Validate input parameters
        $validator = Validator::make([
            'campaign_uuid' => $campaign_uuid,
            'subscriber_id' => $subscriber_id,
            'url' => $request->query('url')
        ], [
            'campaign_uuid' => 'required|uuid',
            'subscriber_id' => 'required|integer|exists:list_users,id',
            'url' => 'required|url'
        ]);

        if ($validator->fails()) {
            Log::warning('Invalid click tracking request', [
                'errors' => $validator->errors(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            abort(400, 'Invalid tracking parameters');
        }

        $targetUrl = $request->query('url');
        $logContext = [
            'campaign_uuid' => $campaign_uuid,
            'subscriber_id' => $subscriber_id,
            'target_url' => $targetUrl,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ];

        Log::channel('tracking')->info('Click tracking request received', $logContext);

        try {
            // Check if campaign exists
            $campaignExists = DB::table('campaigns')
                ->where('campaign_uuid', $campaign_uuid)
                ->exists();

            // Record the click event
            DB::table('campaign_events')->insert([
                'campaign_uuid' => $campaign_uuid,
                'subscriber_id' => $subscriber_id,
                'type' => 'click',
                'url' => $targetUrl,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update campaign statistics if campaign exists
            if ($campaignExists) {
                DB::table('campaigns')
                    ->where('campaign_uuid', $campaign_uuid)
                    ->increment('click_count');
            }

            // Ensure an open event exists
            $openExists = DB::table('campaign_events')
                ->where('campaign_uuid', $campaign_uuid)
                ->where('subscriber_id', $subscriber_id)
                ->where('type', 'open')
                ->exists();

            if (!$openExists) {
                DB::table('campaign_events')->insert([
                    'campaign_uuid' => $campaign_uuid,
                    'subscriber_id' => $subscriber_id,
                    'type' => 'open',
                    'ip_address' => $request->ip(),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 1000),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($campaignExists) {
                    DB::table('campaigns')
                        ->where('campaign_uuid', $campaign_uuid)
                        ->increment('open_count');
                }

                Log::channel('tracking')->info('Auto-open recorded with click', $logContext);
            }

            Log::channel('tracking')->info('Click event recorded', $logContext);

        } catch (\Exception $e) {
            Log::channel('tracking')->error('Failed to record click event', [
                'error' => $e->getMessage(),
                'context' => $logContext
            ]);
        }

        return redirect()->away($targetUrl);
    }
    /**
     * GET /track/unsubscribe/{campaign_uuid}/{subscriber_id}
     * Record an unsubscribe event, deactivate the subscriber, and show an HTML confirmation.
     */
    public function unsubscribe(Request $request, $campaign_uuid, $subscriber_id)
    {
        // Validate input parameters
        $validator = Validator::make(
            ['campaign_uuid' => $campaign_uuid, 'subscriber_id' => $subscriber_id],
            [
                'campaign_uuid' => 'required|uuid',
                'subscriber_id' => 'required|integer|exists:list_users,id'
            ]
        );

        if ($validator->fails()) {
            Log::warning('Invalid unsubscribe tracking request', [
                'errors'     => $validator->errors()->all(),
                'campaign'   => $campaign_uuid,
                'subscriber' => $subscriber_id,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            abort(400, 'Invalid tracking parameters');
        }

        $logContext = [
            'campaign_uuid' => $campaign_uuid,
            'subscriber_id' => $subscriber_id,
            'ip'            => $request->ip(),
            'user_agent'    => $request->userAgent(),
        ];
        Log::channel('tracking')->info('Unsubscribe tracking request received', $logContext);

        try {
            // Deactivate the subscriber
            DB::table('list_users')
                ->where('id', $subscriber_id)
                ->update([
                    'is_active'  => 0,
                    'updated_at' => now(),
                ]);

            // Record unsubscribe event
            DB::table('campaign_events')->insert([
                'campaign_uuid' => $campaign_uuid,
                'subscriber_id' => $subscriber_id,
                'type'          => 'unsubscribe',
                'ip_address'    => $request->ip(),
                'user_agent'    => substr($request->userAgent() ?? '', 0, 1000),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            Log::channel('tracking')->info('Unsubscribe event recorded', $logContext);
        } catch (\Exception $e) {
            Log::channel('tracking')->error('Failed to record unsubscribe event', [
                'error'   => $e->getMessage(),
                'context' => $logContext,
            ]);
        }

        // Return HTML confirmation
        $html = <<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="UTF-8"/>
            <meta name="viewport" content="width=device-width,initial-scale=1"/>
            <title>Unsubscribed</title>
            <!-- Font Awesome -->
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
            <style>
                html, body {
                margin: 0; padding: 0;
                width: 100%; height: 100%;
                display: flex; align-items: center; justify-content: center;
                background: #f0f4f8;
                animation: fadeIn 0.5s ease-out;
                }
                .card {
                background: #fff;
                border-radius: 1rem;
                box-shadow: 0 8px 24px rgba(0,0,0,0.1);
                padding: 2rem;
                text-align: center;
                max-width: 360px;
                width: 90%;
                animation: pop 0.6s ease-out;
                }
                .icon {
                font-size: 4rem;
                color: #4caf50;
                margin-bottom: 1rem;
                animation: pop 0.6s ease-out;
                }
                h1 {
                margin: 0.5rem 0;
                font-family: sans-serif;
                color: #333;
                }
                p {
                color: #666;
                margin-bottom: 1.5rem;
                font-family: sans-serif;
                }
                .btn-home {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                background: #4caf50;
                color: #fff;
                border: none;
                border-radius: 0.5rem;
                font-size: 1rem;
                cursor: pointer;
                font-family: sans-serif;
                transition: background 0.3s;
                }
                .btn-home:hover {
                background: #43a047;
                }

                @keyframes pop {
                0% { transform: scale(0.5); opacity: 0; }
                60% { transform: scale(1.1); opacity: 1; }
                100% { transform: scale(1); }
                }
                @keyframes fadeIn {
                from { opacity: 0; }
                to   { opacity: 1; }
                }
            </style>
            </head>
            <body>
            <div class="card">
                <i class="fa-solid fa-circle-check icon"></i>
                <h1>Unsubscribed!</h1>
                <p>You have been unsubscribed successfully.</p>
                <button class="btn-home" onclick="window.location.href='/'">
                <i class="fa-solid fa-home me-1"></i>Go to Home
                </button>
            </div>
            </body>
            </html>
            HTML;


        return response($html, 200)
            ->header('Content-Type', 'text/html');
    }

}