<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class ReserveBalanceAdditionSuccess extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $this->to($this->data['email']);
        return $this;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::ALERTS];

        $this->from($email);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::SUPPORT];
        $header = Constants::HEADERS[Constants::SUPPORT];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addCc()
    {
        $email = [];

        $this->cc($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Razorpay | Rs '.$this->data['amount'].' credited to '.$this->data['account_type'].' for '. $this->data['merchant_dba'];

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->data['merchant_id']);

            $headers->addTextHeader(MailTags::HEADER, MailTags::RESERVE_BALANCE_ADDITION_MAIL);
        });

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.reserve_balance_addition_success');

        return $this;
    }
}
