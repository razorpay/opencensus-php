<?php

namespace RZP\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Merchant\Invoice\Processor;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class MerchantInvoice extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    protected $merchantId;

    protected $month;

    protected $year;

    protected $isCorrection;

    public function __construct(
        string $merchantId,
        int $month,
        int $year,
        string $mode,
        bool $isCorrection)
    {
        parent::__construct($mode);

        $this->merchantId   = $merchantId;

        $this->month        = $month;

        $this->year         = $year;

        $this->isCorrection = $isCorrection;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $creator = new Processor($this->merchantId, $this->month, $this->year);

            $creator->createInvoiceEntities($this->isCorrection);
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
