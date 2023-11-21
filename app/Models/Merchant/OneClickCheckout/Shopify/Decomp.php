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

    // Logic to determine whether we use MCS for completing the checkout or stick to API.
    // 1. Merchants using Nector coins are not supported.
    // 2. Orders using Gift cards are not supported.
    // 3. Orders using coupon engine are not supported.
    // 4. Merchants using customer account creation are not supported.
    // 5. Merchants using custom fullfilment centres are not supported.
    // 6. Merchants using draft order flow are not supported.
    // 7. Orders processed through SQS are not supported.
    // 8. Merchants using Gupshup for customer consent flows.
    // 9. Merchants who have taxes enabled on shipping.
    // Remaining traffic is controlled using Splitz.
    public function useMCSForCompleteCheckout(): bool
    {
        $merchantId = $this->merchant->getId();
        if (
            $this->merchant->isFeatureEnabled(Feature\Constants::ONE_CC_SHOPIFY_ACC_CREATE) || 
            $this->merchant->isFeatureEnabled(Feature\Constants::ONE_CC_SHOPIFY_DRAFT_ORDER) ||
            $this->merchant->isFeatureEnabled(Feature\Constants::ONE_CC_ENABLE_NECTOR_COINS) ||
            $this->merchant->isFeatureEnabled('one_cc_opt_shipping_tax') ||
            $this->merchant->isFeatureEnabled('one_cc_tax_inclusion') ||
            $merchantId === 'LsgXO1I1dfZNeI' || // wingreens for fullfilment centres
            $this->merchant->get1ccConfigFlagStatus(OneClickCheckout\Constants::ONE_CC_COUPON_ENGINE) ||
            $this->merchant->get1ccConfigFlagStatus(OneClickCheckout\Constants::ONE_CC_GIFT_CARD) ||
            $this->merchant->get1ccConfigFlagStatus(OneClickCheckout\Constants::ONE_CC_ENABLE_GUPSHUP)
        )
        {
            return false;
        }
        return (new SplitzExperimentEvaluator())->useMCSForShopifyCompleteCheckout();
    }

    // Very thin wrapper so it is easier for us to track active decomp flows for Magic Checkout.
    public function completeCheckoutDecompFlow(array $input): array
    {
        return $this->magicCheckoutSvc->completeShopifyCheckout($input);
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
}
