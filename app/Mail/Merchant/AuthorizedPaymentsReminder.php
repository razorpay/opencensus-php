<?php

namespace RZP\Mail\Merchant;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;

class AuthorizedPaymentsReminder extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    protected function addRecipients()
    {
        $emails = $this->data['merchant'][Merchant\Entity::TRANSACTION_REPORT_EMAIL];

        $name = $this->data['merchant'][Merchant\Entity::NAME];

        $to = [];

        foreach ($emails as $email) {
            $to[] = [$email. $name];
        }

        $this->to($to);

        return $this;
    }

    protected function addSender()
    {
        $email = Common::MAIL_ADDRESSES[Common::REPORTS];

        $this->from($email);

        return $this;
    }

    protected function addCc()
    {
        $email = Common::MAIL_ADDRESSES[Common::NOTIFICATIONS];

        $this->cc($email);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Common::MAIL_ADDRESSES[Common::SUPPORT];
        $header = Common::FROM_HEADER[Common::SUPPORT];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addSubject()
    {
        // date format = 6th July 2015
        $date = Carbon::today('Asia/Kolkata')->format('jS F Y');

        $final = $this->data['final'];

        $subject = "Razorpay | Authorized Payments Reminder for $date";

        if ($final === true)
        {
            $subject = "Razorpay | Final Authorized Payments Reminder for $date";
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
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::AUTH_REMINDER);

            foreach ($this->data['payments'] as $payment) {
                $headers->addTextHeader(MailTags::HEADER, $payment->getPublicId());
            }
        });

        return $this;
    }
}
