<?php

namespace RZP\Jobs;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice\Core;

class AdjustmentInvoiceEntityCreate extends Job
{
    protected $merchantId;

    protected $month;

    protected $year;

    protected $amount;

    protected $tax;

    protected $description;

    protected $balanceId;

    protected $force;

    const ADJUSTMENT_INVOICE_MUTEX_RESOURCE = 'ADJUSTMENT_INVOICE_CREATE_%s_%s_%s';

    protected $queueConfigKey = 'merchant_invoice';

    public $timeout = 60;

    const MUTEX_LOCK_TIMEOUT = 60;

    public function __construct(
        string $merchantId,
        int $month,
        int $year,
        int $amount,
        int $tax,
        string $description,
        string $balanceId,
        bool $force,
        string $mode)
    {
        parent::__construct($mode);

        $this->merchantId   = $merchantId;

        $this->month        = $month;

        $this->year         = $year;

        $this->amount       = $amount;

        $this->tax          = $tax;

        $this->description  = $description;

        $this->balanceId    = $balanceId;

        $this->force        = $force;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::ADJUSTMENT_INVOICE_ENTITY_CREATION_REQUEST,
                [
                    'merchant_id' => $this->merchantId,
                    'month'       => $this->month,
                    'year'        => $this->year,
                    'amount'      => $this->amount,
                    'tax'         => $this->tax,
                    'description' => $this->description,
                    'balance_id'  => $this->balanceId,
                    'force'       => $this->force,
                ]);

            $resource = sprintf(self::ADJUSTMENT_INVOICE_MUTEX_RESOURCE, $this->merchantId, $this->month, $this->year);

            $this->mutex->acquireAndRelease(
                $resource,
                function ()
                {
                    return (new Core())->createMultipleInvoiceEntities($this->merchantId, $this->month, $this->year,
                                                                        $this->amount, $this->tax, $this->description,
                                                                        $this->balanceId, $this->force);
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
            );
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::ADJUSTMENT_INVOICE_ENTITY_CREATION_FAILED,
                [
                    'merchant_id'   => $this->merchantId,
                    'month'         => $this->month,
                    'year'          => $this->year,
                    'mode'          => $this->mode,
                ]);
        }
    }
}
