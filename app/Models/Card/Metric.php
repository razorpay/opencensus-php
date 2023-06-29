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
    const CARD_VAULT_METRICS                       = 'card_vault_metrics';
    const CARD_METADATA_FETCH                      = 'card_metadata_fetch';
    const CARD_METADATA_SAVE                       = 'card_metadata_save';
    const CARD_METADATA_FETCH_AFTER_3_DAYS         = 'card_metadata_fetch_after_3_days';
    const CARD_METADATA_FETCH_BEFORE_OR_ON_3RD_DAY = 'card_metadata_fetch_before_or_on_3rd_day';
    const CARD_METADATA_FETCH_FROM_API_DB          = 'card_metadata_fetch_from_api_db';
    const CARD_METADATA_FETCH_FROM_VAULT           = 'card_metadata_fetch_from_vault';
    const LABEL_STATUS                             = 'status';
    const LABEL_ACTION                             = 'action';
    const LABEL_STATUS_CODE                        = 'status_code';
    const LABEL_BU_NAMESPACE                       = 'bu_namespace';
    const LABEL_NAMESPACE                          = 'namespace';
    const IS_VAULT_TOKEN_EMPTY                     = 'is_vault_token_empty';
    const TEMP_VAULT_TOKEN                         = 'temp_vault_token';
    const NETWORK_TOKEN                            = 'network_token';
    const ATTRIBUTE_NAME                           = 'attribute_name';
    const ROUTE                                    = 'route';
    const LABEL_IS_TOKENISED                       = 'is_tokenised';

    const VAULT_CARD_METADATA_SAVE_FAILED  = 'vault_card_metadata_save_failed';

    const INVALID_VAULT_TOKEN_ASSOCIATED = 'invalid_vault_token_associated';


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

    public function pushCardMetaDataMetrics ($metricName , $AttributeName , $isVaultTokenEmpty , $isTempVaultToken, $isNetworkToken)
        {
            try
            {
                $dimensions = [];

                $dimensions[self::ATTRIBUTE_NAME] = $AttributeName;

                $dimensions[self::IS_VAULT_TOKEN_EMPTY] = $isVaultTokenEmpty;

                $dimensions[self::TEMP_VAULT_TOKEN] = $isTempVaultToken;

                $dimensions[self::NETWORK_TOKEN] = $isNetworkToken ;

                $app = \App::getFacadeRoot();

                $dimensions[self::ROUTE] = $app['request.ctx']->getRoute();

                $this->trace->count($metricName, $dimensions);
            }
            catch (\Throwable $exc)
            {
                $this->trace->traceException(
                    $exc,
                    Trace::ERROR,
                    TraceCode::CARD_METADATA_METRICS_PUSH_FAILED,
                    [
                        'Field Name'    =>  $AttributeName
                    ]);
            }
        }

}
