<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;

class AddOndemandRestrictedFeature extends Job
{
    protected $mode;

    public $timeout = 7200;

    const LIMIT = 400;

    const DATALAKE_QUERY ='';

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

        $this->trace->info(TraceCode::ADD_ONDEMAND_RESTRICTED_FEATURE_JOB);

        try
        {
            $merchantIds = (new Ondemand\Core)->findOndemandRestrictedEligilbleMerchants();

            foreach ($merchantIds as $merchantId)
            {
                AddOndemandRestrictedFeatureForMerchant::dispatch($this->mode, $merchantId);

                $this->trace->info(TraceCode::ADD_ONDEMAND_RESTRICTED_FEATURE_JOB_DISPATCHED,[
                    'merchant_id' => $merchantId
                ]);
            }
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ADD_ONDEMAND_RESTRICTED_FEATURE_ERROR
            );
        }
        finally
        {
            $this->delete();
        }
    }
}
