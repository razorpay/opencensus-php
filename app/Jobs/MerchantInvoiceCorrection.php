<?php

namespace RZP\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice\Correction;

class MerchantInvoiceCorrection extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    protected $merchantId;

    protected $month;

    protected $year;

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

        $this->trace->info(
                TraceCode::MERCHANT_INVOICE_CORRECTION_JOB,
                [
                    'merchant_id'   => $this->merchantId,
                    'month'         => $this->month,
                    'year'          => $this->year,
                    'mode'          => $this->mode,
                ]);

        try
        {
            $creator = new Correction($this->merchantId, $this->month, $this->year);

            $creator->calculateAndLogInvoiceCorrection();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::MERCHANT_INVOICE_CORRECTION_FAILED,
                    [
                        'merchant_id'   => $this->merchantId,
                        'month'         => $this->month,
                        'year'          => $this->year,
                        'mode'          => $this->mode,
                    ]);
        }
    }
}
