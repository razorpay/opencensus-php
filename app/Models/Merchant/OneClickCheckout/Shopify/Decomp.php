<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Throwable;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Merchant\Metric;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService;
use RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator;

// All Shopify related decomp should write wrappers in this file for easier maintenance.
class Decomp extends Base\Service
{
    protected $monitoring;
    protected $magicCheckoutSvc;

    public function __construct()
    {
        parent::__construct();
        $this->monitoring = new Monitoring();
        $this->magicCheckoutSvc = new MagicCheckoutService\Service();
    }

    // useMCSForCompleteCheckout controls traffic migration for "v1/1cc/shopify/complete" endpoint.
    public function useMCSForCompleteCheckout(): bool
    {
        $merchantId = $this->merchant->getId();
        $useMCS = $this->useMCSForShopifyCompleteCheckoutForFeatureFlags($merchantId);
        if (!$useMCS)
        {
            return false;
        }
        // This experiment controls ramp up for merchants without customizations (as above).
        return (new SplitzExperimentEvaluator())->useMCSForShopifyCompleteCheckout();
    }

    // Very thin wrapper so it is easier for us to track active decomp flows for Magic Checkout.
    public function completeCheckoutDecompFlow(array $input): array
    {
        return $this->magicCheckoutSvc->completeShopifyCheckout($input);
    }

    // Very thin wrapper so it is easier for us to track active decomp flows for Magic Checkout.
    public function checkAndCompletePostShopifyOrderPlacementSteps(array $input): array
    {
        return $this->magicCheckoutSvc->checkAndCompletePostShopifyOrderPlacementSteps($input);
    }

    public function isShopifyOrderPlacedByMCS(string $orderId): bool
    {
        try
        {
            // response is `order_placed: true|false`
            $response = $this->magicCheckoutSvc->getCheckoutOrderStatus(['order_id' => $orderId]);
            return $response['order_placed'];
        }
        catch (\Throwable $th)
        {
            $this->trace->error(
                TraceCode::MAGIC_DECOMP_API_ERROR,
                [
                    'error'  => $th->getMessage(),
                    'module' => 'complete_checkout',
                    'step'   => 'check_order_status',
                    'input'  => ['order_id' => $orderId]
                ]);
            return false;
        }
    }

    public function pushCompleteCheckoutPayloadToMCSQueue(array $publishData, int $waitTime): void
    {
        $queueName = $this->app['config']->get('queue.mcs_shopify_complete_checkout');
        $this->app['queue']->connection('sqs')->later($waitTime, 'mcs_shopify_complete_checkout', json_encode($publishData), $queueName);
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_MCS_COMPLETE_CHECKOUT_SQS_PUSH_SUCCESS,
            [
                'data' => $publishData
            ]);
    }

    // useMCSForAsyncCompleteCheckout controls traffic migration for "one-cc-shopify-create-order" worker.
    // Based on this, we either push to the SQS queue consumed by API or a new one consumed by MCS.
    public function useMCSForAsyncCompleteCheckout(string $merchantId): bool
    {
        // $merchant is autoset by the API middlewares. For flows involving SQS workers or cronjobs we need
        // to manually set the $this->merchant variable everytime.
        if (empty($this->merchant) === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($merchantId);
        }
        $useMCS = $this->useMCSForShopifyCompleteCheckoutForFeatureFlags($merchantId);
        if (!$useMCS)
        {
            return false;
        }
        return (new SplitzExperimentEvaluator())->useMCSForAsyncShopifyCompleteCheckout($merchantId);
    }

    // useMCSForShopifyCompleteCheckoutForFeatureFlags controls merchant wise (based onfeature flags)
    // migration strategy for Shopify complete checkout API and SQS worker.
    // API and SQS must control traffic migration only for the supported merchants.
    protected function useMCSForShopifyCompleteCheckoutForFeatureFlags(string $merchantId): bool
    {
        // These are the feature flags which are yet not supported in MCS.
        if (
            $this->merchant->isFeatureEnabled(Feature\Constants::ONE_CC_SHOPIFY_ACC_CREATE) ||
            $merchantId === 'LsgXO1I1dfZNeI' || // wingreens for fullfilment centres
            $this->merchant->get1ccConfigFlagStatus(OneClickCheckout\Constants::ONE_CC_ENABLE_GUPSHUP)
        )
        {
            return false;
        }
        // These are the feature flags currently being migrated.
        if (
            $this->merchant->isFeatureEnabled(Feature\Constants::ONE_CC_SHOPIFY_DRAFT_ORDER) ||
            $this->merchant->isFeatureEnabled('one_cc_opt_shipping_tax') ||
            $this->merchant->isFeatureEnabled('one_cc_tax_inclusion')
        )
        {
            return (new SplitzExperimentEvaluator())->useMCSForShopifyCompleteCheckoutForFeatureFlags($merchantId);
        }
        return true;
    }

}
