<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Constants\MailTags;

class CriticalFailure extends Base
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $channel = ucfirst($this->data['channel']);

        $this->data['body']    = $channel . ' Nodal Account Settlements have failed due to some critical error on '
                                 . Carbon::now(Timezone::IST)->format('Y-m-d H:i:m');
    }

    protected function getFromHeader()
    {
        return 'Settlement Alert';
    }

    protected function addSubject()
    {
        $today = Carbon::today(Timezone::IST)->format('d-m-Y');

        $subject = ucfirst($this->data['channel']) . ' Critical error on ' . $today;

        $this->subject($subject);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::FTA_CRITICAL_ERROR;
    }

    protected function addRecipients()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::SETTLEMENTS];

        $this->to($email);

        return $this;
    }
}
