<?php

namespace RZP\Models\Customer\Token;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Trace\TraceCode;
use RZP\Models\Card\IIN\Entity;

class Metric extends Base\Core
{
    // Labels for Token Metrics
    const TOKEN_HQ                              = 'token_hq';
    const TOKEN_HQ_RT                           = 'token_hq_rt';
    const LABEL_CARD_TYPE                       = 'card_type';
    const LABEL_CARD_IIN                        = 'card_iin';
    const LABEL_CARD_CATEGORY                   = 'card_category';
    const LABEL_CARD_NETWORK                    = 'card_network';
    const LABEL_CARD_ISSUER                     = 'card_issuer';
    const LABEL_CARD_COUNTRY                    = 'card_country';
    const LABEL_MERCHANT_ID                     = 'merchant_id';

    const LABEL_STATUS                          = 'status';
    const LABEL_STATUS_CODE                     = 'status_code';
    const LABEL_ACTION                          = 'action';
    const LABEL_INTERNAL_SERVICE_REQUEST        = 'internal_service_request';
    const LABEL_ASYNC                           = 'async';
    const LABEL_TRACE_CODE                      = 'code';
    const LABEL_TRACE_FIELD                     = 'field';
    const LABEL_TRACE_SOURCE                    = 'source';
    const LABEL_TRACE_EXCEPTION_CLASS           = 'exception_class';


    public function pushTokenHQDimensions($input, $status, $statusCode = null, $action = null, $exe = null)
    {
        try
        {
            $dimensions = $this->getDefaultDimensions($input);

            $dimensions[self::LABEL_STATUS] = $status;

            $dimensions[self::LABEL_STATUS_CODE] = $statusCode;

            $dimensions[self::LABEL_ACTION] = $action;

            $dimensions[self::LABEL_INTERNAL_SERVICE_REQUEST] = isset($input[self::LABEL_INTERNAL_SERVICE_REQUEST]) ? $input[self::LABEL_INTERNAL_SERVICE_REQUEST] : null;

            $dimensions[self::LABEL_ASYNC] = isset($input[self::LABEL_ASYNC]) ? $input[self::LABEL_ASYNC] : null;

            if ($exe !== null)
            {
                $this->pushExceptionMetrics($exe, self::TOKEN_HQ, $dimensions);

                return;
            }

            $this->trace->count(self::TOKEN_HQ, $dimensions);
        }
        catch (\Throwable $exc)
        {
            $this->trace->traceException(
                $exc,
                Trace::ERROR,
                TraceCode::TOKEN_HQ_METRIC_DIMENSION_PUSH_FAILED,
                [
                    'action'    => $action ?? 'none',
                    'status'    => $status ?? 'none',
                ]);
        }
    }

    public function pushTokenHQResponseTimeMetrics(int $startTime, $status, $action = null)
    {
        try
        {
            $dimensions[self::LABEL_STATUS] = $status;

            $dimensions[self::LABEL_ACTION] = $action;

            $responseTime = get_diff_in_millisecond($startTime);

            $this->trace->histogram(self::TOKEN_HQ_RT, $responseTime, $dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TOKEN_HQ_RESPONSE_TIME_DIMENSION_PUSH_FAILED
            );
        }
    }

    protected function getDefaultDimensions($input)
    {
        if (!isset($input))
        {
            return [];
        }

        $dimensions = [];

        if (isset($input['iin']))
        {
            $iin = $input['iin'];

            $dimensions += [
                self::LABEL_CARD_IIN         => $iin[Entity::IIN]  ?? null,
                self::LABEL_CARD_CATEGORY    => $iin[Entity::CATEGORY] ?? null,
                self::LABEL_CARD_NETWORK     => $iin[Entity::NETWORK]  ?? null,
                self::LABEL_CARD_TYPE        => $iin[Entity::TYPE] ?? null,
                self::LABEL_CARD_ISSUER      => $iin[Entity::ISSUER] ?? null,
                self::LABEL_CARD_COUNTRY     => $iin[Entity::COUNTRY] ?? null,
            ];
        }

        if (isset($input['merchant']))
        {
            $merchant = $input['merchant'];

            $dimensions += [
                self::LABEL_MERCHANT_ID      => $merchant['id'],
            ];
        }

        return $dimensions;
    }

    public function pushExceptionMetrics(\Throwable $e, string $metricName, array $extraDimensions = [])
    {
        $dimensions = $this->getDefaultExceptionDimensions($e);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count($metricName, $dimensions);
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
