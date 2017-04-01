<?php

namespace RZP\Mail\Base;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable as BaseMailable;
use Illuminate\Queue\SerializesModels;

class Mailable extends BaseMailable
{
    public function build()
    {
        return $this->addSender()
                    ->addRecipients()
                    ->addHtmlView()
                    ->addTextView()
                    ->addSubject()
                    ->addMailData()
                    ->addAttachments()
                    ->addHeaders();
    }

    /**
     * Stub method to add mail sender. To be implemented by child classes
     */
    protected function addSender()
    {
        return $this;
    }

    /**
     * Stub method to add mail recipients. To be implemented by child classes
     */
    protected function addRecipients()
    {
        return $this;
    }

    /**
     * Stub method to add reply to addresses. To be implemented by child classes.
     */
    protected function addReplyTo()
    {
        return $this;
    }

    /**
     * Stub method to add cc addresses. To be implemented by child classes.
     */
    protected function addCc()
    {
        return $this;
    }

    /**
     * Stub method to add bcc addresses. To be implemented by child classes.
     */
    protected function addBcc()
    {
        return $this;
    }

    /**
     * Stub method to add HTML mail view. To be implemented by child classes
     */
    protected function addHtmlView()
    {
        return $this;
    }

    /**
     * Stub method to add text view for mail. To be implemented by child classes
     */
    protected function addTextView()
    {
        return $this;
    }

    /**
     * Stub method to add subject to mail. To be implemented by child classes.
     */
    protected function addSubject()
    {
        return $this;
    }

    /**
     * Stub method to add mail data for use by the template.
     * To be implemented by child classes.
     */
    protected function addMailData()
    {
        return $this;
    }

    /**
     * Stub method to handle attachments. To be implemented by child classes
     */
    protected function addAttachments()
    {
        return $this;
    }

    /**
     * Stub method to add mail headers. To be implemented by child clases
     */
    protected function addHeaders()
    {
        return $this;
    }
}
