<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\Settlement\Ondemand;
use Razorpay\Trace\Logger as Trace;

class PartialScheduledSettlementJob extends Job
{
    protected $mode;

    public $timeout = 7200;

    const LIMIT = 400;

    public function __construct($mode)
    {
        parent::__construct($mode);

        $this->mode = $mode;
    }

    public function handle()
    {
        parent::handle();

        RuntimeManager::setMemoryLimit('4096M');

        RuntimeManager::setTimeLimit($this->timeout);

        RuntimeManager::setMaxExecTime($this->timeout);

        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_JOB);

        try
        {
            $offset = 0;

            $i = 0;

            while (true)
            {
                $merchantIds = $this->repoManager
                                    ->feature
                                    ->fetchMerchantIdsWithFeatureInChunks(Feature\Constants::ES_AUTOMATIC_RESTRICTED, $offset, self::LIMIT);

                $i++;

                $offset = $i * self::LIMIT;

                if (empty($merchantIds) === true)
                {
                    break;
                }

                $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_JOB_MERCHANT_IDS, [
                    "merchant_ids"  =>  $merchantIds
                ]);

                foreach ($merchantIds as $merchantId)
                {
                    PartialScheduledSettlementForMerchantJob::dispatch($this->mode, $merchantId);

                    $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_FOR_MERCHANT_JOB_DISPATCHED, [
                        "merchant_id"   =>  $merchantId
                    ]);
                }
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_JOB_ERROR
            );
        }
        finally
        {
            $this->delete();
        }
    }
}
