<?php

namespace RZP\Models\Card;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Trace\TraceCode;
use RZP\Models\Card\Entity;

class Metric extends Base\Core
{
    // Labels for Card vault Metrics
    const CARD_VAULT_METRICS                    = 'card_vault_metrics';
    const LABEL_STATUS                          = 'status';
    const LABEL_ACTION                          = 'action';
    const LABEL_STATUS_CODE                     = 'status_code';
    const LABEL_BU_NAMESPACE                    = 'bu_namespace';
    const LABEL_NAMESPACE                       = 'namespace';


    public function pushCardVaultDimensions($input, $status, $statusCode = null, $action = null, $exe = null)
    {
        try
        {
            $dimensions = $this->getDefaultDimensions($input);

            $dimensions[self::LABEL_STATUS] = $status;

            $dimensions[self::LABEL_STATUS_CODE] = $statusCode;

            $dimensions[self::LABEL_ACTION] = $action;

            if ($exe !== null)
            {
                $this->pushExceptionMetrics($exe, self::CARD_VAULT_METRICS, $dimensions);
                $this->trace->info(TraceCode::VAULT_BU_NAMESPACE_MIGRATION_RAZORX_VARIANT, [
                    'dimension' => $dimensions
                ]);
                return;
            }

            $this->trace->count(self::CARD_VAULT_METRICS, $dimensions);
        }
        catch (\Throwable $exc)
        {
            $this->trace->traceException(
                $exc,
                Trace::ERROR,
                TraceCode::CARD_VAULT_METRICS_DIMENSION_PUSH_FAILED,
                [
                    'action'    => $action ?? 'none',
                    'status'    => $status ?? 'none',
                ]);
        }
    }


    protected function getDefaultDimensions($input)
    {
        if (!isset($input))
        {
            return [];
        }

        $dimensions = [];

        if (isset($input))
        {

            $dimensions += [
                self::LABEL_NAMESPACE          => $input['namespace']  ?? null,
                self::LABEL_BU_NAMESPACE       => $input['bu_namespace']  ?? null,
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
