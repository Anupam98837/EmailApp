<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $campaign;
    public $subscriber;

    public function __construct($campaign, $subscriber)
    {
        $this->campaign   = $campaign;
        $this->subscriber = $subscriber;

        Log::info('CampaignMail: __construct called', [
            'campaign_id'   => $this->campaign->id,
            'subscriber_id' => $this->subscriber->id,
        ]);

        $this->configureMailer();
    }

    protected function configureMailer(): void
    {
        Log::info('CampaignMail: configureMailer start', [
            'campaign_id' => $this->campaign->id,
        ]);

        try {
            $mailer = DB::table('mailer_settings')
                ->where('from_address', $this->campaign->from_address)
                ->first();

            if (! $mailer) {
                Log::warning('CampaignMail: no matching mailer found, using fallback', [
                    'from_address' => $this->campaign->from_address,
                ]);
                return;
            }

            Config::set('mail.mailers.smtp', [
                'transport'  => 'smtp',
                'host'       => $mailer->host,
                'port'       => $mailer->port,
                'encryption' => $mailer->encryption,
                'username'   => $mailer->username,
                'password'   => $mailer->password,
                'timeout'    => null,
                'auth_mode'  => null,
            ]);

            Config::set('mail.from.address', $mailer->from_address);
            Config::set('mail.from.name',    $mailer->from_name);

            Log::info('CampaignMail: mailer config applied', [
                'mailer' => $mailer->from_address,
                'host'   => $mailer->host,
            ]);
        } catch (\Exception $e) {
            Log::error('CampaignMail: configureMailer failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function build()
    {
        Log::info('CampaignMail: build started', [
            'campaign_uuid'  => $this->campaign->campaign_uuid,
            'subscriber_id'  => $this->subscriber->id,
        ]);

        // 1) Load original HTML
        $html = $this->campaign->body_html;
        Log::info('CampaignMail: original HTML loaded');

        // 2) Keep your existing generic %%user%% replacement
        $html = str_replace('%%user%%', 'User', $html);

        // 2a) NEW: name personalization (does not change any other behavior)
        // Split single "name" column into first/last with safe fallbacks.
        $fullName  = trim((string) ($this->subscriber->name ?? ''));
        $firstName = '';
        $lastName  = '';

        if ($fullName !== '') {
            // Split by any whitespace; first token = first name; rest = last
            $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
            $firstName = $parts[0] ?? '';
            if (count($parts) > 1) {
                $lastName = implode(' ', array_slice($parts, 1));
            }
        }

        // If first name empty, fall back to local-part of email (before @)
        if ($firstName === '' && !empty($this->subscriber->email)) {
            $local = explode('@', $this->subscriber->email)[0] ?? '';
            // Turn "john_doe-smith" → "John Doe Smith"
            $firstName = ucwords(str_replace(['.', '_', '-'], ' ', trim($local)));
        }

        // Final friendly fallback if still empty
        if ($firstName === '') {
            $firstName = 'Friend';
        }

        // Prepare replacement map (only touches placeholders if present)
        $replacements = [
            '%%first_name%%' => $firstName,
            '%%last_name%%'  => $lastName,
            '%%full_name%%'  => ($fullName !== '' ? $fullName : $firstName),
        ];

        // Apply personalization (simple, non-invasive)
        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        // 3) Rewrite all <a> hrefs to click-tracking URLs (unchanged)
        $html = preg_replace_callback('/<a\s[^>]*href="([^"]+)"[^>]*>/i', function ($matches) {
            $originalUrl = $matches[1];

            if (preg_match('/^(mailto:|javascript:)/i', $originalUrl)) {
                return $matches[0];
            }

            $trackedUrl = route('track.click', [
                'campaign_uuid' => $this->campaign->campaign_uuid,
                'subscriber_id' => $this->subscriber->id,
            ]) . '?url=' . urlencode($originalUrl);

            Log::debug('CampaignMail: link rewritten', [
                'original' => $originalUrl,
                'tracked'  => $trackedUrl,
            ]);

            return str_replace($originalUrl, $trackedUrl, $matches[0]);
        }, $html);

        Log::info('CampaignMail: links rewritten');

        // 4) Inject open-tracking pixel (unchanged)
        $openUrl = route('track.open', [
            $this->campaign->campaign_uuid,
            $this->subscriber->id,
        ]);
        $pixel   = '<img src="' . $openUrl . '" width="1" height="1" style="display:none;" alt="" />';
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $pixel . '</body>', $html);
            Log::info('CampaignMail: pixel injected before </body>');
        } else {
            $html .= $pixel;
            Log::info('CampaignMail: pixel appended to end of HTML');
        }

        // 5) Build the email (unchanged)
        $email = $this
            ->subject($this->campaign->subject_override ?: $this->campaign->template_subject)
            ->from($this->campaign->from_address, $this->campaign->from_name)
            ->replyTo($this->campaign->reply_to_address)
            ->html($html);

        Log::info('CampaignMail: basic email composed');

        // 6) Attach any files (unchanged)
        if (!empty($this->campaign->attachments)) {
            $attachments = json_decode($this->campaign->attachments, true) ?: [];
            foreach ($attachments as $file) {
                $path = public_path($file);
                if (file_exists($path)) {
                    $email->attach($path);
                    Log::info('CampaignMail: attached file', ['file' => $file]);
                } else {
                    Log::warning('CampaignMail: attachment missing', ['file' => $file]);
                }
            }
        } else {
            Log::info('CampaignMail: no attachments found');
        }

        Log::info('CampaignMail: build completed', [
            'campaign_id'      => $this->campaign->id,
            'subscriber_email' => $this->subscriber->email,
        ]);

        return $email;
    }
}
