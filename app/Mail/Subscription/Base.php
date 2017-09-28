<?php

namespace RZP\Mail\Subscription;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Mail\Payment;

class Base extends Payment\Base
{
    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::SUBSCRIPTIONS];

        $header = Constants::HEADERS[Constants::REPORTS];

        $this->from($email, $header);

        return $this;
    }

    protected function addSubject()
    {
        $action = $this->getAction();

        $label = $this->data['merchant']['billing_label'];

        $subject = "$action for $label";

        if ($this->isMerchantEmail === true)
        {
            $subject = "Razorpay | $subject";
        }

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $subscriptionId = $this->data['subscription']['id'];

            $mailTag = $this->getMailTag();

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $subscriptionId);

            $headers->addTextHeader(MailTags::HEADER, $mailTag);
        });

        return $this;
    }
}
