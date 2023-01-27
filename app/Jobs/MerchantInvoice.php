<?php

namespace RZP\Jobs;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Invoice\Processor;

class MerchantInvoice extends Job
{
    protected $merchantId;

    protected $month;

    protected $year;

    // this is the Key that is passed to processor under which the results are stored in cache this is unique for a merchant per month per year
    protected $cacheTag;

    public $timeout = 5400;

    const MERCHANT_INVOICE_MUTEX_RESOURCE = 'MERCHANT_INVOICE_CREATE_%s_%s_%s';

    const CACHE_TAG_RESOURCE = 'merchant_invoice_%s_%s_%s_%s';

    const MUTEX_LOCK_TIMEOUT = 5400;

    const MAX_ALLOWED_ATTEMPTS = 5;

    // interval will be in seconds
    const RETRY_INTERVAL = 900;

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

        $this->cacheTag     = sprintf(self::CACHE_TAG_RESOURCE, $mode, $merchantId, $month, $year);
    }

    public function handle()
    {
        parent::handle();

        try {
            $this->trace->info(
                TraceCode::MERCHANT_INVOICE_ENTITY_PRE_MUTEX_DEBUG,
                [
                    'merchant_id' => $this->merchantId,
                    'month' => $this->month,
                    'year' => $this->year,
                ]);
            $creator = new Processor($this->merchantId, $this->month, $this->year, $this->cacheTag);

            $resource = sprintf(self::MERCHANT_INVOICE_MUTEX_RESOURCE, $this->merchantId, $this->month, $this->year);

            $this->mutex->acquireAndRelease(
                $resource,
                function () use ($creator) {
                    $creator->createInvoiceEntities();
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
            );

            //removing data from cache in case of success
            if(!empty($cacheKeyArr[$this->cacheTag]))
            {
                foreach ($cacheKeyArr[$this->cacheTag] as $item)
                {
                    $this->cache->tags($this->cacheTag)->forget($item);
                }
                unset($cacheKeyArr[$this->cacheTag]);
            }
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
            $this->trace->info(
                TraceCode::MERCHANT_INVOICE_ENTITY_POST_MUTEX_DEBUG,
                [
                    'merchant_id' => $this->merchantId,
                    'month'       => $this->month,
                    'year'        => $this->year,
                ]);
        }
    }
    /**
     * This method override the parent method if the queue job timeout being observed in the Job
     * check no of attempts not exceeds max_allowed_attempts then
     * release the job back into the queue else delete from queue
     */
    protected function beforeJobKillCleanUp()
    {
        if($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->trace->info(
                TraceCode::MERCHANT_INVOICE_RETRY_QUEUE_JOB_TIMEOUT,
                [
                    'merchant_id' => $this->merchantId,
                    'month'       => $this->month,
                    'year'        => $this->year,
                    'attempt'     => $this->attempts()
                ]);

            $this->release(self::RETRY_INTERVAL);
        }
        else
        {
            $this->trace->info(
                TraceCode::MERCHANT_INVOICE_CREATE_MESSAGE_DELETE,
                [
                    'merchant_id' => $this->merchantId,
                    'month'       => $this->month,
                    'year'        => $this->year,
                ]);

            $this->delete();
        }

        parent::beforeJobKillCleanUp();
    }
}
