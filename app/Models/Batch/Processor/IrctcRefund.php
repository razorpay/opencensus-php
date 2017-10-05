<?php

namespace RZP\Models\Batch\Processor;

use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class IrctcRefund extends Base
{
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
            $params = [
                Payment\Entity::AMOUNT      => $payment->getAmount(),
                Payment\Entity::CURRENCY    => $payment->getCurrency()
            ];

            $paymentProcessor->capture($payment, $params);
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
        }

        $this->batch->setProcessedAmount($processedAmount);
    }

    protected function getFileName($ext)
    {
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        $prefix = 'deltarefund_RZRPAY_';

        if ($this->mode === Mode::TEST)
        {
            $prefix = 'deltarefund_WUATRZRPAY_';
        }

        $name = $prefix . $time . '_V1';

        if (empty($ext) === false)
        {
            $name = $name . '.' . $ext;
        }

        return $name;
    }
}
