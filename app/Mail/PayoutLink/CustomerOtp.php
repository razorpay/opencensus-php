<?php

namespace RZP\Mail\PayoutLink;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity;

class CustomerOtp extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.customer_otp';

    const SUBJECT = 'One Time Password (OTP) for verification';

    protected $otp;

    protected $payoutLinkId;

    protected $merchantId;

    protected $payoutLink = null;

    public function __construct(string $payoutLinkId, string $merchantId, string $otp)
    {
        parent::__construct();

        $this->otp = $otp;

        $this->payoutLinkId = $payoutLinkId;

        $this->merchantId = $merchantId;
    }

    protected function addRecipients()
    {
        $payoutLink = $this->getPayoutLink();

        $this->to($payoutLink->getContactEmail());

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT]);

        return $this;
    }

    protected function getPayoutLink() :\RZP\Models\PayoutLink\Entity
    {
        if ($this->payoutLink === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->payoutLink = $repo->payout_link->findByIdAndMerchantId($this->payoutLinkId, $this->merchantId);
        }

        return $this->payoutLink;
    }

    protected function getMerchant(): Entity
    {
        return $this->getPayoutLink()->merchant;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
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

        $payoutLink = $this->getPayoutLink();

        $data = [
            'otp'                   => $this->otp,
            'merchant_display_name' => $merchant->getBillingLabel(),
            'purpose'               => $payoutLink->getPurpose(),
            'logoUrl'               => $merchant->getFullLogoUrlWithSize(),
            'primary_color'         => $merchant->getBrandColorElseDefault(),
        ];

        $this->with($data);

        return $this;
    }
}
