<?php

namespace RZP\Models\Card\IIN;

use App;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Metric extends Base\Core
{
    // Labels for IIN Metrics
    const LABEL_IIN_NETWORK                     = 'network';
    const LABEL_IIN_TYPE                        = 'type';
    const LABEL_IIN_SUBTYPE                     = 'subtype';
    const LABEL_IIN_COUNTRY                     = 'country';
    const LABEL_IIN_ISSUER                      = 'issuer';
    const LABEL_IIN_ENABLED                     = 'enabled';
    const LABEL_IIN_STATUS                      = 'status';
    const LABEL_TRACE_CODE                      = 'code';
    const LABEL_TRACE_FIELD                     = 'field';
    const LABEL_TRACE_SOURCE                    = 'source';
    const LABEL_TRACE_EXCEPTION_CLASS           = 'exception_class';

    // Metric Names
    const BIN_API                               = 'bin_api';
    const BIN_API_RESPONSE_TIME                 = 'bin_api_response_time';

    // Dimensions Values
    const SUCCESS                               = 'success';
    const FAILED                                = 'failed';

    public function pushIinMetrics(string $metric, string $status, Entity $iin = null, $exe = null)
    {
        try
        {
            if ($iin !== null)
            {
                $dimensions = $this->getDefaultDimentions($iin);
            }

            $dimensions[self::LABEL_IIN_STATUS] = $status;

            if ($exe !== null)
            {
                $this->pushExceptionMetrics($exe, $metric, $dimensions);

                return;
            }

            $this->trace->count($metric, $dimensions);
        }

        catch (\Throwable $exc)
        {
            $this->trace->traceException(
                $exc,
                Trace::ERROR,
                TraceCode::IIN_METRICS_PUSH_EXCEPTION,
                [
                    'metric'    => $metric,
                    'status'    => $status,
                    'iin'       => $iin ?? 'none',
                ]);
        }
    }

    public function pushIINResponseTimeMetrics(Entity $iin, string $metric, int $startTime)
    {
        try
        {
            $responseTime = get_diff_in_millisecond($startTime);

            $dimensions = $this->getDefaultDimentions($iin);

            $this->trace->histogram($metric, $responseTime, $dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::IIN_ERROR_LOGGING_RESPONSE_TIME_METRIC
            );
        }
    }

    public function pushExceptionMetrics(\Throwable $e, string $metricName, array $extraDimensions = [])
    {
        $dimensions = $this->getDefaultExceptionDimensions($e);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count($metricName, $dimensions);
    }

    protected function getDefaultDimentions(Entity $iin)
    {
        return [
            self::LABEL_IIN_NETWORK           => $iin->getNetwork(),
            self::LABEL_IIN_TYPE              => $iin->getType(),
            self::LABEL_IIN_SUBTYPE           => $iin->getSubType(),
            self::LABEL_IIN_COUNTRY           => $iin->getCountry(),
            self::LABEL_IIN_ISSUER            => $iin->getIssuer(),
            self::LABEL_IIN_ENABLED           => $iin->isEnabled(),
        ];
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
                Metric::LABEL_TRACE_CODE         => $e->getCode(),
            ];
        }

        $dimensions = [
            Metric::LABEL_TRACE_CODE                => array_get($errorAttributes, Error::INTERNAL_ERROR_CODE),
            Metric::LABEL_TRACE_FIELD               => array_get($errorAttributes, Error::FIELD),
            Metric::LABEL_TRACE_SOURCE              => array_get($errorAttributes, Error::ERROR_CLASS),
            Metric::LABEL_TRACE_EXCEPTION_CLASS     => get_class($e),
        ];

        return $dimensions;
    }

}
