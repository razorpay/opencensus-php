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
        return $this->data['header'];
    }

    protected function addSubject()
    {
        $this->subject($this->data['subject']);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::FTA_RECON_REPORT;
    }

    protected function addAttachments()
    {
        foreach ( $this->data['attachments'] as $file)
        {
            $this->attach($file);
        }

        return $this;
    }

    protected function addRecipients()
    {
        $email =  Constants::MAIL_ADDRESSES[Constants::SETTLEMENT_ALERTS];

        $this->to($email);

        return $this;
    }
}
