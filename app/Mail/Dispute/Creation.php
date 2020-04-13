<?php

namespace RZP\Mail\Dispute;

use RZP\Constants\MailTags;
use RZP\Models\Currency\Currency;

class Creation extends Base
{
    protected function addSubject()
    {
        $merchantName = $this->data['merchant']['name'];

        $amount = $this->data['dispute']['amount'];
        $currency = $this->data['dispute']['currency'];

        $formattedAmount = $this->getFormattedAmount($amount, $currency);

        $subject = 'Dispute raised for ' . $formattedAmount . ' on '
            . $this->data['dispute']['payment_id']
            . ' against ' . $merchantName;

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.dispute.creation');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $disputeId = $this->data['dispute']['id'];

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DISPUTE_CREATED);

            $headers->addTextHeader(MailTags::HEADER, $disputeId);
        });

        return $this;
    }
}
