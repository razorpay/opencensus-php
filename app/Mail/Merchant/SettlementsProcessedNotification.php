<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class SettlementsProcessedNotification extends Mailable
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
        $this->to($this->data['merchant']['email']);

        return $this;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::REPORTS];

        $header = Constants::HEADERS[Constants::REPORTS];

        $this->from($email, $header);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $this->replyTo($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Razorpay Settlement Notification';

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
        if($this->data['settlement']['has_aggregated_fee_tax'] === false)
        {
            $this->view('emails.merchant.settlement_notification_new');
        }
        else
        {
           $this->view('emails.merchant.settlement_notification_old');
        }

        return $this;
    }
}
