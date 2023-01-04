<?php

namespace RZP\Models\Payment;

use App;
use Cache;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\Observer as BaseObserver;
use RZP\Models\Risk\Core as RiskCore;

class Observer extends BaseObserver
{
    public function created(Entity $payment)
    {
        if ((method_exists($payment, 'dualWrite') === true) and ($payment->dualWrite() === true))
        {
            return;
        }

        $this->saveRelatedEntitiesFromMetaData($payment);

        (new Metric)->pushCreateMetrics($payment);
    }

    /**
     * Used to flush the cache on updates, for upi payments
     * as we are caching the status to avoid DB hits`
     * @param  Payment $entity
     */
    public function updated($payment)
    {
        if ((method_exists($payment, 'dualWrite') === true) and ($payment->dualWrite() === true))
        {
            return;
        }

        $this->validateEntity($payment);

        if ($payment->isUpi() === true)
        {
            $key = Entity::getCacheUpiStatusKey($payment->getPublicId());

            try
            {
                Cache::forget($key);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::UPI_CACHE_FLUSH_ERROR,
                    ['key' => $key]);
            }
        }
    }

    protected function validateEntity($entity)
    {
        if (($entity instanceof Entity) === false)
        {
            throw new Exception\RuntimeException('Entity should be instance of Payment Entity',
                [
                    'entity' => $entity
                ]);
        }
    }

    protected function saveRelatedEntitiesFromMetaData(Entity $payment)
    {
        $paymentAnalytics = $payment->getMetaData("payment_analytics");

        if (is_null($paymentAnalytics) === false)
        {
            $this->app['repo']->saveOrFail($paymentAnalytics);
        }

        $riskEntity = $payment->getMetaData("risk_entity");

        if (is_null($riskEntity) === false)
        {
            $source = $riskEntity["source"];
            $riskData = $riskEntity["risk_data"];

            (new RiskCore)->logPaymentForSource($payment, $source, $riskData);
        }

        if ($payment->isUpi() === true)
        {
            $upiMetadata = $payment->getMetadata(UpiMetadata\Entity::UPI_METADATA);

            if ($upiMetadata instanceof UpiMetadata\Entity)
            {
                $this->app['repo']->saveOrFail($upiMetadata);
            }
        }
    }
}
