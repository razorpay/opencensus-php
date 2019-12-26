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

    protected $purpose;

    protected $merchantDisplayName;

    protected $customerEmail;

    protected $logoUrl;

    protected $primaryColor;

    public function __construct(string $customerEmail,
                                string $otp,
                                string $merchantDisplayName = null,
                                string $purpose = null,
                                string $logoUrl = null,
                                string $primaryColor = null)
    {
        parent::__construct();

        $this->customerEmail = $customerEmail;

        $this->otp = $otp;

        $this->purpose = $purpose;

        $this->merchantDisplayName = $merchantDisplayName;

        $this->primaryColor = $primaryColor;

        $this->logoUrl = $logoUrl;
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
            'otp'                   => $this->otp,
            'merchant_display_name' => $this->merchantDisplayName,
            'purpose'               => $this->purpose,
            'logoUrl'               => $this->logoUrl,
            'primary_color'          => $this->primaryColor,
        ];

        $this->with($data);

        return $this;
    }
}
