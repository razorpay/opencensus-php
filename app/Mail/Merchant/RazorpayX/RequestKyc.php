<?php
namespace RZP\Mail\Merchant\RazorpayX;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class RequestKyc extends Mailable
{
    const SUBJECT       = 'Need a few more details to complete activation on RazorpayX';

    const TEMPLATE_PATH = 'emails.merchant.razorpayx.request_kyc';

    const SUPPORT_URL   = 'https://razorpay.com/support/#request/merchant';

    const FILL_KYC_URL  = 'https://x.razorpay.com/activation';

    protected $merchantName;

    protected $merchantEmail;

    public function __construct(string $merchantName, string $merchantEmail)
    {
        parent::__construct();

        $this->merchantEmail = $merchantEmail;

        $this->merchantName = $merchantName;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->merchantEmail, $this->merchantName);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH);

        return $this;
    }

    protected function addMailData()
    {
        $this->with(
            [
                'dashboard_url' => self::FILL_KYC_URL,
                'support_url'   => self::SUPPORT_URL
            ]);

        return $this;
    }
}
