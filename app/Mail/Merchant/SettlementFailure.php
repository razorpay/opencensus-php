<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Constants\MailTags;

class SettlementFailure extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        parent::addMailData();

        $this->data = array_merge($this->data, $data);
    }

    protected function addRecipients()
    {
        $email = $this->data['merchant_email'];

        $this->to($email);

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY], 'Razorpay Settlement Support');

        return $this;
    }

    protected function addCc()
    {
        $this->cc(Constants::MAIL_ADDRESSES[Constants::SUPPORT]);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Razorpay | Notification for failed settlement on your account ' . $this->data['merchant_id'];

        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $mailData = $this->data;

        $mailData['subject'] = $this->getSubject();

        $this->with($mailData);

        return $this;
    }

    protected function getSubject()
    {
        return 'Razorpay | Notification for failed settlement on your account ' . $this->data['merchant_id'];
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.settlement_failure');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::SETTLEMENT_FAILURE_EMAIL);
        });
    }
}
