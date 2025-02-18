<?php

namespace RZP\Mail\PayoutLink;

use App;
use RZP\Mail\Base\EmailHelper;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Mail\Transaction\Payout;
use RZP\Trace\TraceCode;

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

    public function shouldSendEmailViaStork():bool
    {
        $app = \App::getFacadeRoot();

        $merchantInfo = $this->getMerchantInfo();

        $isStorkEmailVIAEnabled = (new EmailHelper())->isSendingPayoutServiceMailsSupported($merchantInfo['id'],$this->view);

        $traceData = [
            'merchant_id' => $merchantInfo['id'],
            'should_create_via_stork' => $isStorkEmailVIAEnabled,
            'template_view' => $this->view,
        ];

        $app['trace']->info(TraceCode::PAYOUT_SERVICE_EMAIL_ATTEMPT_STORK_ALL, $traceData);

        return ($isStorkEmailVIAEnabled);
    }

    public function getParamsForStork(): array
    {
        $merchantInfo = $this->getMerchantInfo();
        $data = [
            'otp'                   => $this->otp,
            'merchant_display_name' => $merchantInfo['billing_label'],
            'purpose'               => $this->purpose,
            'logoUrl'               => $merchantInfo['brand_logo'],
            'primary_color'         => $merchantInfo['brand_color'],
        ];

        return [
            'template_name' => self::EMAIL_TEMPLATE,
            'template_namespace' => 'razorpayx_payouts_core',
            'org_id' => $merchantInfo['org_id'],
            'params' => $data
        ];
    }

}
