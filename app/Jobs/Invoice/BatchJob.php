<?php

namespace RZP\Jobs\Invoice;

use RZP\Jobs\Invoice\Job as InvoiceJob;

/**
 * This class overrides the queue name and job name so that batch invoice jobs
 * burst traffic doesn't delay ongoing invoice jobs
 *
 * Class BatchIssuer
 * @package RZP\Jobs\Invoice
 */
class BatchJob extends InvoiceJob
{
    protected $jobName = 'batch_invoice_issue';

    protected $queueConfigKey = 'merchant_invoice';
}
