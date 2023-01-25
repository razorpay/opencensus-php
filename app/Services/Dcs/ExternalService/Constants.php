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
    ];
}
