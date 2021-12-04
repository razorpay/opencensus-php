<?php

namespace RZP\Models\Payment\Fraud\Notifications;

use App;
use Mail;

use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Merchant\RiskMobileSignupHelper;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payment\Fraud\Constants\Notification as Constants;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;

abstract class Base
{
    /**
     * @var MerchantEntity
     */
    protected $merchant;

    /**
     * @var PaymentEntity
     */
    protected $payment;

    protected $app;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var Trace
     */
    protected $trace;

    protected $config;

    protected $merchantFreshdeskTicket;

    /**
     * @param MerchantEntity $merchant
     * @param PaymentEntity $payment
     * @param Config $config
     */
    public function __construct(MerchantEntity $merchant, PaymentEntity $payment, Config $config)
    {
        $this->app = App::getFacadeRoot();
        $this->mode = $this->app['rzp.mode'];
        $this->trace = $this->app['trace'];

        $this->merchant = $merchant;
        $this->payment = $payment;

        $this->config = $config;
    }

    public function notifyMerchant()
    {
        if ($this->shouldNotify(Constants::FRESHDESK_TICKET) === true)
        {
            $this->createFreshdeskTicketForMerchant();
        }

        if ($this->shouldNotify(Constants::EMAIL) === true)
        {
            $this->emailMerchant();
        }

        if ($this->shouldNotify(Constants::SMS) === true)
        {
            $this->smsMerchant();
        }

        if ($this->shouldNotify(Constants::WHATSAPP) === true)
        {
            $this->sendWhatsappToMerchant();
        }
    }

    private function createFreshdeskTicketForMerchant()
    {
        [$mailBody, $mailSubject, $emailPayload, $requestParams] = $this->getFreshdeskTicketData();

        (new RiskMobileSignupHelper())->createFdTicket($this->merchant,
                                                       $mailBody,
                                                       $mailSubject,
                                                       $emailPayload,
                                                       $requestParams);
    }

    private function sendWhatsappToMerchant()
    {
        $whatsAppPayload = [
            'ownerId'       => $this->merchant->getId(),
            'ownerType'     => 'merchant',
            'template_name' => $this->config->getWhatsappTemplateName(),
            'params'        => $this->getWhatsappData(),
        ];

        (new Stork)->sendWhatsappMessage(
            $this->mode,
            $this->config->getWhatsappTemplate(),
            $this->merchant->merchantDetail->getContactMobile(),
            $whatsAppPayload
        );
    }

    private function emailMerchant()
    {
        $data = $this->getEmailData();

        if (is_null($data) === false)
        {
            $mailer = $this->config->getEmailHandler();

            $provider = $this->config->getEmailProvider();

            $this->trace->info(
                TraceCode::FRAUD_NOTIFICATION_EMAIL_SENDING,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'payment_id'  => $this->payment->getId(),
                    'fraud_type'  => $this->config->getFraudType(),
                ]);

            if ($provider === Constants::FRESHDESK)
            {
                $this->app['freshdesk_client']->sendOutboundEmail(
                    $data, FreshdeskConstants::URLIND);
            }
            else
            {
                Mail::queue(new $mailer($data));
            }
        }
    }

    private function smsMerchant()
    {
        $data = $this->getSmsData();

        if (is_null($data) === false)
        {
            $data['template'] = $this->config->getSmsTemplate();

            $this->trace->info(
                TraceCode::FRAUD_NOTIFICATION_SMS_SENDING,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'payment_id'  => $this->payment->getId(),
                    'fraud_type'  => $this->config->getFraudType(),
                ]);

            $this->app->raven->sendSms($data);
        }
    }

    private function shouldNotify(string $channel): bool
    {
        $notifyIntervalInSecs = 0;

        $config = $this->config;

        $fraudType = $config->getFraudType();

        switch ($channel)
        {
            case Constants::EMAIL:
                if ($config->isEmailEnabled() === false)
                {
                    return false;
                }

                if ($config->emailInstantly() === true)
                {
                    return true;
                }

                $notifyIntervalInSecs = $config->getEmailInterval();

                break;

            case Constants::SMS:
                if ($config->isSmsEnabled() === false)
                {
                    return false;
                }

                if ($config->smsInstantly() === true)
                {
                    return true;
                }

                $notifyIntervalInSecs = $config->getSmsInterval();

                break;

            case Constants::WHATSAPP:
                if ($config->isWhatsappEnabled() === false)
                {
                    return false;
                }

                if ($config->whatsappInstantly() === true)
                {
                    return true;
                }

                $notifyIntervalInSecs = $config->getWhatsappInterval();

                break;

            case Constants::FRESHDESK_TICKET:
                if ($config->isFreshdeskTicketEnabled() === false)
                {
                    return false;
                }

                if ($config->freshdeskTicketInstantly() === true)
                {
                    return true;
                }

                $notifyIntervalInSecs = $config->getFreshdeskTicketInterval();

                break;

            default:
                return false;
        }

        $redis = $this->app['redis']->connection();

        $key = sprintf(Constants::FRAUD_NOTIFICATION_REDIS_KEY, $fraudType, $channel, $this->merchant->getId());

        $redisRes = $redis->set($key, 1, 'ex', $notifyIntervalInSecs, 'nx');

        if ($redisRes === null)
        {
            // if this key was set in last notifyIntervalInSecs seconds, redis will return null. In that case do not trigger event.
            return false;
        }

        return true;
    }

    abstract protected function getSmsData();

    abstract protected function getEmailData();

    abstract protected function getWhatsappData();

    abstract protected function getFreshdeskTicketData();
}
