<?php

namespace RZP\Mail\Dispute;

use RZP\Constants\MailTags;

class BulkCreation extends Base
{

    protected function addSubject()
    {
        $merchantName = $this->data['merchant']['name'];

        $amount = (float) ($this->data['totalAmount'] / 100);

        $subject = 'Disputes raised for a total amount of Rs. ' . $amount . ' against ' . $merchantName;

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.dispute.bulk_creation');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DISPUTES_CREATED_IN_BULK);
        });

        return $this;
    }
}
