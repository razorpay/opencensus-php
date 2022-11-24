<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;

class CreateSubMerchant extends Mailable
{
    protected $subMerchant;

    protected $aggregator;

    public function __construct(array $subMerchant, array $aggregator)
    {
        parent::__construct();

        $this->subMerchant = $subMerchant;

        $this->aggregator = $aggregator;
    }

    protected function addRecipients()
    {
        $email = $this->subMerchant['email'];

        $name = $this->subMerchant['name'];

        $this->to($email, $name);

        return $this;
    }

    protected function addCc()
    {
        if ($this->subMerchant['email'] !== $this->aggregator['email'])
        {
            $this->cc($this->aggregator['email']);
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
            'name'  => $this->subMerchant['name'],
            'email' => $this->subMerchant['email'],
        ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
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
