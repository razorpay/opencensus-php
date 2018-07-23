<?php

namespace RZP\Models\Payment;

use Cache;
use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\Observer as BaseObserver;

class Observer extends BaseObserver
{
    /**
     * Used to flush the cache on updates, for upi payments
     * as we are caching the status to avoid DB hits`
     * @param  Payment $entity
     */
    public function updated($payment)
    {
        $this->validateEntity($payment);

        if ($payment->isUpi() === true)
        {
            $trace = App::getFacadeRoot()['trace'];

            $key = Entity::getCacheUpiStatusKey($payment->getPublicId());

            try
            {
                Cache::forget($key);
            }
            catch (\Throwable $e)
            {
                $trace->traceException(
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
            throw new Exception\RuntimeException('Entity should be instance of PaymentEntity', [
                'entity' => $entity
            ]);
        }
    }
}
