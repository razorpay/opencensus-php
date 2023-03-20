<?php

namespace RZP\Models\SubVirtualAccount;

use Razorpay\Trace\Logger;

use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;

class Metric extends Core
{
    const LABEL_METRIC_NAME                                     = 'metric_name';
    const MASTER_MERCHANT_FEATURE_NOT_ENABLED_TOTAL             = 'master_merchant_feature_not_enabled_total';
    const MASTER_MERCHANT_NOT_LIVE_OR_EQUIVALENT_TOTAL          = 'master_merchant_not_live_or_equivalent_total';
    const SUB_MERCHANT_NOT_LIVE_OR_EQUIVALENT_TOTAL             = 'sub_merchant_not_live_or_equivalent_total';
    const SUB_MERCHANT_LIMIT_ADDITION_EXCEPTIONS_TOTAL          = 'sub_merchant_limit_addition_exceptions_total';
    const SUB_ACCOUNT_CREDIT_TRANSFER_PROCESSING_FAILURES_TOTAL = 'sub_account_credit_transfer_processing_failures_total';

    public function pushMetrics($metricName, $dimensions)
    {
        try
        {
            $this->trace->count($metricName, $dimensions);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::SUB_VIRTUAL_ACCOUNT_METRIC_PUSH_EXCEPTION,
                [
                    self::LABEL_METRIC_NAME => $metricName
                ]
            );
        }
    }


}
