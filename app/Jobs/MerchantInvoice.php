<?php

namespace RZP\Jobs;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice\Processor;

class MerchantInvoice extends Job
{
    protected $merchantId;

    protected $month;

    protected $year;

    public $timeout = 1800;

    const MERCHANT_INVOICE_MUTEX_RESOURCE = 'MERCHANT_INVOICE_CREATE_%s_%s_%s';

    const MUTEX_LOCK_TIMEOUT = 1800;

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

            $resource = sprintf(self::MERCHANT_INVOICE_MUTEX_RESOURCE, $this->merchantId, $this->month, $this->year);

            $this->mutex->acquireAndRelease(
                $resource,
                function () use ($creator)
                {
                    $creator->createInvoiceEntities();
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
