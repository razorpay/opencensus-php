<?php

namespace RZP\Mail\Emi;

use Carbon\Carbon;

class Password extends Base
{
    protected $emiFilePassword;

    public function __construct(string $bankName, string $emiFilePassword)
    {
        parent::__construct($bankName);

        $this->emiFilePassword = $emiFilePassword;
    }

    protected function addSender()
    {
        $fromEmail = Common::MAIL_ADDRESSES[Common::EMI];

        $fromHeader = $this->bankName . ' Emi File Password';

        $this->to($fromEmail, $fromHeader);
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = $this->bankName . ' Emi File Password for ' . $today;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $body = $this->bankName . ' Emi File Password for ' . $today . " is " . $this->emiFilePassword;

        $data = [
            'body' => $body
        ];

        $this->with($data);

        return $this;
    }
}
