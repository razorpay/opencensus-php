<?php


namespace RZP\Http;


class RouteLatencyGroup
{
    const LATENCY_GROUP_LOW      = 'low';
    const LATENCY_GROUP_MEDIUM   = 'medium';
    const LATENCY_GROUP_HIGH     = 'high';
    const LATENCY_GROUP_CRON_JOB = 'job';

    /**
     * @return string latency group for P95 for the particular route
     */
    public static function getLatencyGroupForRoute($route): string
    {
        if (in_array($route, Route::$internalApps['cron']) === true)
        {
            return self::LATENCY_GROUP_CRON_JOB;
        }
        if (array_key_exists($route, self::$latencyGroupMap) === false)
        {
            return self::LATENCY_GROUP_LOW;
        }

        return self::$latencyGroupMap[$route];
    }

    /*Adding three different kinds of latency group for routes based on analysis of their latency behavior:
    1. low latency (treated as default) ~3s
    2. medium latency ~10s
    3. high latency ~30s
    */

    protected static $latencyGroupMap = [
        'merchant_activation_company_search' => self::LATENCY_GROUP_MEDIUM,
        'merchant_activation_save'           => self::LATENCY_GROUP_MEDIUM,
        'fd_consume_webhook'                 => self::LATENCY_GROUP_MEDIUM,
        'merchant_document_upload'           => self::LATENCY_GROUP_MEDIUM,
        'user_fetch_admin'                   => self::LATENCY_GROUP_MEDIUM,
        'user_otp_create'                    => self::LATENCY_GROUP_MEDIUM,
        'merchant_fetch_users'               => self::LATENCY_GROUP_HIGH,
        'merchant_analytics'                 => self::LATENCY_GROUP_HIGH,
        'pricing_get_merchant_plans'         => self::LATENCY_GROUP_HIGH,
        'bvs_service_dashboard'              => self::LATENCY_GROUP_HIGH,
        'admin_fetch_entity_multiple'        => self::LATENCY_GROUP_HIGH,

    ];
}
