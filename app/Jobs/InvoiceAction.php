<?php

namespace RZP\Jobs;

use App;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Invoice;

class InvoiceAction extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $event;
    protected $invoice;

    protected $core;

    public function __construct($event, Invoice\Entity $invoice)
    {
        $this->event   = $event;

        $this->invoice = $invoice;
    }

    public function handle()
    {
        $this->init();

        /**
         * TODO:
         * - Add logic to handle max retry
         * - Put logs
         */

        $handler = 'handle' . studly_case($this->event);

        if (method_exists($this, $handler) === false)
        {
            return;
        }

        $this->$handler();
    }

    protected function init()
    {
        $this->core = new Invoice\Core;
    }

    protected function handleIssued()
    {
        (new Invoice\Notifier($this->invoice))->notifyInvoiceIssuedToCustomer();
    }

    protected function handleExpired()
    {
        (new Invoice\Notifier($this->invoice))->notifyInvoiceExpiredToCustomer();
    }
}
