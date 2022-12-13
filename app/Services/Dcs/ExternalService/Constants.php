<?php

namespace RZP\Services\Dcs\ExternalService;

class Constants
{

    /**
     * Stores the mapping of the features to their corresponding services
     */
    public static $newDcsConfigurationServiceMapping = [
        "rzp/pg/merchant/affordability/EligibilityFeatures" => "checkout-affordability-api"
    ];
}
