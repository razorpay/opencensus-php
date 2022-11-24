<?php

namespace RZP\Mail\Admin\Account;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class Base extends Mailable
{
    protected $admin;

    protected $org;

    protected $input;

    public function __construct(array $admin, array $org, array $input)
    {
        parent::__construct();

        $this->admin = $admin;

        $this->org = $org;

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
        $to = $this->admin['email'];

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
        $this->withSymfonyMessage(function (Email $message)
        {
            $mailTag = $this->getMailTag();

            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, $mailTag);
        });

        return $this;
    }

    protected function getOrgDataForMail($orgId = null)
    {
        return parent::getMailDataForAdmin();
    }
}
