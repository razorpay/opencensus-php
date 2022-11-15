<?php

namespace RZP\Services\Dcs;

use Example\Pg\Merchant\Refund\Features as RefundFeatures;
use Rzp\Pg\Merchant\Affordability\EligibilityFeatures;

use Razorpay\Dcs\Kv\V1\Model\V1Key;

class Constants
{
    // FundsOnHold feature metadata
    const RefundEnabled = 'refund_enabled';
    const DisableAutoRefund = 'disable_auto_refund';
    const EligibilityEnabled = 'eligibility_enabled';

    /**
     * Stores the mapping of the features to their corresponding dcs keys
     */
    public static $featureToDCSKeyMapping = [
        self::RefundEnabled => "example/pg/merchant/refund/Features",
        self::DisableAutoRefund => "example/pg/merchant/refund/Features",
        self::EligibilityEnabled => "rzp/pg/merchant/affordability/EligibilityFeatures"
    ];

    /**
     * Stores the mapping of the features to their corresponding services
     */
    public static $newDcsFeaturesAndServiceMapping = [
        self::EligibilityEnabled => "checkout-affordability-api"
    ];

    /**
     * Stores the mapping of the features to their corresponding Class
     */
    public static $featureToDCSClass = [
        self::RefundEnabled => RefundFeatures::class,
        self::DisableAutoRefund => RefundFeatures::class,
        self::EligibilityEnabled => EligibilityFeatures::class
    ];

    /**
     * Stores the mapping of the features to their corresponding Class
     */
    public static $dcsKeyToDCSClass = [
        "example/pg/merchant/refund/Features" => RefundFeatures::class,
        "rzp/pg/merchant/affordability/EligibilityFeatures" => EligibilityFeatures::class
    ];
    /**
     * @return string[]
     */
    public static function getFeatureToDCSClass(): array
    {
        return self::$featureToDCSClass;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getClassFromDCSKey(string $key)
    {
        return self::$featureToDCSClass[$key];
    }

    /**
     * @param string $key
     * @return string
     */
    public static function toDcsKey(string $key)
    {
        return self::$featureToDCSClass[$key];
    }

    public static function convertDCSKeyToString(V1Key $key)
    {
        return sprintf("%s/%s/%s/%s",
            $key->getNamespace(),
            $key->getEntity(),
            $key->getDomain(),
            $key->getObjectName()
        );
    }


    public static function convertDCSKeyToClassName(V1Key $key)
    {
        $namespace = ucwords(str_replace(['/'], ' ', $key->getNamespace()));
        $namespace = str_replace([' '], '\\', $namespace);

        $domain = ucwords(str_replace(['/'], ' ', $key->getDomain()));
        $domain = str_replace([' '], '\\', $domain);
        return (sprintf("%s\%s\%s\%s",
            $namespace,
            ucfirst($key->getEntity()),
            $domain,
            ucfirst($key->getObjectName())
        ));
    }
}
