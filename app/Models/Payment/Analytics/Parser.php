<?php

namespace RZP\Models\Payment\Analytics;

use App;

use RZP\Exception;
use RZP\Http\RequestHeader;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Trace\TraceCode;

class Parser
{
    private static $setKeys = [
        Entity::CHECKOUT_ID,
        Entity::LIBRARY,
        Entity::LIBRARY_VERSION,
        Entity::PLATFORM,
        Entity::PLATFORM_VERSION,
        Entity::INTEGRATION,
        Entity::INTEGRATION_VERSION,
    ];

    private static $updateKeys = [
        Entity::BROWSER,
        Entity::OS,
        Entity::OS_VERSION,
        Entity::DEVICE,
    ];

    public static function recordPaymentRequestData($rawData, array & $log)
    {
        // get data from request
        self::setHttpRequestData($log);

        $metadata = self::getCheckoutMetadata($rawData);

        if ($metadata !== null)
        {
            self::setLogFromMetadata($metadata, $log);

            self::setLogAttempts($metadata, $log);

            self::updateLogFromMetadata($metadata, $log);
        }
    }

    protected static function setHttpRequestData(array & $log)
    {
        // get user-agent service
        $app = App::getFacadeRoot();

        $uAgent = $app['agent'];

        $log[Entity::BROWSER] = $uAgent->browser();

        if (isset($log[Entity::BROWSER]))
        {
            $log[Entity::PLATFORM_VERSION] = $uAgent->version($uAgent->browser());
        }

        $log[Entity::OS] = $uAgent->platform();

        $log[Entity::OS_VERSION] = $uAgent->version($uAgent->platform());

        $log[Entity::DEVICE] = self::getDeviceValue($uAgent);

        // get the HTTP request
        $request = $app['request'];

        $log[Entity::IP] = $request->ip();

        if ($request->header(RequestHeader::REFERER) !== null)
        {
            $log[Entity::REFERER] = $request->header(RequestHeader::REFERER);
        }

        if ($request->header(RequestHeader::USER_AGENT) !== null)
        {
            $log[Entity::USER_AGENT] = $request->header(RequestHeader::USER_AGENT);
        }
    }

    protected static function getCheckoutMetadata($rawData)
    {
        $app = App::getFacadeRoot();

        // get data from frontend
        if ((isset($rawData['input']) === false) or
            (isset($rawData['input']['_']) === false))
        {
            return null;
        }

        $metadata = $rawData['input']['_'];

        $app['trace']->info(
            TraceCode::PAYMENT_METADATA,
            [
                'metadata'   => $metadata,
                'payment_id' => $rawData['payment_id']
            ]);

        return $metadata;
    }

    // set analytics data from metadata
    protected static function setLogFromMetadata($metadata, & $log)
    {
        foreach (self::$setKeys as $key)
        {
            if (empty($metadata[$key]) === false)
            {
                $log[$key] = $metadata[$key];
            }
        }
    }

    protected static function setLogAttempts($metadata, & $log)
    {
        if (isset($metadata[Entity::CHECKOUT_ID]) === false)
        {
            return;
        }

        $checkoutId = $metadata[Entity::CHECKOUT_ID];

        $log[Entity::ATTEMPTS] = self::calculatePaymentAttempts($checkoutId);
    }

    protected static function updateLogFromMetadata($metadata, & $log)
    {
        $app = App::getFacadeRoot();

        $anomalies = [];

        // Give preference to value passed from frontend over that parsed from user-agent
        foreach (self::$updateKeys as $key)
        {
            if ((isset($metadata[$key]) === true) and
                (isset($log[$key]) === true))
            {
                // collect anomalies
                self::collectMismatch($log[$key], $metadata[$key], $key, $anomalies);

                $log[$key] = $metadata[$key];
            }
        }

        // log anomalies
        if (empty($anomalies) === false)
        {
            $app['trace']->info(TraceCode::PAYMENT_USER_AGENT_ANOMALY, $anomalies);
        }
    }

    protected static function calculatePaymentAttempts($checkoutId)
    {
        $app = App::getFacadeRoot();

        $oldPayments = $app['repo']->payment_analytics->getRecentMerchantPaymentsForCheckoutId($checkoutId);

        $oldPaymentsGroupedByPaymentId = $oldPayments->groupBy(Entity::PAYMENT_ID);

        $count = $oldPaymentsGroupedByPaymentId->count();

        if (($count > 0) and
            ($count !== $oldPayments->first()->getAttempts()))
        {
            $app['trace']->warning(
                TraceCode::PAYMENT_CHECKOUT_INVALID_ID,
                [
                    'checkout_id' => $checkoutId
                ]);

            return null;
        }

        $attempts = $count + 1;

        return $attempts;
    }

    /**
     * @param string $davalueFromFrontend
     * @param string $valueFromUserAgent
     * @param string $dataPoint
     */
    protected static function collectMismatch($checkoutValue, $userAgentValue,
        $dataPoint, array & $anomalies)
    {
        if (strcasecmp($checkoutValue, $userAgentValue) !== 0)
        {
             $anomalies[$dataPoint] = [
                'checkoutValue' => $checkoutValue,
                'userAgentValue' => $userAgentValue
            ];
        }
    }

    protected static function getDeviceValue($uAgent)
    {
        $device = null;

        if ($uAgent->isMobile())
        {
            $device = Metadata::MOBILE;
        }
        else if($uAgent->isDesktop())
        {
            $device = Metadata::DESKTOP;
        }
        else if ($uAgent->isTablet())
        {
            $device = Metadata::TABLET;
        }

        return $device;
    }
}