<?php

namespace RZP\Models\Batch\Processor;

use RZP\Error\ErrorCode;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class IrctcSettlement extends Base
{
    const IRCTC_PAYMENT_METHODS = ['NB', 'CC', 'DC', 'PPI', 'EMI', 'UPI', 'NA']; // list of payment methods required by IRCTC.

    protected function processEntry(array & $entry)
    {
        $paymentId = str_replace("\xEF\xBB\xBF", '',  $entry[Batch\Header::PAYMENT_ID]);

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        $paymentProcessor = (new PaymentProcessor($this->merchant));

        $amount = $payment->getAmount();

        // The payment amount is inclusive of fees, so we need to capture with the original amount.
        if ($payment->isFeeBearerCustomer() === true)
        {
            $amount = $amount - $payment->getFee();
        }

        $params = [
            Payment\Entity::AMOUNT   => $amount,
            Payment\Entity::CURRENCY => $payment->getCurrency()
        ];

        $resource = $payment->getId() . "_" . "transaction";
        $mutex_timeout = 5;
        $retry_count  = 3;
        $min_retry_delay = 100;
        $max_retry_delay = 300;

        // Prevent duplicate transactions with mutex lock.
        $this->mutex->acquireAndRelease(
            $resource,

            function () use ($payment,$params,$paymentProcessor){

                // We do not capture the payment if its already refunded
                if (($payment->isPartiallyOrFullyRefunded() === false) and
                    ($payment->hasBeenCaptured() === false))
                {
                    if ($payment->isExternal() === true) {
                        $this->app['pg_router']->paymentCapture($payment->getId(), $params, true);
                    } else {
                        $paymentProcessor->capture($payment, $params);
                    }

                }},

            $mutex_timeout,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            $retry_count,
            $min_retry_delay,
            $max_retry_delay,
 );


        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    /**
     * Besides what parent's method does:
     * - Sets aggregate processed amount of batch entity.
     *
     * @param $entries
     */
    protected function postProcessEntries(array & $entries)
    {
        parent::postProcessEntries($entries);

        $processedAmount = 0;

        foreach ($entries as $entry)
        {
            if ($entry[Batch\Header::STATUS] === Batch\Status::SUCCESS)
            {
                $processedAmount += $entry[Batch\Header::PAYMENT_AMOUNT];
            }
        }

        $this->batch->setProcessedAmount($processedAmount);
    }

    /**
     * Will not mark batch as processed in case of failures,
     * even if there is a single failure, batch will go to partially processed state.
     *
     * @return bool
     */
    protected function shouldMarkProcessedOnFailures(): bool
    {
        return false;
    }

    /**
     * @param $headings
     * @param $values
     * @param $ix
     * @return array|false|void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * Overriding this function because new Irctc settlement file contains additional column.
     * To support old and new format, popping the additional column as it is not being used.
     */
    protected function parseTextRowWithHeadingMismatch($headings, $values, $ix)
    {
        if ((count($values) > count($headings)) and ((count($values) - count($headings)) === 1))
        {
            if (in_array(end($values), self::IRCTC_PAYMENT_METHODS) === true)
            {
                array_pop($values);
                return array_combine($headings, $values);
            }
        }
        return parent::parseTextRowWithHeadingMismatch($headings, $values, $ix);
    }
}
