<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;
use RZP\Models\Merchant;

class CreateSubMerchant extends Mailable
{
    protected $subMerchant;

    protected $aggregator;

    public function __construct(Merchant\Entity $subMerchant, Merchant\Entity $aggregator)
    {
        parent::__construct();

        $this->subMerchant = $subMerchant;

        $this->aggregator = $aggregator;
    }

    protected function addRecipients()
    {
        $email = $this->subMerchant->getEmail();

        $name = $this->subMerchant->getName();

        $this->to($email, $name);

        return $this;
    }

    protected function addCc()
    {
        if ($this->subMerchant->getEmail() !== $this->aggregator->getEmail())
        {
            $this->cc($this->aggregator->getEmail());
        }

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Welcome to Razorpay');

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'name'  => $this->subMerchant->getName(),
            'email' => $this->subMerchant->getEmail(),
        ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::WELCOME);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.welcome');

        return $this;
    }
}
