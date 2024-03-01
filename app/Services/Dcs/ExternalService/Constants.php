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
        "rzp/pg/merchant/risk/TrustScan"                    => "checkout-affordability-api",
        "rzp/pg/merchant/order/Features" => "pg-router",
        "rzp/pg/merchant/order/cart/Features" => "pg-router",
        "rzp/pg/merchant/order/payments/Features" => "pg-router",
        "rzp/pg/merchant/payments/communication/Features" => "pg-router",
        "rzp/pg/merchant/payments/banking_program/AttemptCustomisation" => "pg-router",
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
        "rzp/pg/merchant/checkout/magic/GlobalControls" => "magic-checkout-service",
        "rzp/pg/merchant/checkout/magic/Dashboard" => "magic-checkout-service",
        "rzp/pg/merchant/checkout/magic/AccountControls" => "magic-checkout-service",
        "rzp/pg/merchant/checkout/magic/Analytics" => "magic-checkout-service",
        "rzp/pg/merchant/checkout/magic/UIControls" => "magic-checkout-service",
        "rzp/pg/merchant/checkout/magic/Address" => "magic-checkout-service",
        "rzp/pg/merchant/checkout/magic/Payment" => "magic-checkout-service",
        "rzp/pg/merchant/paymentlinks/Features" => "payment-links",
        "rzp/pg/org/paymentlinks/Features" => "payment-links",
        "rzp/nocode/merchant/paymentlink/Features" => "payment-links",
        "rzp/pg/org/care/TicketCreation" => "care",
        "rzp/platform/merchant/reporting/CustomReports" => "reporting",
        "rzp/platform/org/reporting/CustomReports" => "reporting",
        "rzp/pg/merchant/router/CostBasedRouting"  => "smart_routing",
        "rzp/x/merchant/workflows/Workflows" => "workflows",
        "rzp/x/merchant/onboarding/rbl/RblAccess" => "banking-accounts"
    ];
}
