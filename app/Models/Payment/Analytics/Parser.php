<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Exception;
use RZP\Http\RequestHeader;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use Jenssegers\Agent\Agent;

class Parser extends Base\Core
{
    /**
     * These keys will be set from either user-agent
     * or payment metadata input.
     */
    private static $setKeys = [
        Entity::CHECKOUT_ID,
        Entity::LIBRARY,
        Entity::LIBRARY_VERSION,
        Entity::PLATFORM,
        Entity::PLATFORM_VERSION,
        Entity::INTEGRATION,
        Entity::INTEGRATION_VERSION,
        Entity::RISK_SCORE,
        Entity::RISK_ENGINE,
    ];

    /**
     * For these keys, even if we can get the data from request headers or
     * user-agent, the data we get from checkout (payment metadata) gets
     * preference and overrides.
     *
     * Especially for 'referer', we get it in request headers also.
     * But because of the way api/checkout is structured, often 'referer'
     * value is simply checkout.razorpay.com and we don't get the actual website
     * where the payment happened. However, since checkout can accurately tell us
     * the website, we give it preference.
     *
     * For other keys like os, os_version, device, we get to know the values via
     * user-agent. However, when the values come from android sdk, then we give
     * those values preference as the sdk has better chance of know the os
     * accurately compared to user-agent.
     */
    private static $updateKeys = [
        Entity::OS,
        Entity::OS_VERSION,
        Entity::DEVICE,
        Entity::REFERER,
    ];

    protected static $map = [
        Entity::CHECKOUT_ID           => 'checkout_id',
        Entity::LIBRARY               => 'library',
        Entity::LIBRARY_VERSION       => 'library_version',
        Entity::BROWSER               => 'browser',
        Entity::BROWSER_VERSION       => 'browser_version',
        Entity::OS                    => 'os',
        Entity::OS_VERSION            => 'os_version',
        Entity::DEVICE                => 'device',
        Entity::PLATFORM              => 'platform',
        Entity::PLATFORM_VERSION      => 'platform_version',
        Entity::INTEGRATION           => 'integration',
        Entity::INTEGRATION_VERSION   => 'integration_version',
        Entity::REFERER               => 'referer',
        Entity::RISK_SCORE            => 'risk_score',
        Entity::RISK_ENGINE           => 'risk_engine',
    ];

    protected static $defaults = [
        Entity::PLATFORM_VERSION => null,
        Entity::PLATFORM         => null,
        Entity::INTEGRATION      => null,
    ];

    protected function init()
    {
        $this->request = $this->app['request'];

        $this->uAgent = $this->app['agent'];

        $this->ba = $this->app['basicauth'];
    }

    public function recordPaymentRequestData(Entity $pa, Payment\Entity $payment)
    {
        $pa->payment()->associate($payment);

        $pa->setMerchantId($payment->getMerchantId());

        $this->setHttpRequestData($pa);

        $this->setAttempts($pa, $payment);

        $this->setMetadataFromPayment($pa, $payment);

        $this->updateMetadataFromPayment($pa, $payment);

        return;
    }

    /**
     * Traces if column values aren't consistent with each other
     */
    public function traceInconsistentData(array $pa)
    {
        $library = $pa[Entity::LIBRARY] ?? null;

        $referer = $pa[Entity::REFERER] ?? null;

        $platform = $pa[Entity::PLATFORM] ?? null;

        if (($library !== null) and
            ($library === Metadata::CHECKOUTJS) and
            ($referer === null) and
            ($platform !== Metadata::BROWSER))
        {
            $this->trace->warning(
                TraceCode::PAYMENT_ANALYTICS_INCORRECT_DATA,
                [
                    Entity::PAYMENT_ID => $pa[Entity::PAYMENT_ID],
                    Entity::LIBRARY => $library,
                    Entity::REFERER => $referer,
                    Entity::PLATFORM => $platform
                ]);
        }
    }

    /**
     * Traces any Metadata value sent by front-end, that is not recognized by API
     */
    public function traceUnrecognizedData(array $pa)
    {
        $invalidData = [];

        foreach ($pa as $key => $value)
        {
            if (Metadata::isInvalid($value))
            {
                $invalidData[$key] = $value;
            }
        }

        if (empty($invalidData) === false)
        {
            $this->trace->error(
                TraceCode::PAYMENT_ANALYTICS_UNRECOGNIZED_DATA,
                ['invalid_data' => $invalidData,
                 'payment_id'   => $pa[Entity::PAYMENT_ID]]);
        }
    }

