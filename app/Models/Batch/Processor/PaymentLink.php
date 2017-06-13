<?php

namespace RZP\Models\Batch\Processor;

use Mail;

use RZP\Models\Invoice;
use RZP\Models\Batch;
use RZP\Mail\Batch as BatchMail;

class PaymentLink extends Base
{
    protected function processEntry(array & $entry)
    {
        $input = Batch\Helper\PaymentLink::getEntityInput($entry);

        $invoice = (new Invoice\Core)->create(
                        $input, $this->merchant, null, $this->batch);

        //
        // Update the entry with output values
        //

        $entry[Batch\Header::STATUS]          = Batch\Status::SUCCESS;
        $entry[Batch\Header::PAYMENT_LINK_ID] = $invoice->getPublicId();
    }

    protected function sendProcessedMail()
    {
        $mail = new BatchMail\PaymentLink(
                        $this->batch->toArray(),
                        $this->merchant->toArray(),
                        $this->outputFileLocalPath);

        Mail::send($mail);
    }
}
