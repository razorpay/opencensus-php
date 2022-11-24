<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Models\Admin\Org;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class PartnerNeedsClarificationEmail extends Mailable
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
        $this->to($this->data['merchant']['email']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.partner_needs_clarification');

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
            $senderEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
            $senderName = Constants::HEADERS[Constants::NOREPLY];

            $this->from($senderEmail, $senderName);
        }

        return $this;
    }

    protected function addBcc()
    {
        if ($this->org[Org\Entity::ID] === Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->cc(Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK]);
        }

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'There is an issue with your Partner activation form';

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

            $headers->addTextHeader(MailTags::HEADER, MailTags::NEEDS_CLARIFICATION);
        });

        return $this;
    }
}