    /**
     * Sets analytics data using the HTTP request
     */
    protected function setHttpRequestData(Entity $pa): void
    {
        $ua = null;

        /**
         * Payment entity creation happens in gateway callback for QrV2 Payments
         * Since the user agent will not be present in headers in the gateway callback,
         *      we are taking the user agent from payment input
         * $this->app['agent'] in init() is not taking user agent from input,
         *      hence we are creating a new Agent object with user agent from input
         *
         * For S2S Payments as well, take user agent from input
         */
        if (
            $this->ba->isPrivateAuth() ||
            ($this->ba->isDirectAuth() && $pa->payment->isUpiQr())
        ) {
            $ua = $pa->payment->getMetadata('user_agent');

            $this->uAgent = new Agent(null, $ua);
        }

        $pa->setBrowser($this->getBrowser($ua));

        if ($pa->getBrowser() !== null)
        {
            $pa->setBrowserVersion($this->uAgent->version($this->uAgent->browser($ua)));
        }

        $pa->setOs($this->getOs($ua));

        $pa->setOsVersion($this->uAgent->version($this->uAgent->platform($ua)));

        $pa->setDevice($this->getDeviceValue($ua));

        $pa->setIp($this->getIp($pa));

        $pa->setReferer($this->getRefererUrl());

        $ua = $ua ?: $this->request->header(RequestHeader::USER_AGENT);

        $pa->setUserAgent($ua);
    }

    protected function getBrowser($ua)
    {
        $browserFromUa = $this->uAgent->browser($ua);

        if ($browserFromUa === false)
        {
            return;
        }

        $browserFromUa = strtolower($browserFromUa);

        if (Metadata::isValidBrowser($browserFromUa) === true)
        {
            return $browserFromUa;
        }

        switch ($browserFromUa)
        {
            case 'mozilla':
                return Metadata::FIREFOX;

            default:
                return $browserFromUa;
        }
    }

    protected function getOs($ua)
    {
        $osFromUa = $this->uAgent->platform($ua);

        if ($osFromUa === false)
        {
            return;
        }

        $osFromUa = strtolower($osFromUa);

        if (Metadata::isValidOs($osFromUa))
        {
            return $osFromUa;
        }

        switch ($osFromUa)
        {
            case 'os x':
                return Metadata::MACOS;

            case 'androidos':
                return Metadata::ANDROID;

            default:
                return $osFromUa;
        }
    }

    protected function getRefererUrl()
    {
        $reqReferer = $this->request->header(RequestHeader::REFERER);

        if ($reqReferer === null)
        {
            return null;
        }

        // blacklist razorpay referer URLs
        $parsedUrl = parse_url($reqReferer);

        $domain = $parsedUrl['host'] ?? null;

        if ($domain === null)
        {
            return null;
        }

        $substrs = explode('.', $domain);

        $substrCount = count($substrs);

        // Extract domain from a url containing subdomain
        if ($substrCount >= 2)
        {
            // This doesn't handle cases when referer is, let's say, amazon.co.uk
            $domain = $substrs[$substrCount - 2] . '.' . $substrs[$substrCount - 1];
        }

        return ((strtolower($domain) !== 'razorpay.com') ? $reqReferer : null);
    }

    /**
     * Payment entity creation happens in gateway callback for QrV2 Payments
     * Since we want to capture the user's IP address from which checkout was opened,
     *      we are taking the IP from payment input
     *
     * For S2S Payments as well, take IP from input
     *
     * @param  Entity  $pa
     *
     * @return string
     */
    protected function getIp(Entity $pa)
    {
        $ip = $this->request->ip();

        if (
            $this->ba->isPrivateAuth() ||
            ($this->ba->isDirectAuth() && $pa->payment->isUpiQr())
        ) {
            return $pa->payment->getMetadata('ip', $ip);
        }

        return $ip;
    }

