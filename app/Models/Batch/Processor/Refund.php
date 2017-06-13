<?php

namespace RZP\Models\Batch\Processor;

use Mail;

use RZP\Models\Batch;
use RZP\Mail\Batch as BatchMail;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Refund extends Base
{
    protected function processEntry(array & $entry)
    {
        $paymentId = $entry[Batch\Header::PAYMENT_ID];
        $amount    = $entry[Batch\Header::AMOUNT];

        $payment = $this->repo->payment->findByPublicIdAndMerchant(
                                            $paymentId,
                                            $this->merchant);

        $paymentProcessor = (new PaymentProcessor($this->merchant));

        $refund = $paymentProcessor->refundPaymentViaBatchEntry(
                                        $payment,
                                        $this->batch,
                                        $amount);

        //
        // Update the entry with output values
        //

        $entry[Batch\Header::REFUND_ID]         = $refund->getPublicId();
        $entry[Batch\Header::REFUNDED_AMOUNT]   = $refund->getAmount();
        $entry[Batch\Header::STATUS]            = Batch\Status::SUCCESS;
        $entry[Batch\Header::ERROR_CODE]        = null;
        $entry[Batch\Header::ERROR_DESCRIPTION] = null;
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
                $processedAmount += $entry[Batch\Header::REFUNDED_AMOUNT];
            }
        }

        $this->batch->setProcessedAmount($processedAmount);
    }

    protected function sendProcessedMail()
    {
        $mail = new BatchMail\Refund(
                        $this->batch->toArray(),
                        $this->merchant->toArray(),
                        $this->outputFileLocalPath);

        Mail::send($mail);
    }
}
