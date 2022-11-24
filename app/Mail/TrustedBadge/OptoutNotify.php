<?php

namespace RZP\Mail\TrustedBadge;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;

/**
 * Represents email sent to notify a merchant that they have opted-out of RTB & it is removed from their checkout.
 *
 * @package RZP\Mail\TrustedBadge
 */
class OptoutNotify extends Base
{
    /** @var string The subject of the email message. */
    public $subject = 'You have opted out of Razorpay Trusted Badge program';

    /**
     * @return OptoutNotify
     */
    protected function addHtmlView(): OptoutNotify
    {
        return $this->view('emails.trusted_badge.optout_notify');
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
            $headers->addTextHeader(MailTags::HEADER, MailTags::RTB_OPTOUT_NOTIFY);
        });

        return $this;
    }
}
