<?php

namespace RZP\Models\PayoutLink\Notifications;

use App;
use Mail;

use RZP\Trace\TraceCode;
use RZP\Models\PayoutLink\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\PayoutLink\SendLink as SendLinkMail;

/**
 * Class SendLink
 * This class will take the payout link and send link to its customers
 * via Email and Sms
 */
class SendLink extends Base
{
    const SMS_TEMPLATE = 'sms.payout_link.send_link';

    protected $raven;

    protected $payoutLink;

    public function __construct(Entity $payoutLink)
    {
        parent::__construct();

        $this->payoutLink = $payoutLink;

        $this->raven = $this->app['raven'];
    }

    protected function sendSms()
    {
        if (($this->payoutLink->shouldSendSms() === false) or
            (empty($this->payoutLink->getContactPhoneNumber())))
        {
            return;
        }

        $payload = $this->getSmsPayload();

        $this->trace->info(TraceCode::PAYOUT_LINK_SENDING_LINK_SMS,
                           [
                               'payout_link_id' => $this->payoutLink->getPublicId(),
                           ]);

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
                    'payout_link_id' => $this->payoutLink->getPublicId(),
                    'payload'        => $payload
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
                'short_url'             => $this->payoutLink->getShortUrl(),
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

        $this->trace->info(TraceCode::PAYOUT_LINK_SENDING_LINK_EMAIL,
                           [
                               'payout_link_id' => $this->payoutLink->getPublicId()
                           ]);

        $sendLinkMail = new SendLinkMail($this->payoutLink->getPublicId());

        Mail::queue($sendLinkMail);
    }
}
