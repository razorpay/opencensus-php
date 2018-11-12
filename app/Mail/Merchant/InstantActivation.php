<?php

namespace RZP\Mail\Merchant;

use RZP\Models\Admin\Org;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class InstantActivation extends Mailable
{
    protected $data;

    protected $org;

    public function __construct(array $data, array $org)
    {
        parent::__construct();

        $this->data = $data;

        $this->org = $org;
    }

    protected function addRecipients()
    {
        $this->to($this->data['merchant']['email']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.instant_activation');

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
        $subject = "Start accepting payments with " . $this->data['merchant']['org']['business_name'];

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function($message) {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::INSTANT_ACTIVATION);
        });

        return $this;
    }

}
