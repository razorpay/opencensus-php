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
        return 'Settlement UTR Alert';
    }

    protected function addSubject()
    {
        $subject = 'No UTR Report for ' . $this->data['date'];

        $this->subject($subject);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::NULL_UTR_REPORT;
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
