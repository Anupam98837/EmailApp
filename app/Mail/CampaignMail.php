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
        $this->campaign = $campaign;
        $this->subscriber = $subscriber;

        Log::info('CampaignMail: __construct called', [
            'campaign_id' => $this->campaign->id,
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

            if (!$mailer) {
                Log::warning('CampaignMail: no matching mailer found, using fallback', [
                    'from_address' => $this->campaign->from_address,
                ]);
                return;
            }

            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => $mailer->host,
                'port' => $mailer->port,
                'encryption' => $mailer->encryption,
                'username' => $mailer->username,
                'password' => $mailer->password,
                'timeout' => null,
                'auth_mode' => null,
            ]);

            Config::set('mail.from.address', $mailer->from_address);
            Config::set('mail.from.name', $mailer->from_name);

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
            'campaign_uuid' => $this->campaign->campaign_uuid,
            'subscriber_id' => $this->subscriber->id,
        ]);

        $openUrl = route('track.open', [$this->campaign->campaign_uuid, $this->subscriber->id]);

        $html = $this->campaign->body_html;

        Log::info('CampaignMail: original HTML loaded');

        // Trackable links
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

        // Embed open tracking pixel
        $pixel = '<img src="' . $openUrl . '" width="1" height="1" style="display:none;" alt="" />';

        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $pixel . '</body>', $html);
            Log::info('CampaignMail: pixel injected before </body>');
        } else {
            $html .= $pixel;
            Log::info('CampaignMail: pixel appended to end of HTML');
        }

        $email = $this->subject($this->campaign->subject_override ?: $this->campaign->template_subject)
                      ->from($this->campaign->from_address, $this->campaign->from_name)
                      ->replyTo($this->campaign->reply_to_address)
                      ->html($html);

        Log::info('CampaignMail: basic email composed');

        // Handle attachments
        if (!empty($this->campaign->attachments)) {
            $attachments = json_decode($this->campaign->attachments, true) ?? [];
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
            'campaign_id' => $this->campaign->id,
            'subscriber_email' => $this->subscriber->email,
        ]);

        return $email;
    }
}
