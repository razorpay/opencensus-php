<?php

namespace RZP\Mail\PayoutLink;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class CustomerDemoOtpInternal extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.customer_otp';

    const SUBJECT = 'One Time Password (OTP) for verification';

    protected $otp;

    protected $merchantInfo;

    protected $toEmail;

    protected $purpose;

    protected $merchant = null;

    public function __construct(array $merchantInfo, string $otp, string $toEmail, string $purpose)
    {
        parent::__construct();

        $this->otp = $otp;

        $this->merchantInfo = $merchantInfo;

        $this->toEmail = $toEmail;

        $this->purpose = $purpose;
    }

    protected function addRecipients()
    {
        $this->to($this->toEmail);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function getMerchantInfo(): array
    {
        return $this->merchantInfo;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
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
        $merchantInfo = $this->getMerchantInfo();

        $data = [
            'otp'                   => $this->otp,
            'merchant_display_name' => $merchantInfo['billing_label'],
            'purpose'               => $this->purpose,
            'logoUrl'               => $merchantInfo['brand_logo'],
            'primary_color'         => $merchantInfo['brand_color'],
        ];

        $this->with($data);

        return $this;
    }
}
