<?php

namespace RZP\Mail\PayoutLink;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity;

class CustomerOtpInternal extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.customer_otp';

    const SUBJECT = 'One Time Password (OTP) for verification';

    protected $otp;

    protected $merchantId;

    protected $toEmail;

    protected $purpose;

    protected $merchant = null;

    public function __construct(string $merchantId, string $otp, string $toEmail, string $purpose)
    {
        parent::__construct();

        $this->otp = $otp;

        $this->merchantId = $merchantId;

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

    protected function getMerchant(): Entity
    {
        if ($this->merchant === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->merchant = $repo->merchant->findByPublicId($this->merchantId);
        }

        return $this->merchant;
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
        $merchant = $this->getMerchant();

        $displayName = $merchant->getBillingLabel();

        $data = [
            'otp'                   => $this->otp,
            'merchant_display_name' => $displayName,
            'purpose'               => $this->purpose,
            'logoUrl'               => $merchant->getFullLogoUrlWithSize(),
            'primary_color'         => $merchant->getBrandColorElseDefault(),
        ];

        $this->with($data);

        return $this;
    }
}
