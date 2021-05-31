<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Partner\Commission\Invoice;

class CommissionInvoiceGenerate extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    public $timeout = 1000;

    /**
     * @var array
     */
    protected $data;

    public function __construct(string $mode, array $data)
    {
        parent::__construct($mode);

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::COMMISSION_INVOICE_GENERATE_JOB_REQUEST,
                [
                    'mode'   => $this->mode,
                    'data'   => $this->data,
                ]);

            $summary = [
                'failed_ids'    => [],
                'failed_count'  => 0,
                'success_count' => 0,
            ];

            foreach ($this->data['merchant_ids'] as $merchantId)
            {
                try
                {
                    $partner = $this->repoManager->merchant->findOrFailPublic($merchantId);

                    (new Invoice\Core)->generateInvoice($partner, $this->data);

                    $summary['success_count']++;
                }
                catch (\Throwable $e)
                {
                    $summary['failed_count']++;
                    $summary['failed_ids'][] = $merchantId;

                    $this->trace->traceException($e, Trace::ERROR, TraceCode::COMMISSION_INVOICE_GENERATE_ERROR, ['id' => $merchantId]);
                }
            }

            $this->trace->info(TraceCode::COMMISSION_INVOICE_GENERATE_SUMMARY, $summary);

            // if there are any failed ids, we will retry
            if ($summary['failed_count'] > 0)
            {
                $this->checkRetry();
            }
            else
            {
                $this->delete();
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_INVOICE_GENERATE_JOB_ERROR,
                [
                    'mode'   => $this->mode,
                    'data'   => $this->data,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(
                TraceCode::COMMISSION_INVOICE_GENERATE_QUEUE_DELETE,
                [
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
