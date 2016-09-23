<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Exception;
use RZP\Http\RequestHeader;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Trace\TraceCode;

class Parser extends Base\Core
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
        Entity::REFERER,
    ];

    public function recordPaymentRequestData($rawData, array & $log)
    {
        // get data from request
        $this->setHttpRequestData($log);

        $metadata = $this->getCheckoutMetadata($rawData);

        if ($metadata !== null)
        {
            $this->setLogFromMetadata($metadata, $log);

            $this->setLogAttempts($metadata, $log);

            $this->updateLogFromMetadata($metadata, $log);
        }
    }

    protected function setHttpRequestData(array & $log)
    {
        // get user-agent service
        $uAgent = $this->app['agent'];

        $log[Entity::BROWSER] = $uAgent->browser();

        if (isset($log[Entity::BROWSER]))
        {
            $log[Entity::PLATFORM_VERSION] = $uAgent->version($uAgent->browser());
        }

        $log[Entity::OS] = $uAgent->platform();

        $log[Entity::OS_VERSION] = $uAgent->version($uAgent->platform());

        $log[Entity::DEVICE] = $this->getDeviceValue($uAgent);

        // get the HTTP request
        $request = $this->app['request'];

        $log[Entity::IP] = $request->getRealClientIp();

        $reqReferer = $request->header(RequestHeader::REFERER);

        if ($reqReferer !== null)
        {
            // blacklist razorpay referer URLs
            $parsedUrl = parse_url($reqReferer);

            if ((isset($parsedUrl['host'])) and
                (strtolower($parsedUrl['host']) !== 'razorpay.com'))
            {
                $log[Entity::REFERER] = $reqReferer;
            }
        }

        if ($request->header(RequestHeader::USER_AGENT) !== null)
        {
            $log[Entity::USER_AGENT] = $request->header(RequestHeader::USER_AGENT);
        }
    }

    protected function getCheckoutMetadata($rawData)
    {
        // get data from frontend
        if ((isset($rawData['input']) === false) or
            (isset($rawData['input']['_']) === false))
        {
            return null;
        }

        $metadata = $rawData['input']['_'];

        $this->trace->info(
            TraceCode::PAYMENT_METADATA,
            [
                'metadata'   => $metadata,
                'payment_id' => $rawData['payment_id']
            ]);

        return $metadata;
    }

    // set analytics data from metadata
    protected function setLogFromMetadata($metadata, & $log)
    {
        foreach (self::$setKeys as $key)
        {
            if (empty($metadata[$key]) === false)
            {
                $log[$key] = $metadata[$key];
            }
        }
    }

    protected function setLogAttempts($metadata, & $log)
    {
        if (isset($metadata[Entity::CHECKOUT_ID]) === false)
        {
            return;
        }

        $checkoutId = $metadata[Entity::CHECKOUT_ID];

        $log[Entity::ATTEMPTS] = $this->calculatePaymentAttempts($checkoutId);
    }

    protected function updateLogFromMetadata($metadata, & $log)
    {
        $anomalies = [];

        // Give preference to value passed from frontend over that parsed from user-agent
        foreach (self::$updateKeys as $key)
        {
            if ((isset($metadata[$key]) === true) and
                (isset($log[$key]) === true))
            {
                // collect anomalies
                $this->collectMismatch($log[$key], $metadata[$key], $key, $anomalies);

                $log[$key] = $metadata[$key];
            }
        }

        // log anomalies
        if (empty($anomalies) === false)
        {
            $this->trace->info(TraceCode::PAYMENT_USER_AGENT_ANOMALY, $anomalies);
        }
    }

    protected function calculatePaymentAttempts($checkoutId)
    {
        $oldPayments = $this->repo->payment_analytics->getRecentMerchantPaymentsForCheckoutId($checkoutId);

        $oldPaymentsGroupedByPaymentId = $oldPayments->groupBy(Entity::PAYMENT_ID);

        $count = $oldPaymentsGroupedByPaymentId->count();

        if (($count > 0) and
            ($count !== $oldPayments->first()->getAttempts()))
        {
            $this->trace->warning(
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
    protected function collectMismatch($checkoutValue, $userAgentValue,
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

    protected function getDeviceValue($uAgent)
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