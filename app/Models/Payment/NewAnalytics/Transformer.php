<?php

namespace RZP\Models\Payment\NewAnalytics;

use RZP\Trace\TraceCode;
use RZP\Models\Payment\Analytics;
use Razorpay\Trace\Logger as Trace;

class Transformer
{
    protected $entity = 'new_payment_analytics';

    public static function getNewAnalyticsEntity( Analytics\Entity $analytics): Entity
    {
        $fillArray = $analytics->toArrayPublic();

        $newAnalytics = (new Entity())->build($fillArray);

        $attributes = ["RiskScore", "VirtualDeviceId", "RiskEngine"];

        $newAnalytics = Transformer::getUpdatedEntityForAttributes($attributes, $newAnalytics, $analytics);

        return $newAnalytics;
    }

    public static function getUpdatedNewAnalyticsEntity( Entity $newAnalytics, Analytics\Entity $analytics): Entity
    {
        $attributes = ["Attempts", "Library", "LibraryVersion", "Browser", "BrowserVersion", "Device", "Os", "OsVersion",
            "Ip", "Platform", "PlatformVersion", "Referer", "Integration", "IntegrationVersion", "UserAgent", "RiskScore", "VirtualDeviceId", "RiskEngine"];

        $newAnalytics = Transformer::getUpdatedEntityForAttributes($attributes, $newAnalytics, $analytics);

        return $newAnalytics;
    }

    public static function getUpdatedEntityForAttributes(array $attributes, Entity $newAnalytics, Analytics\Entity $analytics): Entity
    {
        foreach ($attributes as $attribute)
        {
            try
            {
                $getterMethod = "get" . $attribute;

                $setterMethod = "set" . $attribute;

                $newAnalytics->$setterMethod($analytics->$getterMethod());
            }
            catch (\Throwable $ex)
            {
                $app = \App::getFacadeRoot();

                $app['trace']->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::NEW_PAYMENT_ANALYTICS_ERROR_SETTING_ATTRIBUTE,
                    [
                        'error' => $ex->getMessage(),
                    ]);
            }
        }
        return $newAnalytics;
    }

}
