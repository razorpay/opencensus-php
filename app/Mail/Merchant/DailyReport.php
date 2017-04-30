<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant;

class DailyReport extends Mailable
{
    protected $data;

    protected $merchant;

    public function __construct(array $data, Merchant\Entity $merchant)
    {
        $this->data = $data;

        $this->merchant = $merchant;
    }

    protected function addRecipients()
    {
        $this->to($this->data['email']);

        return $this;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::REPORTS];

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
        $email = Constants::MAIL_ADDRESSES[Constants::NOTIFICATIONS];

        $this->cc($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Razorpay | Daily Transaction Report for ' . $this->data['date'];

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->merchant->getPublicId());

            $headers->addTextHeader(MailTags::HEADER, MailTags::DAILY_REPORT);
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
        $this->view('emails.merchant.daily_report');

        return $this;
    }
}
