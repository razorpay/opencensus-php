<?php

namespace RZP\Services\Dcs\ExternalService;

class Constants
{

    /**
     * Stores the mapping of the features to their corresponding services
     */
    public static $newDcsConfigurationServiceMapping = [
        "rzp/pg/merchant/affordability/EligibilityFeatures" => "checkout-affordability-api",
        "rzp/pg/merchant/affordability/Widget"              => "checkout-affordability-api",
        "rzp/pg/merchant/order/Features" => "pg-router",
        "rzp/pg/merchant/order/cart/Features" => "pg-router",
        "rzp/pg/merchant/order/payments/Features" => "pg-router",
        "rzp/pg/merchant/payments/communication/Features" => "pg-router",
        "rzp/pg/org/payments/credits/Features" => "pg-router",
        "rzp/pg/merchant/payments/ledger/Features" => "pg-router",
        "rzp/pg/merchant/payments/refunds/Features" => "pg-router",
        "rzp/capital/merchant/onboarding/corporatecards/EligibilityFeatures" => "capital-los",
        "rzp/capital/merchant/onboarding/cashadvance/EligibilityFeatures" => "capital-los",
        "rzp/capital/merchant/cashadvance/LineOfCredit" => "capital-loc",
        "rzp/capital/merchant/cashadvance/Marketplace" => "capital-loc",
        "rzp/pg/merchant/settlements/EarlySettlementScheduled" => "capital-es",
        "rzp/pg/merchant/settlements/EarlySettlementOndemand" => "capital-es",
        "rzp/pg/merchant/refunds/RefundCreation" => "scrooge",
        "rzp/pg/merchant/refunds/Display" => "scrooge",
        "rzp/pg/merchant/refunds/Webhook" => "scrooge",
        "rzp/pg/org/refunds/Display" => "scrooge",
    ];
}
