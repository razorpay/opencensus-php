<?php

namespace RZP\Models\Batch\Processor;

use Carbon\Carbon;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class IrctcRefund extends Base
{
    const FILE_TO_WRITE_NAME        = 'refundvalidation_RZRPAY_BRDS_';

    protected function processEntry(array & $entry)
    {
        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $type = $entry[Batch\Header::REFUND_TYPE];

        $processor = 'process' . studly_case($type) .'TypeRefunds';

        $refund = $this->$processor($entry, $payment);

        $entry[Batch\Header::STATUS]       = Batch\Status::SUCCESS;
        $entry[Batch\Header::REFUND_ID]    = $refund->getPublicId();
        $entry[Batch\Header::REFUND_DATE]  = $this->getRefundDate($refund);
    }

    protected function processRTypeRefunds(array $entry, Payment\Entity $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $input = $this->getRefundParams($entry);

        return $paymentProcessor->createRefundFromMerchantFile($payment, $input, $this->batch);
    }

    protected function processCTypeRefunds(array $entry, Payment\Entity $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        // In case the payment is not captured, we need to capture the payment before initiating the refund
        if ($payment->hasBeenCaptured() === false)
        {
            $amount = $payment->getAmount();

            // The payment amount is inclusive of fees, so we need to capture with the original amount.
            if ($payment->merchant->isFeeBearerCustomer() === true)
            {
                $amount = $amount - $payment->getFee();
            }

            $params = [
                Payment\Entity::AMOUNT      => $amount,
                Payment\Entity::CURRENCY    => $payment->getCurrency()
            ];

            $payment = $paymentProcessor->capture($payment, $params);
        }

        $input = $this->getRefundParams($entry);

        $input[Refund\Entity::AMOUNT]  = intval($entry[Batch\Header::REFUND_AMOUNT] * 100);

        return $paymentProcessor->createRefundFromMerchantFile($payment, $input, $this->batch);
    }

    protected function getRefundDate(Refund\Entity $refund)
    {
        $ts = $refund->getCreatedAt();

        // Format dd/mm/yyyy hh:mm,
        $refundDate = Carbon::createFromTimestamp($ts, Timezone::IST)
                          ->format('Ymd');

        return $refundDate;
    }

    protected function getRefundParams(array $entry)
    {
        $input = [
            Refund\Entity::RECEIPT => $entry[Batch\Header::CANCELLATION_ID] . '_' . $entry[Batch\Header::MERCHANT_REFERENCE],
            Refund\Entity::NOTES   => [
                'reservation_id'    => $entry[Batch\Header::MERCHANT_REFERENCE],
                'cancellation_id'   => $entry[Batch\Header::CANCELLATION_ID],
                'cancellation_date' => $entry[Batch\Header::CANCELLATION_DATE],
                'refund_type'       => $entry[Batch\Header::REFUND_TYPE],
            ],
        ];

        return $input;
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
                $processedAmount += $entry[Batch\Header::REFUND_AMOUNT];
            }

            unset($entry[Batch\Header::ERROR_DESCRIPTION]);
        }

        $this->batch->setProcessedAmount($processedAmount);
    }

    /**
     * File name format/example: refundvalidation_RZRPAY__BRDS_20171212_V1
     *
     * @param string|null $ext
     *
     * @return string
     */
    protected function getFileName(string $ext = null): string
    {
        $time = Carbon::yesterday(Timezone::IST)->format('Ymd');

        $prefix = self::FILE_TO_WRITE_NAME;

        $name = $prefix . $time . '_V1';

        if (empty($ext) === false)
        {
            $name = $name . '.' . $ext;
        }

        return $name;
    }
}
