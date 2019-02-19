<?php

namespace RZP\Models\FundAccount\Validation;

use App;

use RZP\Models\Base;

class Metric extends Base\Core
{
    // Labels for Fund Account Validation Metrics

    // Metric Names
    const FUND_ACCOUNT_VALIDATION_CREATED           = 'fund_account_validation_created';
    const FUND_ACCOUNT_VALIDATION_FAILED            = 'fund_account_validation_failed';

    public function pushCreatedMetrics()
    {
    }

    public function pushExceptionMetrics(\Throwable $e, string $metricName, array $extraDimensions = [])
    {
    }
}
