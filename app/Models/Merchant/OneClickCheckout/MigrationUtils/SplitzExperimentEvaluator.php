<?php

namespace RZP\Models\Merchant\OneClickCheckout\MigrationUtils;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class SplitzExperimentEvaluator extends Base\Core
{
    protected $app;
    protected $trace;
    protected $splitzService;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->splitzService = $this->app['splitzService'];
    }

    /**
     * Evaluates a response from Splitz
     * In case of basic traffic routing to microservices this can be configured to return a bool
     * For more complex cases the variant value will be returned
     * Works only for a response structure
     *
     * @param array $payload Payload to be sent to Splitz
     * @param bool $evaluateExperiment Whether to evaluate the experiment result with the expectedVariant
     * @param string $expectedVariant Expected Splitz response to return true
     * @param string|null $defaultVariant In case of errors, the default return value
     * @param array $tracePayload Custom payload to be merged to the array for filtering
     * @param string $onFailTraceCode Custom trace code for logging any errors
     * @return array Returns variant and the bool based on $evaluateExperiment
     */
    public function evaluateExperiment(
        array  $payload,
        bool   $evaluateExperiment = false,
        string $expectedVariant = '',
        string $defaultVariant = '',
        array  $tracePayload = [],
        string $onFailTraceCode = TraceCode::ONE_CC_SPLITZ_EXPERIMENT_ERROR,
    ): array
    {
        try {
            $response = $this->splitzService->evaluateRequest($payload);
            $variant = $response['response']['variant']['name'] ?? null;

            if ($variant === null) {
                $this->traceError($onFailTraceCode, 'invalid_response', $response, 'invalid_response', $tracePayload);
                $variant = $defaultVariant;
            }
        } catch (\Throwable $e) {
            $response = $response ?? [];
            $this->traceError($onFailTraceCode, 'uncaught_exception', $response, $e->getMessage(), $tracePayload);
            $variant = $defaultVariant;
        }

        if ($evaluateExperiment === true) {
            return [
                'variant' => $variant,
                'experiment_enabled' => $variant === $expectedVariant,
            ];
        }
        $this->trace->info(TraceCode::ONE_CC_SPLITZ_EXPERIMENT_RESPONSE,
            array_merge(
                $tracePayload,
                [
                    'response' => $response ?? [],
                    'variant' => $variant,
                ])
        );
        return ['variant' => $variant];
    }

    /**
     * @param string $onFailTraceCode
     * @param string $reason
     * @param string $response
     * @param string $errorMessage
     * @param array $tracePayload
     * @return void
     */
    protected function traceError(string $onFailTraceCode, string $reason, array $response = [], string $errorMessage, array $tracePayload): void
    {
        $this->trace->error(
            $onFailTraceCode,
            array_merge(
                [
                    'reason' => $reason,
                    'response' => $response ?? 'invalid_response',
                    'error' => $errorMessage,
                ],
                $tracePayload
            )
        );
    }
}
