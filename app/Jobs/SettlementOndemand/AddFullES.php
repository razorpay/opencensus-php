<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\Settlement\Ondemand;

class AddFullES extends Job
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

        $this->trace->info(TraceCode::ADD_FULL_ES_JOB);

        try
        {
            $merchantIds = (new Ondemand\Core)->findFullESEligilbleMerchants();

            foreach ($merchantIds as $merchantId)
            {
                AddFullESForMerchant::dispatch($this->mode, $merchantId);

                $this->trace->info(TraceCode::ADD_FULL_ES_JOB_DISPATCHED,[
                    'merchant_id' => $merchantId
                ]);
            }

        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ADD_FULL_ES_JOB_ERROR
            );
        }
        finally
        {
            $this->delete();
        }

    }

}