    /**
     * Set analytics data from metadata sent by frontend
     */
    protected function setMetadataFromPayment(Entity $log, $payment)
    {
        $metadata = $payment->getMetadata();

        if (isset($metadata))
        {
            foreach (self::$setKeys as $key)
            {
                $metadataKey = self::$map[$key];

                $functionName = 'set' . studly_case($key);

                if (empty($metadata[$metadataKey]) === false)
                {
                    $log->$functionName($metadata[$key]);
                }
                else if (array_key_exists($key, self::$defaults) === true)
                {
                    $log->$functionName(self::$defaults[$key]);
                }
            }
        }
    }

    /**
     * Payment attempt calculation steps are as follows:
     * - Get the reference id, it will be either order id or checkout id
     * - Get older payments for the reference id
     * - Increment count by 1
     */
    protected function setAttempts(Entity $log, $payment)
    {
        $orderId = $payment->getApiOrderId();

        if ($orderId !== null)
        {
            $order = $payment->order;

            // No need to increment count here as it's already done
            // during payment creation for an order.
            $attempts = $order->getAttempts();
        }
        else
        {
            $metadata = $payment->getMetadata();

            $checkoutId = null;

            if ((isset($metadata) and isset($metadata['checkout_id'])))
            {
                $checkoutId = $metadata['checkout_id'];
            }

            $attempts = $this->getAttemptsFromCheckoutId($checkoutId);
        }

        $log->setAttempts($attempts);
    }

    protected function getAttemptsFromCheckoutId($checkoutId)
    {
        if ($checkoutId === null)
        {
            return 1;
        }

        $oldPaymentAnalytics = $this->repo
                            ->payment_analytics
                            ->getRecentMerchantPaymentsForCheckoutId($checkoutId);

        $count = $oldPaymentAnalytics->count();

        $latestEntity = $oldPaymentAnalytics->first();

        if (($count > 0) and
            ($count !== $latestEntity->getAttempts()))
        {
            $this->trace->warning(
                TraceCode::PAYMENT_CHECKOUT_INVALID_ID,
                [
                     'checkout_id' => $checkoutId,
                     'count' => $count,
                     'last_entity' => [
                                        'id' => $latestEntity->getId(),
                                        'attempts' => $latestEntity->getAttempts()
                                      ]
                ]
            );

            return;
        }

        return ($count + 1);
    }

    /**
     * Overrides analytics column values by giving preference to value passed
     * from front-end over that parsed from user-agent
     */
    protected function updateMetadataFromPayment(Entity $log, $payment)
    {
        $metadata = $payment->getMetadata();

        if (isset($metadata) === false)
        {
            return;
        }

        $anomalies = [];

        foreach (self::$updateKeys as $key)
        {
            $metadataKey = self::$map[$key];

            if (isset($metadata[$metadataKey]) === false)
            {
                continue;
            }

            $logValueForKey = $log[$key] ?? null;

            if ($logValueForKey === $metadata[$metadataKey])
            {
                continue;
            }

            // collect anomalies
            if ($logValueForKey !== null)
            {
                $this->collectMismatch($logValueForKey,
                    $metadata[$metadataKey],
                    $key,
                    $anomalies);
            }

            $functionName = 'set' . studly_case($key);

            $log->$functionName($metadata[$metadataKey]);
        }

        // log anomalies
        if (empty($anomalies) === false)
        {
            $this->trace->info(TraceCode::PAYMENT_USER_AGENT_ANOMALY, $anomalies);
        }
    }

    /**
     * @param string $davalueFromFrontend
     * @param string $valueFromUserAgent
     * @param string $dataPoint
     */
    protected function collectMismatch(
        $checkoutValue, $userAgentValue, $dataPoint, array & $anomalies)
    {
        if (strcasecmp($checkoutValue, $userAgentValue) !== 0)
        {
             $anomalies[$dataPoint] = [
                'checkoutValue' => $checkoutValue,
                'userAgentValue' => $userAgentValue
            ];
        }
    }

    protected function getDeviceValue($ua)
    {
        $device = null;

        if ($this->uAgent->isMobile($ua))
        {
            $device = Metadata::MOBILE;
        }
        else if($this->uAgent->isDesktop($ua))
        {
            $device = Metadata::DESKTOP;
        }
        else if ($this->uAgent->isTablet($ua))
        {
            $device = Metadata::TABLET;
        }

        return $device;
    }
}
