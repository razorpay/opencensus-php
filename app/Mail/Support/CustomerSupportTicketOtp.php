<?php

namespace RZP\Mail\Support;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity;

class CustomerSupportTicketOtp extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.support.customer_otp';

    const SUBJECT = 'Razorpay | OTP to Verify Email';

    protected $otp;

    public function __construct(string $email, string $otp)
    {
        parent::__construct();

        $this->otp = $otp;

        $this->email = $email;
    }

    protected function addRecipients()
    {
        $this->to($this->email);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::SUPPORT]);

        return $this;
    }


    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::SUPPORT],
                           Constants::HEADERS[Constants::NOREPLY]);
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
