<?php

namespace RZP\Models\Payment\Fraud\Notifications;

use App;
use Mail;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payment\Fraud\Constants\Notification as Constants;

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
        if ($this->shouldNotify(Constants::EMAIL) === true)
        {
            $this->emailMerchant();
        }

        if ($this->shouldNotify(Constants::SMS) === true)
        {
            $this->smsMerchant();
        }
    }

    private function emailMerchant()
    {
        $data = $this->getEmailData();

        if (is_null($data) === false)
        {
            $mailer = $this->config->getEmailHandler();

            $this->trace->info(
                TraceCode::FRAUD_NOTIFICATION_EMAIL_SENDING,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'payment_id'  => $this->payment->getId(),
                    'fraud_type'  => $this->config->getFraudType(),
                ]);

            Mail::queue(new $mailer($data));
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
}
