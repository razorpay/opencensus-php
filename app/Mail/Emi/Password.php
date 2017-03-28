<?php

namespace RZP\Mail\Emi;

use Carbon\Carbon;

class Password extends Base
{
    protected $emiFilePassword;

    public function __construct(array $emailIdsToSendTo, string $bankName, string $emiFilePassword)
    {
        parent::__construct($emailIdsToSendTo, $bankName);

        $this->emiFilePassword = $emiFilePassword;
    }

    protected function getFromHeader()
    {
        $fromHeader = $this->bankName . ' Emi File Password';

        return $fromHeader;
    }

    protected function getData()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $body = $this->bankName . ' Emi File Password for ' . $today . " is " . $this->emiFilePassword;

        $subject = $this->bankName . ' Emi File Password for ' . $today;

        return [
            'body'    => $body,
            'subject' => $subject,
        ];
    }
}
