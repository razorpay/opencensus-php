<?php

namespace RZP\Mail\Merchant;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant;

class AuthorizedPaymentsReminder extends Mailable
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
        $emails = $this->data['merchant'][Merchant\Entity::TRANSACTION_REPORT_EMAIL];

        $name = $this->data['merchant'][Merchant\Entity::NAME];

        $this->to($emails, $name);

        return $this;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::REPORTS];

        $this->from($email);

        return $this;
    }

    protected function addCc()
    {
        $email = [];

        $this->cc($email);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $header = Constants::HEADERS[Constants::NOREPLY];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addSubject()
    {
        // date format = 6th July 2015
        $date = Carbon::today(Timezone::IST)->format('jS F Y');

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

    protected function addHtmlView()
    {
        $this->view('emails.merchant.authorized_reminder');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::AUTH_REMINDER);

            foreach ($this->data['payments'] as $payment)
            {
                $headers->addTextHeader(MailTags::HEADER, $payment['public_id']);
            }
        });

        return $this;
    }
}
