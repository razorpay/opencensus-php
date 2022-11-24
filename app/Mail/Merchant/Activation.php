<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Constants as MerchantConstant;

class Activation extends Mailable
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
        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            if ($this->isWhitelistActivationFlow() === true)
            {
                $this->view('emails.merchant.whitelist_activation_heimdall');
            }
            else
            {
                $this->view('emails.merchant.activation_heimdall');
            }
        }
        else
        {
            $this->view('emails.merchant.activation');
        }

        return $this;
    }

    protected function addTextView()
    {
        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            if ($this->isWhitelistActivationFlow() === true)
            {
                $this->text('emails.merchant.whitelist_activation_text_heimdall');
            }
            else
            {
                $this->text('emails.merchant.activation_text_heimdall');
            }
        }

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

    protected function addCc()
    {
        if ($this->org[Org\Entity::ID] === Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->cc([]);
        }

        return $this;
    }

    protected function addSubject()
    {
        $subject = null;

        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            if ($this->isWhitelistActivationFlow() === true)
            {
                $subject = 'KYC verification for ' . $this->data['merchant']['org']['business_name'] . ' is complete';
            }
            else
            {
                $subject = $this->data['merchant']['org']['business_name'] . ' | Account activated for ' . $this->data['merchant']['billing_label'];
            }
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
