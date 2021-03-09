<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Invoice\Core;

class MerchantInvoiceBackFill extends Job
{
    protected $merchantId;

    protected $month;

    protected $year;

    const MERCHANT_INVOICE_BACK_FILL_MUTEX = 'MERCHANT_INVOICE_BACKFILL_MUTEX_%s_%s_%s';

    const MUTEX_LOCK_TIMEOUT = 900;

    protected $queueConfigKey = 'pg_einvoice';

    public function __construct($merchantId, $month, $year, $mode)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

        $this->month = $month;

        $this->year = $year;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::MERCHANT_BACK_FILL_PG_INVOICE_PDF_INIT,
            [
                'merchant_id' => $this->merchantId,
                'month'       => $this->month,
                'year'        => $this->year,
            ]);

        $resource = sprintf(self::MERCHANT_INVOICE_BACK_FILL_MUTEX, $this->merchantId, $this->month, $this->year);

        try
        {
            $result = $this->mutex->acquireAndRelease(
                $resource,
                function ()
                {
                    return (new Core())->createPgMerchantInvoicePdf([$this->merchantId], $this->month, $this->year);
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
            );

            $this->trace->info(
                TraceCode::MERCHANT_BACK_FILL_PG_INVOICE_PDF_SUCCESS,
                [
                    'merchant_id' => $this->merchantId,
                    'month'       => $this->month,
                    'year'        => $this->year,
                    'result'      => $result,
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                TraceCode::MERCHANT_BACK_FILL_PG_INVOICE_PDF_FAILED,
                [
                    'merchant_id' => $this->merchantId,
                    'month'       => $this->month,
                    'year'        => $this->year,
                ]);
        }
    }
}
