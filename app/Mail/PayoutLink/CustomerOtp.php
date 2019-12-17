<?php

namespace RZP\Mail\PayoutLink;

use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class CustomerOtp extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.customer_otp';

    const SUBJECT = 'One Time Password (OTP) for verification';

    protected $otp;

    protected $description;

    protected $merchantName;

    protected $customerEmail;

    public function __construct(string $customerEmail, string $otp, $merchantName, $description)
    {
        parent::__construct();

        $this->customerEmail = $customerEmail;

        $this->otp = $otp;

        $this->description = $description;

        $this->merchantName = $merchantName;
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
            'otp'           => $this->otp,
            'merchant_name' => $this->merchantName,
            'description'   => $this->description,
        ];

        $this->with($data);

        return $this;
    }
}
