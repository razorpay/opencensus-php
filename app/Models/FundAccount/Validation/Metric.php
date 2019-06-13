<?php

namespace RZP\Models\FundAccount\Validation;

use App;

use RZP\Constants;
use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;

class Metric extends Base\Core
{
    // Labels for Fund Account Validation Metrics

    // Metric Names
    const FUND_ACCOUNT_VALIDATION_CREATED           = 'fund_account_validation_created';
    const FUND_ACCOUNT_VALIDATION_FAILED            = 'fund_account_validation_failed';

    public function pushCreatedMetrics()
    {
        $this->trace->count(self::FUND_ACCOUNT_VALIDATION_CREATED);
    }

    protected function getDefaultExceptionDimensions(\Throwable $e): array
    {
        $errorAttributes = [];

        if ($e instanceof Exception\BaseException)
        {
            if (($e->getError() !== null) and ($e->getError() instanceof Error))
            {
                $errorAttributes = $e->getError()->getAttributes();
            }
        }
        else
        {
            $errorAttributes = [
                Constants\Metric::LABEL_TRACE_CODE         => $e->getCode(),
            ];
        }

        $dimensions = [
            Constants\Metric::LABEL_TRACE_CODE                => array_get($errorAttributes, Error::INTERNAL_ERROR_CODE),
            Constants\Metric::LABEL_TRACE_FIELD               => array_get($errorAttributes, Error::FIELD),
            Constants\Metric::LABEL_TRACE_SOURCE              => array_get($errorAttributes, Error::ERROR_CLASS),
            Constants\Metric::LABEL_TRACE_EXCEPTION_CLASS     => get_class($e),
        ];

        return $dimensions;
    }

    public function pushExceptionMetrics(\Throwable $e, string $metricName, array $extraDimensions = [])
    {
        $dimensions = $this->getDefaultExceptionDimensions($e);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count($metricName, $dimensions);
    }
}
