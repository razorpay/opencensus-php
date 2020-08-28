<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;

class AddOndemandPricingIfAbsent extends Job
{
    protected $mode;

    public $timeout = 7200;

    protected $queueConfigKey = 'mailing_list_update';

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

        $this->trace->info(TraceCode::ADD_ONDEMAND_PRICING_IF_ABSENT_JOB);

        try
        {
            $offset = 0;

            $i = 0;

            while (true)
            {
                $merchantIds = $this->repoManager
                                    ->feature
                                    ->fetchMerchantIdsWithFeatureInChunks(Feature\Constants::ES_ON_DEMAND, $offset, self::LIMIT);

                $i++;

                $offset = $i  * self::LIMIT;

                if (empty($merchantIds) === true)
                {
                    break;
                }

                foreach ($merchantIds as $merchantId)
                {
                    AddOndemandPricingIfAbsentForMerchant::dispatch($this->mode, $merchantId);
                }
            }
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ADD_ONDEMAND_PRICING_IF_ABSENT_ERROR
            );
        }
        finally
        {
            $this->delete();
        }
    }
}
