<?php

namespace RZP\Mail\Emi;

use Carbon\Carbon;

class File extends Base
{
    public function __construct(array $emailIdsToSendTo, string $bankName, string $filePath)
    {
        parent::__construct($emailIdsToSendTo, $bankName);

        $this->filePath = $filePath;
    }

    protected function getFromHeader()
    {
        $fromHeader = $this->bankName . ' Emi File';

        return $fromHeader;
    }

    protected function getToEmails()
    {
        return array_merge($this->emailIdsToSendTo, ['settlements@razorpay.com']);
    }

    protected function getData()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $body = 'Please process the attached EMI file';

        $subject = $this->bankName . ' Emi File for ' . $today;

        return [
            'body' => $body,
            'subject' => $subject
        ];
    }
}
