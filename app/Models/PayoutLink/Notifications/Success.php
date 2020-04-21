<?php

namespace RZP\Models\PayoutLink\Notifications;

use App;
use Mail;

use RZP\Trace\TraceCode;
use RZP\Models\PayoutLink\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\PayoutLink\Success as SuccessMail;

/**
 * Class CustomerSuccess
 *
 * The notification to the customer when the payout was successful
 */
class Success extends Base
{
    const SMS_TEMPLATE = 'sms.payout_link.success';

    protected $raven;

    protected $payoutLink;

    protected $trace;

    public function __construct(Entity $payoutLink)
    {
        parent::__construct();

        $this->payoutLink = $payoutLink;

        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->raven = $app['raven'];
    }

    protected function sendSms()
    {
        if (($this->payoutLink->shouldSendSms() === false) or
            (empty($this->payoutLink->getContactPhoneNumber())))
        {
            return;
        }

        $this->trace->info(TraceCode::PAYOUT_LINK_SENDING_LINK_SMS,
                           [
                               'payout_link_id' => $this->payoutLink->getPublicId()
                           ]);

        $payload = $this->getSmsPayload();

        try
        {
            $this->raven->sendSms($payload);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_LINK_RAVEN_SMS_FAILED,
                [
                    'payout_link_id' => $this->payoutLink->getPublicId()
                ]);
        }
    }

    protected function getSmsPayload()
    {
        $merchant = $this->payoutLink->merchant;

        $payload = [
            Entity::PARAMS   => [
                'merchant_display_name' => $merchant->getBillingLabel(),
                'purpose'               => $this->payoutLink->getPurpose(),
                'utr'                   => $this->payoutLink->payout()->getUtr(),
                'amount'                => $this->payoutLink->getFormattedAmount()
            ],
            Entity::TEMPLATE => self::SMS_TEMPLATE,
            Entity::SOURCE   => Entity::API_POUT_LNK_SRC,
            Entity::RECEIVER => $this->payoutLink->getContactPhoneNumber()
        ];

        return $payload;
    }

    protected function sendEmail()
    {
        if (($this->payoutLink->shouldSendEmail() === false) or
            (empty($this->payoutLink->getContactEmail())))
        {
            return;
        }

        $this->trace->info(TraceCode::PAYOUT_LINK_SUCCESS_EMAIL,
                           [
                               'payout_link_id' => $this->payoutLink->getPublicId()
                           ]);

        $successMail = new SuccessMail($this->payoutLink->getPublicId());

        Mail::queue($successMail);
    }


}
