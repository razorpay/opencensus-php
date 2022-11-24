<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant\Constants as MerchantConstant;

class NotifyActivationSubmission extends Mailable
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
        $toEmail = $this->data['contact_email'];

        $toName = $this->data['contact_name'];

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.notify_activation_submission');

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
            $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

            $fromName = Constants::HEADERS[Constants::SUPPORT];

            $this->from($fromEmail, $fromName);
        }

        return $this;
    }

    protected function addSubject()
    {
        if ($this->data[MerchantConstant::IS_WHITELISTED_ACTIVATION] === true)
        {
            $subject = 'KYC form submitted for ' . $this->org['business_name'];
        }
        else
        {
            $subject = $this->org['business_name'] . ' | Account pending approval for ' . $this->data['business_name'];
        }

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $mailData = [
            'merchant_details' => $this->data,
            'org'              => $this->org
        ];

        $this->with($mailData);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::CONFIRM_ACTIVATION_SUBMISSION);
        });

        return $this;
    }
}
