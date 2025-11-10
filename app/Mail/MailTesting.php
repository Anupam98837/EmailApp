<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailTesting extends Mailable
{
    use Queueable, SerializesModels;

    public string $from_address;
    public string $from_name;
    public ?string $reply_to;
    public string $subject_line;
    public string $html_body;
    public ?string $text_body;

    /**
     * @param  array{
     *   from_address: string,
     *   from_name:    string,
     *   reply_to?:    string|null,
     *   subject:      string,
     *   html:         string,
     *   text?:        string|null
     * }  $data
     */
    public function __construct(array $data)
    {
        $this->from_address = $data['from_address'];
        $this->from_name    = $data['from_name'];
        $this->reply_to     = $data['reply_to']  ?? null;
        $this->subject_line = $data['subject'];
        $this->html_body    = $data['html'];
        $this->text_body    = $data['text']      ?? null;
    }

    public function build()
    {
        $mail = $this
            ->from($this->from_address, $this->from_name)
            ->subject($this->subject_line)
            ->html($this->html_body);

        if ($this->reply_to) {
            $mail->replyTo($this->reply_to);
        }

        if ($this->text_body) {
            // Add a plain-text part for mail clients that need it
            $mail->withSwiftMessage(function ($message) {
                $message->addPart($this->text_body, 'text/plain');
            });
        }

        return $mail;
    }
}
