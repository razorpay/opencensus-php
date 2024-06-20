<?php

namespace RZP\Models\Merchant\OneClickCheckout\MigrationUtils;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;

class SplitzExperimentEvaluator extends Base\Core
{
    protected $trace;
    protected $splitzService;

    public function __construct()
    {
        parent::__construct();
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

    public function useMCSToPollForShippingRates(): bool
    {
        $input = $this->merchantIdBasedPayload('app.magic_poll_shipping_rates_experiment_id');
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    public function useMCSToUpdateShippingAddress(): bool
    {
        $input = $this->merchantIdBasedPayload('app.magic_update_shipping_address_experiment_id');
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    public function useMCSForShopifyCompleteCheckout(): bool
    {
        $input = $this->merchantIdBasedPayload('app.magic_complete_checkout_decomp_experiment_id');
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    public function useMCSForShopifyCompleteCheckoutForFeatureFlags(string $merchantId): bool
    {
        $input = $this->merchantIdBasedPayload('app.magic_complete_checkout_decomp_feature_flags_experiment_id', $merchantId);
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    public function useCustomerGSTINForShopify(string $merchantId): bool
    {
        $input = $this->merchantIdBasedPayload('app.one_cc_customer_gstin_experiment_id', $merchantId);
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    public function useTripleConsentForMerchant(string $merchantId): bool
    {
        $input = $this->merchantIdBasedPayload('app.one_cc_triple_consent_experiment_id', $merchantId);
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enabled';
    }

    public function useMCSForShopifyApplyCouponDecomposition(): bool
    {
        $input = $this->merchantIdBasedPayload('app.magic_shopify_apply_coupon_decomp_experiment_id');
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    public function useMCSForMerchantApplyCouponDecomposition(): bool
    {
        $input = $this->merchantIdBasedPayload('app.magic_merchant_apply_coupon_decomp_experiment_id');
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    public function useMCSForShopifyRemoveCouponDecomposition(): bool
    {
        $input = $this->merchantIdBasedPayload('app.magic_shopify_remove_coupon_decomp_experiment_id');
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    public function useMCSForMerchantRemoveCouponDecomposition(): bool
    {
        $input = $this->merchantIdBasedPayload('app.magic_merchant_remove_coupon_decomp_experiment_id');
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    // useMCSForAsyncShopifyCompleteCheckout is called from APIEventSubscriber which does not have
    // merchant set in context so we pass the merchantId and manually build the input.
    public function useMCSForAsyncShopifyCompleteCheckout(string $merchantId): bool
    {
        $input = $this->merchantIdBasedPayload('app.magic_complete_checkout_async_decomp_experiment_id', $merchantId);
        $result = $this->evaluateExperiment($input);
        return $result['variant'] === 'enable';
    }

    // To be used when merchant_id is the only param required for evaluating the experiment.
    // $this->merchant always exists for web server pods. In case of worker pods the client
    // must explicitly pass $merchantId.
    protected function merchantIdBasedPayload(string $experimentPath, string $merchantId = ''): array
    {
        if ($merchantId === '')
        {
            $merchantId = $this->merchant->getId();
        }
        return [
            'id'            => UniqueIdEntity::generateUniqueId(),
            'experiment_id' => $this->app['config']->get($experimentPath),
            'request_data'  => json_encode(['merchant_id' => $merchantId]),
        ];
    }
}
