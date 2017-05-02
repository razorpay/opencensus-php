<?php

namespace RZP\Mail\Base;

use App;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable as BaseMailable;

class Mailable extends BaseMailable
{
    use Queueable, SerializesModels;

    // Attribute to store app mode value which is used to construct the mailable
    protected $mode;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];
    }

    public function build()
    {
        return $this->addSender()
                    ->addRecipients()
                    ->addCc()
                    ->addBcc()
                    ->addReplyTo()
                    ->addHtmlView()
                    ->addTextView()
                    ->addSubject()
                    ->addMailData()
                    ->addAttachments()
                    ->addHeaders();
    }

    /**
     * Stub method to add mail sender. To be implemented by child classes
     * Use the mailable from() method to add senders
     */
    protected function addSender()
    {
        return $this;
    }

    /**
     * Stub method to add mail recipients. To be implemented by child classes
     * Use the mailable to() method to add recipients
     */
    protected function addRecipients()
    {
        return $this;
    }

    /**
     * Stub method to add reply to addresses. To be implemented by child classes.
     * Use the mailable replyTo() method to add this
     */
    protected function addReplyTo()
    {
        return $this;
    }

    /**
     * Stub method to add cc addresses. To be implemented by child classes.
     * Use the mailable cc() method to add mail addresses in cc
     */
    protected function addCc()
    {
        return $this;
    }

    /**
     * Stub method to add bcc addresses. To be implemented by child classes.
     * Use the mailable bcc() method to add mail addresses in bcc
     */
    protected function addBcc()
    {
        return $this;
    }

    /**
     * Stub method to add HTML mail view. To be implemented by child classes
     * Use the mailable view() method to add html view to mail.
     */
    protected function addHtmlView()
    {
        return $this;
    }

    /**
     * Stub method to add text view for mail. To be implemented by child classes
     * Use the mailable text() method to add text view to mail
     */
    protected function addTextView()
    {
        return $this;
    }

    /**
     * Stub method to add subject to mail. To be implemented by child classes.
     * Use the mailable subject() method to add subject to mail
     */
    protected function addSubject()
    {
        return $this;
    }

    /**
     * Stub method to add mail data for use by the template. To be implemented by child classes.
     * Use the mailable with() method to attach any data to the mail body
     */
    protected function addMailData()
    {
        return $this;
    }

    /**
     * Stub method to handle attachments. To be implemented by child classes
     * Use the mailable attach method to attach any file or raw content to mail.
     */
    protected function addAttachments()
    {
        return $this;
    }

    /**
     * Stub method to add mail headers. To be implemented by child clases
     * Use the withSwiftMessage method to get the underlying swift message and
     * add any mail headers like Mailgun header
     */
    protected function addHeaders()
    {
        return $this;
    }
}
