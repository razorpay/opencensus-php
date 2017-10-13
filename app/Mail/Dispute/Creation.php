<?php

namespace RZP\Mail\Dispute;

use RZP\Constants\MailTags;

class Creation extends Base
{

    protected function addSubject()
    {
        $merchantName = $this->data['merchant']['name'];

        $this->subject('Chargeback Alert');

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

            $headers->addTextHeader(MailTags::HEADER, MailTags::DISPUTE_CREATED_MAIL);

            // TODO : Check if any additional headers to be added (waiting on pooja)
        });

        return $this;
    }
}
