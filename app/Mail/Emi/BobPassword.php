<?php

namespace RZP\Mail\Emi;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Mail\Base\Constants;

class BobPassword extends Base
{
    protected $emiFilePassword;

    public function __construct(string $bankName, string $emiFilePassword, array $emails)
    {
        parent::__construct($bankName, $emails);

        $this->emiFilePassword = $emiFilePassword;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::EMI];

        $fromHeader = $this->bankName . ' Emi File ';

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = $this->emails;

        $this->to($emails);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = $this->bankName . ' Emi File for ' . $today;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $body = $this->bankName . ' Emi File for ' . $today . " is " . $this->emiFilePassword;

        $data = [
            'body' => $body
        ];

        $this->with($data);

        return $this;
    }
}
