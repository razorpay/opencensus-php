<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Constants as MerchantConstant;

class AxisActivation extends Mailable
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
        $this->view('emails.merchant.axis_activation');

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::SUPPORT],
            Constants::HEADERS[Constants::SUPPORT]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::SUPPORT],
            Constants::HEADERS[Constants::SUPPORT]);

        return $this;
    }

    protected function addSubject()
    {
        $subject = null;

        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            $subject = $this->data['merchant']['org']['business_name'] . ' | Account activated for ' . $this->data['merchant']['billing_label'];
        }
        else
        {
            $subject = 'Settlements enabled for your Razorpay account';
        }

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
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_ACTIVATED);
        });

        return $this;
    }

    public function isWhitelistActivationFlow(): bool
    {
        return $this->data['merchant'][MerchantConstant::IS_WHITELISTED_ACTIVATION] === true;
    }
}
