<?php

namespace RZP\Jobs\FTS;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;

class FundTransfer extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 10;

    /**
     * @var string
     */
    protected $queueConfigKey = 'fts_fund_transfer';

    protected $id;

    protected $type;

    public function __construct(string $id, string $type)
    {
        $this->id  = $id;

        $this->type = $type;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();

                return;
            }

            parent::handle();

            $this->trace->info(TraceCode::FTS_FUND_TRANSFER,
                [
                    'id'    => $this->id ,
                    "type"  => $this->type,
                ]);

            $ftsResponse = App::getFacadeRoot()['fts_fund_transfer']->requestFundTransfer($this->id, $this->type);

            $this->trace->info(
                TraceCode::FTS_FUND_TRANSFER_SENT,
                $ftsResponse);
        }
        catch (\Throwable $e)
        {

            $data = [
                'id'                => $this->id ,
                'type'              => $this->type,
            ];

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::FTS_FUND_TRANSFER_FAILED,
                $data);
        }
    }
}