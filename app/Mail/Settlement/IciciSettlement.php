<?php

namespace RZP\Mail\Settlement;

use RZP\Constants\MailTags;

class IciciSettlement extends Base
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->fromHeader = 'ICICI Transfer File';

        $today = Carbon::today('Asia/Kolkata')->format('d-m-Y');

        $this->subject = "Icici Transfer files for $today";
    }

    public function build()
    {
         parent::build();

         return $this->view('emails.message')
                        ->attach($this->data['file'])
                        ->withSwiftMessage(function ($message)
                        {
                            $headers = $message->getHeaders();

                            $headers->addTextHeader(MailTags::HEADER, MailTags::ICICI_SETTLEMENT_FILES);
                        });
    }
}
