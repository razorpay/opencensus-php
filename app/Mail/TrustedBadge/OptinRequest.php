<?php

namespace RZP\Mail\TrustedBadge;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;

/**
 * Represents the Email sent when a Merchant Opts-out, then becomes ineligible & then becomes eligible back again.
 *
 * @package RZP\Mail\TrustedBadge
 */
class OptinRequest extends Base
{
    /** @var string The subject of the email message. */
    public $subject = 'Activate your Razorpay Trusted Badge';

    /**
     * @return $this
     */
    protected function addHtmlView(): self
    {
        return $this->view('emails.trusted_badge.optin_request');
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
            $headers->addTextHeader(MailTags::HEADER, MailTags::RTB_OPTIN_REQUEST);
        });

        return $this;
    }
}
