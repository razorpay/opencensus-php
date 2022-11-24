<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class NegativeBalanceThresholdAlert extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        parent::addMailData();

        $this->data = array_merge($this->data, $data);
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::ALERTS];

        $this->from($email);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->data['email']);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.negative_balance_threshold_alert');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->data['merchant_id']);

            $headers->addTextHeader(MailTags::HEADER, $this->data['headers']);
        });

        return $this;
    }
}
