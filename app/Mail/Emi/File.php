<?php

namespace RZP\Mail\Emi;

use Carbon\Carbon;

use RZP\Mail\Base\Constants;

class File extends Base
{
    protected $filePath;

    public function __construct(string $bankName, string $filePath)
    {
        parent::__construct($bankName);

        $this->filePath = $filePath;
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
        $data = [
            'body' => 'Please process the attached EMI file'
        ];

        $this->with($data);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = $this->bankName . ' Emi File for ' . $today;

        $this->subject($subject);

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->filePath);

        return $this;
    }
}
