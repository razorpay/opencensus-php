<?php

namespace RZP\Mail\Settlement;

use RZP\Constants\MailTags;

class Report extends Base
{
    protected function getFromHeader()
    {
        return $this->data['channel'] . ' NULL UTR Report';
    }

    protected function getSubject()
    {
        $subject = ' NULL UTR Report | ' . ucfirst($this->data['channel']) . ' for ' . $this->data['date'];

        return $subject;
    }

    protected function addMailData()
    {
        $this->data['body']    = 'Please find the file attached for the null utr records from yesterday.';

        $this->data['subject'] = $this->getSubject();

        $this->with($this->data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::NULL_UTR_REPORT;
    }

    protected function addAttachments()
    {
        $this->attach($this->data['file']);

        return $this;
    }

    protected function addRecipients()
    {
        $email =  Constants::MAIL_ADDRESSES[Constants::SETTLEMENT_ALERTS];

        $this->to($email);

        return $this;
    }
}
