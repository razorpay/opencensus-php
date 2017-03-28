<?php

namespace RZP\Mail\Settlement;

use RZP\Constants\MailTags;

class KotakReconciliation extends Base
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->subject = "Re: Kotak Settlement files for $this->data['date']";

        $this->fromHeader = 'Kotak Settlement';
    }

    public function build()
    {
        parent::build();

        return $this->view('emails.message')
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::KOTAK_BENEFICIARY_MAIL);
                    });
    }
}
