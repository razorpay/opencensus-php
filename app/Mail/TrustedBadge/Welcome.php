<?php

namespace RZP\Mail\TrustedBadge;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;

/**
 * Represents welcome email sent to merchant when RTB is enabled on their account very first time.
 *
 * @package RZP\Mail\TrustedBadge
 */
class Welcome extends Base
{
    /** @var string The subject of the email message. */
    public $subject = 'Say Hello to the Razorpay Trusted Badge!';

    /**
     * @return $this
     */
    protected function addHtmlView(): self
    {
        return $this->view('emails.trusted_badge.welcome');
    }

    /**
     * Add Tracking Headers to this Email
     *
     * @return $this
     */
    protected function addHeaders(): self
    {
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, $this->data['merchantId']);
            $headers->addTextHeader(MailTags::HEADER, MailTags::RTB_WELCOME);
        });

        return $this;
    }
}
