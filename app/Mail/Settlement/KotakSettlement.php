<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;
use RZP\Constants\MailTags;

class KotakSettlement extends Base
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $this->subject = "Kotak Settlement files for $today";

        $this->fromHeader = 'Kotak Settlement';
    }

    public function build()
    {
        parent::build();

        return $this->view('emails.admin.settlement')
                    ->attach($file . '.xlsx')
                    ->attach($file . '.txt')
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::KOTAK_SETTLEMENT_FILES);
                    });
    }
}
