<?php

namespace RZP\Mail\Admin;

use RZP\Constants\MailTags;
use Symfony\Component\Mime\Email;

class PartnerInvitation extends MerchantInvitation
{
    public function __construct(array $admin, array $org, array $invitation)
    {
        parent::__construct($admin, $org, $invitation);
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(
                MailTags::HEADER, MailTags::ADMIN_INVITE_PARTNER);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.invite_partner');

        return $this;
    }
}
