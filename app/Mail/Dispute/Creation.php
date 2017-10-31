<?php

namespace RZP\Mail\Dispute;

use RZP\Constants\MailTags;

class Creation extends Base
{

    protected function addSubject()
    {
        $merchantName = $this->data['merchant']['name'];

        $this->subject('Alert - A dispute has been received against ' . $merchantName);

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
