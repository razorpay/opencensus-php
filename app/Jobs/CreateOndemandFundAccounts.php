<?php

namespace RZP\Jobs;

use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;

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
                    CreateSettlementOndemandFundAccount::dispatch($this->mode, $merchantId);
                }
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
