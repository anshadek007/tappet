<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SupportRequest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($view, $content, $subject, $sender)
    {
        $this->content = $content;
        $this->subject = $subject;
        $this->sender = $sender;
        $this->view = $view;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from($this->sender)
                    ->subject($this->subject)
                    ->markdown($this->view)
                    ->with('content', $this->content);
    }
}
