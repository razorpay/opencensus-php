<?php

namespace RZP\Mail\Emi;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Mail\Base\Constants;

class NoTransaction extends Base
{
    public function __construct(string $bankName, array $emails)
    {
        parent::__construct($bankName, $emails);
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::EMI];

        $fromHeader = $this->bankName . ' Emi File';

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addMailData()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $body = 'There were no transactions processed on '. $today.', hence no MIS is sent.';

        $data = [
            'body' => $body
        ];

        $this->with($data);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = $this->bankName . ' Emi File for ' . $today;

        $this->subject($subject);

        return $this;
    }
}
