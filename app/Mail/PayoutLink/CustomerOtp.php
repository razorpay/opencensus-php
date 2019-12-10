<?php

namespace RZP\Mail\PayoutLink;

use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class CustomerOtp extends Mailable
{
    const SUBJECT = 'Otp for Payout Link';

    protected $customerEmail;

    protected $otp;

    const EMAIL_TEMPLATE = 'emails.payout_link.customer_otp';

    public function __construct(string $customerEmail, string $otp)
    {
        parent::__construct();

        $this->customerEmail = $customerEmail;

        $this->otp = $otp;
    }

    protected function addRecipients()
    {
        $this->to($this->customerEmail);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addSender()
    {
        return $this->from(
            Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
            Constants::HEADERS[Constants::NOREPLY]
        );
    }

    protected function addHtmlView()
    {
        $this->view(self::EMAIL_TEMPLATE);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'otp' => $this->otp
        ];

        $this->with($data);

        return $this;
    }
}
