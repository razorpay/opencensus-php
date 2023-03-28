<?php

namespace RZP\Models\Batch\Processor;

use Config;
use Carbon\Carbon;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class IrctcRefund extends Base
{
    protected function processEntry(array & $entry)
    {
        $entry[Batch\Header::MERCHANT_REFERENCE] = str_replace("\xEF\xBB\xBF", '',  $entry[Batch\Header::MERCHANT_REFERENCE]);

        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        $type = $entry[Batch\Header::REFUND_TYPE];

        $this->validateEntry($entry);

        $processor = 'process' . studly_case($type) .'TypeRefunds';

        $refund = $this->$processor($entry, $payment);

        $entry[Batch\Header::STATUS]      = Batch\Status::SUCCESS;
        $entry[Batch\Header::REFUND_ID]   = $refund->getPublicId();
        $entry[Batch\Header::REFUND_DATE] = $this->getRefundDate($refund);
    }

    protected function processRTypeRefunds(array & $entry, Payment\Entity $payment)
    {
        $paymentProcessor = (new PaymentProcessor($this->merchant));

        $input = $this->getRefundParams($entry);

        $refund = $this->repo->refund->findByReceiptAndMerchant($input[Refund\Entity::RECEIPT], $this->merchant->getId());

        if ($refund !== null)
        {
            return $refund;
        }

        $refundAmount = $this->getFormattedAmount($entry[Batch\Header::REFUND_AMOUNT]);

        $paymentAmount = $this->getFormattedAmount($entry[Batch\Header::PAYMENT_AMOUNT]);

        if ($refundAmount === $paymentAmount)
        {
            return $paymentProcessor->createRefundFromMerchantFile($payment, $input, $this->batch);
        }
        else
        {
            $entry[Batch\Header::BANK_REMARKS] = 'Rejected - Amount Mismatch';

            $this->sendSlackNotification($refundAmount, $paymentAmount);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCORRECT_AMOUNT_SENT_FOR_FULL_REFUND);
        }
    }

    protected function processCTypeRefunds(array $entry, Payment\Entity $payment)
    {
        $paymentProcessor = (new PaymentProcessor($this->merchant));

        // In case the payment is not captured, we need to capture the payment before initiating the refund
        if ($payment->hasBeenCaptured() === false)
        {
            $amount = $payment->getAmount();

            // The payment amount is inclusive of fees, so we need to capture with the original amount.
            if ($payment->isFeeBearerCustomer() === true)
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

        $refund = $this->repo->refund->findByReceiptAndMerchant($input[Refund\Entity::RECEIPT], $this->merchant->getId());

        if ($refund !== null)
        {
            return $refund;
        }

        return $paymentProcessor->createRefundFromMerchantFile($payment, $input, $this->batch);
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

        foreach ($entries as & $entry)
        {
            if ($entry[Batch\Header::STATUS] === Batch\Status::SUCCESS)
            {
                $processedAmount += $entry[Batch\Header::REFUND_AMOUNT];

                // In case success, we want last column should be `success`
                // Else a proper error description would be set.
                $entry[Batch\Header::ERROR_DESCRIPTION] = 'Success';
            }

            unset($entry[Batch\Header::ERROR_CODE]);
        }

        $this->batch->setProcessedAmount($processedAmount);
    }

    public function getFormattedAmount($amt)
    {
        $formattedAmt = floatval($amt) * 100;

        return intval(number_format($formattedAmt, 2, '.', ''));
    }

    /**
     * Sends slack notification for this alert to appropriate channel
     * @param $expectedAmount
     * @param $givenAmount
     */
    public function sendSlackNotification($refundAmount, $paymentAmount)
    {
        $this->app['slack']->queue(
                                    TraceCode::IRCTC_REFUND_AMOUNT_MISMATCH,
                                    [
                                        'message'        => 'Rejected - Amount Mismatch IRCTC Refund',
                                        'refund_amount'  => $refundAmount,
                                        'payment_amount' => $paymentAmount,
                                    ],
                                    [
                                        'channel'   => Config::get('slack.channels.ops_irctc'),
                                    ]);
    }

    // Check if the Merchant Reference and Cancellation ID are numeric
    protected function validateEntry(array $entry)
    {
        if (!is_numeric($entry[Batch\Header::MERCHANT_REFERENCE]) || !is_numeric($entry[Batch\Header::CANCELLATION_ID])){
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCORRECT_RESERVATION_OR_CANCELLATION_ID_FOR_REFUND);
        }
    }
}
