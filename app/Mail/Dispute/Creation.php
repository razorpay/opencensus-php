<?php

namespace RZP\Mail\Dispute;

use RZP\Constants\MailTags;
use RZP\Models\Payment\Entity as Payment;

class Creation extends Base
{

    protected function addSubject()
    {
        $merchantName = $this->data['merchant']['name'];

        $amount = (float) ($this->data['dispute']['amount'] / 100);

        $subject = 'Dispute raised for Rs. ' . $amount . ' on '
            . Payment::getSignedId($this->data['dispute']['payment_id'])
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
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DISPUTE_CREATED);
        });

        return $this;
    }
}
