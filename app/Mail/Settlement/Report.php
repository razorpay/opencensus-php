<?php

namespace RZP\Mail\Settlement;

use RZP\Constants\MailTags;

class Report extends Base
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->data['body']    = 'PFA';
    }

    protected function getFromHeader()
    {
        return 'Settlement Alert';
    }

    protected function addSubject()
    {
        $subject = 'Settlement Potential Failures for ' . $this->data['date'];

        $this->subject($subject);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::FTA_RECON_REPORT;
    }

    protected function addAttachments()
    {
        $this->attach($this->data['file'], ['as' => 'report.csv']);

        return $this;
    }

    protected function addRecipients()
    {
        $email =  Constants::MAIL_ADDRESSES[Constants::SETTLEMENT_ALERTS];

        $this->to($email);

        return $this;
    }
}
