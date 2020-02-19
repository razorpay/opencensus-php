<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice\Processor;

class MerchantInvoice extends Job
{
    protected $merchantId;

    protected $month;

    protected $year;

    public $timeout = 1800;

    public function __construct(
        string $merchantId,
        int $month,
        int $year,
        string $mode)
    {
        parent::__construct($mode);

        $this->merchantId   = $merchantId;

        $this->month        = $month;

        $this->year         = $year;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $creator = new Processor($this->merchantId, $this->month, $this->year);

            $creator->createInvoiceEntities();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_FAILED,
                [
                    'merchant_id'   => $this->merchantId,
                    'month'         => $this->month,
                    'year'          => $this->year,
                    'mode'          => $this->mode,
                ]);
        }
        finally
        {
            $this->delete();
        }
    }
}
