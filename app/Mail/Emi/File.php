<?php

namespace RZP\Mail\Emi;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Mail\Base\Constants;

class File extends Base
{
    protected $fileData;

    protected $data;

    public function __construct(string $bankName, $fileData, array $emails, $data = null)
    {
        parent::__construct($bankName, $emails);

        $this->fileData = $fileData;

        $this->data = $data;
    }

    public function getFileData()
    {
        return $this->fileData;
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
        if (empty($this->data) === false)
        {
            $data = $this->data;
        }
        else
        {
            $data = [
                'body' => 'Please process the attached EMI file'
            ];
        }

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

    protected function addAttachments()
    {
        if (empty($this->fileData) === false)
        {
            $this->attach(
                $this->fileData['signed_url'],
                [
                    'as'   => $this->fileData['file_name'],
                    'mime' => 'application/zip'
                ]);
        }

        return $this;
    }
}
