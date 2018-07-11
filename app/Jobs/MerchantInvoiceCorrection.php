<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice\Correction;

class MerchantInvoiceCorrection extends Job
{
    const MUTEX_LOCK_TIMEOUT = 3600; // sec

    protected $merchantId;

    protected $month;

    protected $year;

    protected $mutex;

    /**
     * {@inheritDoc}
     */
    protected $queueConfigKey = 'merchant_invoice';

    public $timeout = 3600;

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
            $this->mutex = App::getFacadeRoot()['api.mutex'];

            $this->mutex->acquireAndRelease(
                $this->merchantId,
                function ()
                {
                    $creator = new Correction($this->merchantId, $this->month, $this->year);

                    $creator->calculateAndLogInvoiceCorrection();
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::MERCHANT_INVOICE_CORRECTION_IN_PROGRESS);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                    TraceCode::MERCHANT_INVOICE_CORRECTION_FAILED,
                    [
                        'merchant_id'   => $this->merchantId,
                        'month'         => $this->month,
                        'year'          => $this->year,
                        'mode'          => $this->mode,
                        'message'       => $e->getMessage(),
                    ]);
        }
    }
}
