<?php

namespace RZP\Models\BankingAccount;

use Razorpay\Trace\Logger;

use RZP\Models\Base\Core;
use RZP\Trace\TraceCode;

class Metrics extends Core
{
    // Counters
    const BANKING_ACCOUNT_RBL_MIS_REPORT_JOB_TOTAL           = 'banking_account_rbl_mis_report_job_total';
    const MASTER_DIRECT_BANKING_ACCOUNT_FETCH_FAILURES_TOTAL = 'master_direct_banking_account_fetch_failures_total';

    const BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_FAILED = 'banking_account_gateway_balance_update_job_failed';

    const BANKING_ACCOUNT_PRIORITY_GATEWAY_BALANCE_DISPATCH = 'banking_account_priority_gateway_balance_dispatch';
    const BANKING_ACCOUNT_PRIORITY_GATEWAY_BALANCE_INIT     = 'banking_account_priority_gateway_balance_init';
    const BANKING_ACCOUNT_PRIORITY_GATEWAY_BALANCE_SUCCESS  = 'banking_account_priority_gateway_balance_success';
    const BANKING_ACCOUNT_PRIORITY_GATEWAY_BALANCE_FAILURE  = 'banking_account_priority_gateway_balance_failure';

    // histogram
    const BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_LATENCY    = 'banking_account_gateway_balance_update_latency.histogram';

    public function pushErrorMetrics($labelName, $dimensions)
    {
        try
        {
            $this->trace->count($labelName, $dimensions);
        }
        catch(\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Logger::ERROR,
                TraceCode::BANKING_ACCOUNT_METRIC_PUSH_EXCEPTION,
                $dimensions,
            );
        }
    }

}
