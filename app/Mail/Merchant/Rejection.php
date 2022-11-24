<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Models\Admin\Org;

class Rejection extends Mailable
{
    protected $data;

    protected $org;

    public function __construct(array $data, array $org)
    {
        parent::__construct();
        $this->data = $data;
        $this->org  = $org;
    }

    protected function addRecipients()
    {
        $this->to($this->data['email'], $this->data['name']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.rejection_notification');

        return $this;
    }

    protected function addSender()
    {
        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->from($this->org['from_email'], $this->org['display_name']);
        }

        return $this;
    }

    protected function addSubject()
    {
        $label   = $this->data['id'] .' '. $this->data['name'];
        $subject = 'Activation form update '. $label;
        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_REJECTED);
        });

        return $this;
    }
}
