<?php

namespace RZP\Jobs\SettlementOndemand;

use Carbon\Carbon;
use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Settlement\EarlySettlementFeaturePeriod as featurePeriod;

class DisableES extends Job
{
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

        $this->trace->info(TraceCode::DISABLE_ES_JOB);

        try
        {
            $offset = 0;

            $i = 0;

            $now = Carbon::now(Timezone::IST)->getTimestamp();

            while (true)
            {
                $merchantIds = (new featurePeriod\Repository)->findByDisableDateInChunks($now, $offset, self::LIMIT);

                $i++;

                $offset = $i  * self::LIMIT;

                if (empty($merchantIds) === true)
                {
                    break;
                }

                foreach ($merchantIds as $merchantId)
                {
                    DisableESForMerchant::dispatch($this->mode, $merchantId);
                }
            }
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DISABLE_ES_JOB_ERROR
            );
        }
        finally
        {
            $this->delete();
        }

    }

}
