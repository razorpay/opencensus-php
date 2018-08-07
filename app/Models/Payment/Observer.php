<?php

namespace RZP\Models\Payment;

use App;
use Cache;
use RZP\Constants\Metric;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\Observer as BaseObserver;

class Observer extends BaseObserver
{
    public function created(Entity $payment)
    {
        $this->pushCreatedMetrics($payment);
    }

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
            throw new Exception\RuntimeException('Entity should be instance of PaymentEntity', [
                'entity' => $entity
            ]);
        }
    }

    protected function pushCreatedMetrics(Entity $payment)
    {
        $metricData = [
            Metric::LABEL_PAYMENT_METHOD                =>  $payment->getMethod(),
            Metric::LABEL_PAYMENT_CURRENCY              =>  $payment->getCurrency(),
            Metric::LABEL_PAYMENT_INTERNATIONAL         =>  $payment->isInternational(),
            Metric::LABEL_PAYMENT_TRANSACTION_TYPE      =>  $payment->getTransactionType(),
            Metric::LABEL_PAYMENT_STATUS                =>  $payment->getStatus().'_'.$payment->getInternalErrorCode(),
        ];

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;

            $cardType = $card->getType();

            $issuer = $card->getIssuer();

            $network = $card->getNetwork();

            $iin = $card->getIin();
        }
        else if (($payment->isNetbanking() === true) or
                ($payment->isEmandate() === true))
        {
            $issuer = $payment->getBank();
        }
        else if ($payment->isWallet() == true)
        {
            $issuer = $payment->getWallet();
        }
        else if ($payment->isUpi() === true)
        {
            $issuer = $payment->getPspFromVpa();
        }

        $metricData += [
            Metric::LABEL_PAYMENT_ISSUER        => $issuer ?? null ,
            Metric::LABEL_CARD_NETWORK          => $network ?? null,
            Metric::LABEL_CARD_IIN              => $iin ?? null,
            Metric::LABEL_CARD_TYPE             => $cardType ?? null,
        ];

        $this->trace->count(Metric::PAYMENT_CREATED, $metricData);

    }
}
