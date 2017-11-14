<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Invoice;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Helpers;

class PaymentLink extends Base
{
    /**
     * @var Invoice\Core
     */
    protected $invoiceCore;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->invoiceCore = new Invoice\Core;
    }

    protected function processEntry(array & $entry)
    {
        $input = Helpers\PaymentLink::getEntityInput($entry, $this->params);

        $invoice = $this->invoiceCore->create($input, $this->merchant, null, $this->batch);

        // Update the entry with output values

        $entry[Header::STATUS]              = $invoice->getStatus();
        $entry[Header::PAYMENT_LINK_ID]     = $invoice->getPublicId();
        $entry[Header::SHORT_URL]           = $invoice->getShortUrl();
    }

    /**
     * Overrides: We don't set amount aggregate as it crosses MySQL limit
     * in case of batch payment link inputs.
     *
     * @param array $entries
     */
    protected function fillBatchEntityWithInputFileDetails(array $entries)
    {
        $totalCount  = count($entries);

        $this->batch->setTotalCount($totalCount);
    }
}
