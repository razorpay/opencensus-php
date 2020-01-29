<?php

namespace RZP\Models\Batch\Processor;

use Carbon\Carbon;
use RZP\Models\Batch;
use RZP\Constants\Timezone;

/*
 * IrctcRefund can process both c type and R type refunds. This class is created to support both old and
 * new Irctc recon flow.
 * 1. C type refunds file is processed through IrctcRefund
 * Delta refunds are processed just like R type refunds
 */

class IrctcDeltaRefund extends IrctcRefund
{
    protected $delimiter = ',';

    protected $ignoreHeaders = true;

    protected function processEntry(array &$entry)
    {
        parent::processEntry($entry);
    }

    protected function postProcessEntries(array &$entries)
    {
        parent::postProcessEntries($entries);

        foreach ($entries as & $entry)
        {
            $status = '6';
            $remarks = $entry[Batch\Header::BANK_REMARKS] ?? 'Not Refunded';

            if ($entry[Batch\Header::STATUS] === Batch\Status::SUCCESS)
            {
                $status = '5';
                $remarks = 'Refunded';
            }

            $entry = [
                Batch\Header::MERCHANT_TXN_ID         => $entry[Batch\Header::MERCHANT_REFERENCE],
                Batch\Header::TRANSACTION_DATE        => $entry[Batch\Header::PAYMENT_DATE],
                Batch\Header::BANK_TRANSACTION_ID     => $entry[Batch\Header::PAYMENT_ID],
                Batch\Header::REFUND_AMOUNT           => $entry[Batch\Header::REFUND_AMOUNT],
                Batch\Header::REFUND_STATUS           => $status,
                Batch\Header::BANK_REMARKS            => $remarks,

                //
                // Refund ID and refund date will not be set in following case.
                // When the refund is created via payment auto refund cron, then receipt is null
                // and thus we could not find the refund entity corresponding to this entry, as
                // query to fetch refund uses receipt number.
                //
                Batch\Header::BANK_ACTUAL_REFUND_DATE => $entry[Batch\Header::REFUND_DATE] ?? null,
                Batch\Header::BANK_REFUND_TXN_ID      => $entry[Batch\Header::REFUND_ID] ?? null,
            ];
        }
    }

    protected function processFinally(& $entry)
    {
        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        $ts = $payment->getCreatedAt();

        $entry[Batch\Header::PAYMENT_DATE] = Carbon::createFromTimestamp($ts, Timezone::IST)->format('Ymd');
    }
}
