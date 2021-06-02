<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\OndemandFundAccount;

class CreateOndemandFundAccounts extends Job
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

        RuntimeManager::setTimeLimit(7200);

        RuntimeManager::setMaxExecTime(7200);

        $this->trace->info(TraceCode::CREATE_SETTLEMENT_ONDEMAND_FUND_ACCOUNTS_JOB);

        try
        {
            $merchantIds = $this->repoManager
                                ->feature
                                ->fetchMerchantIdsWithFeatureAndNoFundAccountInChunks(Feature\Constants::ES_ON_DEMAND);

            foreach ($merchantIds as $merchantId)
            {
                CreateSettlementOndemandFundAccount::dispatch($this->mode, $merchantId)->delay(random_int(1,900));
            }

        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CREATE_SETTLEMENT_ONDEMAND_FUND_ACCOUNTS_ERROR
            );
        }
        finally
        {
            $this->delete();
        }
    }
}
