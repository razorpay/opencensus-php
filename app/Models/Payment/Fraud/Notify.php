<?php

namespace RZP\Models\Payment\Fraud;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Payment\Fraud\Notifications\Config;
use RZP\Models\Payment\Fraud\Constants\Notification as Constants;

class Notify
{
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    public function notifyMerchantIfNeeded(MerchantEntity $merchant, PaymentEntity $payment, string $errCode)
    {
        try
        {
            $fraudType = Constants::getFraudTypeByErrorCode($errCode);

            if (is_null($fraudType) === true)
            {
                return;
            }

            $settings = Constants::getSettingsForFraudType($fraudType);

            if (is_null($settings) === true)
            {
                return;
            }

            $handler = Constants::getHandlerForFraudType($fraudType);

            if (is_null($handler) === true)
            {
                return;
            }

            $config = new Config($fraudType, $settings);

            (new $handler($merchant, $payment, $config))->notifyMerchant();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::FRAUD_NOTIFICATION_FAILED);
        }
    }
}
