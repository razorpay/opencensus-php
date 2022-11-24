<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Models\Admin\Org;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class SubMerchantNCStatusChanged extends Mailable
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
        $this->to($this->data['partner_email']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.submerchant_nc_status_changed');

        return $this;
    }

    protected function addSender()
    {
        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->from($this->org['from_email'], $this->org['display_name']);
        }
        else
        {
            $senderEmail = Constants::MAIL_ADDRESSES[Constants::PARTNER_NOTIFICATIONS];
            $senderName = Constants::HEADERS[Constants::PARTNER_NOTIFICATIONS];

            $this->from($senderEmail, $senderName);
        }

        return $this;
    }

    protected function addBcc()
    {
        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Your affiliate’s (MID: '.$this->data['merchant']['id'].') Razorpay account needs action';

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
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::SUBMERCHANT_NC_STATUS_CHANGED);
        });

        return $this;
    }
}
