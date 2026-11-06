<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $subject;
    public $messageContent;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $email, $subject, $messageContent)
    {
        $this->name = $name;
        $this->email = $email;
        $this->subject = $subject;
        $this->messageContent = $messageContent;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('New Contact Message: ' . $this->subject)
                    ->replyTo($this->email, $this->name)
                    ->view('emails.contact-message')
                    ->with([
                        'name' => $this->name,
                        'email' => $this->email,
                        'subject' => $this->subject,
                        'messageContent' => $this->messageContent,
                    ]);
    }
}
