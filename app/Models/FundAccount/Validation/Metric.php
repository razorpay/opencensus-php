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

    // Counters
    const FUND_ACCOUNT_VALIDATION_CREATED_TOTAL          = 'fund_account_validation_created_total';
    const FUND_ACCOUNT_VALIDATION_COMPLETED_TOTAL        = 'fund_account_validation_completed_total';
    const FUND_ACCOUNT_VALIDATION_FAILED_TOTAL           = 'fund_account_validation_failed_total';

    // Metric Names
    const FUND_ACCOUNT_VALIDATION_CREATED                = 'fund_account_validation_created';
    const FUND_ACCOUNT_VALIDATION_FAILED                 = 'fund_account_validation_failed';

    const FUND_ACCOUNT_VALIDATION_CREATED_TO_COMPLETED_DURATION_SECONDS               = 'fund_account_validation_created_to_completed_duration_seconds.histogram';
    const FUND_ACCOUNT_VALIDATION_CREATED_TO_FAILED_DURATION_SECONDS                  = 'fund_account_validation_created_to_failed_duration_seconds.histogram';

    public function pushCreatedMetrics(string $fundAccountType)
    {
        try {
            $dimensions = [
                "fund_account_type" => $fundAccountType,
            ];

            $this->trace->count(self::FUND_ACCOUNT_VALIDATION_CREATED, $dimensions);
        }
        catch (\Throwable $ex)
        {
            app('trace')->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FUND_ACCOUNT_VALIDATION_METRIC_PUSH_EXCEPTION,
                [
                    'fundAccountType' => $fundAccountType,

                ]);
        }
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

    public static function pushStatusChangeMetrics(Entity $fav, string $previousStatus = null)
    {
        $currentStatus = $fav->getStatus();

        try
        {
            if (empty($previousStatus) === false and (Status::hasFinalStatus($fav)))
            {
                $functionName = self::getFunctionNameToCallForStatusChange($currentStatus, $previousStatus);

                self::$functionName($fav);
            }

            self::pushCountMetrics($fav);
        }
        catch (\Throwable $ex)
        {
            app('trace')->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FUND_ACCOUNT_VALIDATION_METRIC_PUSH_EXCEPTION,
                [
                    'id'              => $fav->getId(),
                    'previous_status' => $previousStatus,
                    'current_status'  => $currentStatus,
                ]);
        }
    }

    protected static function pushCountMetrics(Entity $fav)
    {
        $currentStatus = $fav->getStatus();

        $metricConstantKey = 'FUND_ACCOUNT_VALIDATION_' . strtoupper($currentStatus) . '_TOTAL';

        $metricConstantValue = constant("self::{$metricConstantKey}");

        $extraDimensions = self::getMetricExtraDimensions($fav);

        $metricDimensions = self::getMetricDimensions($fav, $extraDimensions);

        app('trace')->count($metricConstantValue, $metricDimensions);
    }


    protected static function getMetricExtraDimensions(Entity $fav)
    {
        return [];
    }

    protected static function getMetricDimensions(Entity $fav, array $extra = []): array
    {
        return $extra +
            [
                "fund_account_type" => $fav->getFundAccountType()
            ];

    }

    protected static function getFunctionNameToCallForStatusChange(string $currentStatus, string $previousStatus)
    {
        $functionName = 'push' . ucfirst($previousStatus) . 'To' . ucfirst($currentStatus) . 'Metrics';

        return camel_case($functionName);
    }


    protected static function pushCreatedToCompletedMetrics(Entity $fav)
    {
        $metricDimensions = self::getMetricDimensions($fav);

        $timeDuration     = $fav->getCreatedAt() - $fav->getUpdatedAt();

        app('trace')->histogram(
            self::FUND_ACCOUNT_VALIDATION_CREATED_TO_COMPLETED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushCreatedToFailedMetrics(Entity $fav)
    {
        $metricDimensions = self::getMetricDimensions($fav);

        $timeDuration     = $fav->getCreatedAt() - $fav->getUpdatedAt();

        app('trace')->histogram(
            self::FUND_ACCOUNT_VALIDATION_CREATED_TO_FAILED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

}
