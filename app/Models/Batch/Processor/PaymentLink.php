<?php

namespace RZP\Models\Batch\Processor;

use Carbon\Carbon;

use RZP\Models\Invoice;
use RZP\Models\Batch;

class PaymentLink extends Base
{
    protected function processEntry(array & $entry)
    {
        $input = Batch\Helper\PaymentLink::getEntityInput($entry);

        $invoice = (new Invoice\Core)->create($input, $this->merchant);

        //
        // Update the entry with output values
        //

        $entry[Batch\Header::STATUS]          = Batch\Status::SUCCESS;
        $entry[Batch\Header::PAYMENT_LINK_ID] = $invoice->getPublicId();
    }

    protected function getProcessedMailPayload(): array
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        return [
            'from'       => 'invoices@razorpay.com',
            'from_title' => 'Payment Link',
            'to'         => $this->merchant->getTransactionReportEmail(),
            'subject'    => 'Razorpay | Processed payment link file for ' . $today,
            'body'       => 'Please find attached processed payment link file',
        ];
    }
}
