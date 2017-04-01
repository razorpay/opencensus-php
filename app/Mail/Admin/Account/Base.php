<?php

namespace RZP\Mail\Admin\Account;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Common;
use RZP\Mail\Base\Mailable;

class Base extends Mailable
{
    protected $admin;

    protected $org;

    protected $input;

    protected $header;

    public function __construct($admin, array $input)
    {
        $this->admin = $admin;

        $this->org = $admin->org;

        $this->input = $input;
    }

    protected function addSender()
    {
        $from = Common::MAIL_ADDRESSES[Common::SUPPORT];
        $fromHeader = Common::FROM_HEADER[Common::SUPPORT];

        $this->from($from, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $to = $this->admin->getEmail();

        $this->to($to);

        return $this;
    }

    protected function addReplyTo()
    {
        $replyTo = Common::MAIL_ADDRESSES[Common::SUPPORT];

        $this->replyTo($replyTo);

        return $this;
    }
}
