<?php

namespace RZP\Mail\Admin\Account;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Admin\Admin\Entity as AdminEntity;

class Base extends Mailable
{
    protected $admin;

    protected $org;

    protected $input;

    public function __construct(AdminEntity $admin, array $input)
    {
        parent::__construct();

        $this->admin = $admin;

        $this->org = $admin->org;

        $this->input = $input;
    }

    protected function addSender()
    {
        $from = Constants::MAIL_ADDRESSES[Constants::SUPPORT];
        $fromHeader = Constants::HEADERS[Constants::SUPPORT];

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
        $replyTo = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $this->replyTo($replyTo);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $mailTag = $this->getMailTag();

            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, $mailTag);
        });

        return $this;
    }
}
