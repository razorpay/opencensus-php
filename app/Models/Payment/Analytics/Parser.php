<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Exception;
use RZP\Http\RequestHeader;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Models\Order;
use RZP\Trace\TraceCode;

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
        Entity::OS                    => 'os',
        Entity::OS_VERSION            => 'os_version',
        Entity::DEVICE                => 'device',
        Entity::PLATFORM              => 'platform',
        Entity::PLATFORM_VERSION      => 'platform_version',
        Entity::INTEGRATION           => 'integration',
        Entity::INTEGRATION_VERSION   => 'integration_version',
        Entity::REFERER               => 'referer'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->request = $this->app['request'];
    }

    public function recordPaymentRequestData(array & $input, $payment)
    {
        $input[Entity::PAYMENT_ID] = $payment->getId();

        $this->setHttpRequestData($input);

        $this->setAttempts($input, $payment);

        $this->setMetadataFromPayment($input, $payment);

        $this->updateMetadataFromPayment($input, $payment);

        $this->checkDataBeforeSave($input);

        return;
    }

    /**
     * Traces if column values aren't consistent with each other
     */
    public function checkDataBeforeSave(array $paymentAnalytics)
    {
        // If library is Checkoutjs, then referer should always be present
        if ((isset($paymentAnalytics[Entity::LIBRARY]) === true) and
            ($paymentAnalytics[Entity::LIBRARY] === Metadata::CHECKOUTJS) and
            (isset($paymentAnalytics[Entity::REFERER]) === false))
        {
            $this->trace->error(
                TraceCode::PAYMENT_ANALYTICS_INCORRECT_DATA,
                [
                    Entity::LIBRARY => $paymentAnalytics[Entity::LIBRARY],
                    Entity::REFERER => ($paymentAnalytics[Entity::REFERER] ?? null),
                ]);
        }
    }

    /**
     * Traces any Metadata value sent by front-end, that is not recognized by API
     */
    public function traceUnrecognizedData($paymentAnalytics)
    {
        $pa = $paymentAnalytics->toArrayPublic();

        $invalidData = [];

        foreach ($pa as $key => $value)
        {
            if (Analytics\Metadata::isInvalid($value))
            {
                $invalidData[$key] = $value;
            }
        }

        if (empty($invalidData) === false)
        {
            $this->trace->error(
                TraceCode::PAYMENT_ANALYTICS_UNRECOGNIZED_DATA,
                ['invalid_data' => $invalidData,
                 'payment_id'   => $paymentAnalytics->getPaymentId()]);
        }
    }

    /**
     * Sets analytics data using the HTTP request
     */
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

        $log[Entity::IP] = $this->request->getRealClientIp();

        $log[Entity::REFERER] = $this->getRefererUrl();

        if ($this->request->header(RequestHeader::USER_AGENT) !== null)
        {
            $log[Entity::USER_AGENT] = $this->request->header(RequestHeader::USER_AGENT);
        }
    }

    protected function getRefererUrl()
    {
        $reqReferer = $this->request->header(RequestHeader::REFERER);

        if ($reqReferer !== null)
        {
            // blacklist razorpay referer URLs
            $parsedUrl = parse_url($reqReferer);

            $domain = (isset($parsedUrl['host']) === true) ? $parsedUrl['host'] : null;

            if ($domain !== null)
            {
                $substrings = explode('.', $domain);

                $substringsCount = count($substrings);

                // Extract domain from a url containing subdomain
                if ($substringsCount >= 2)
                {
                    // This doesn't handle cases when referer is, let's say, amazon.co.uk
                    $domain = $substrings[$substringsCount - 2] . '.' . $substrings[$substringsCount - 1];
                }
            }

            return (strtolower($domain) !== 'razorpay.com') ? $reqReferer : null;
        }

        return null;
    }

    /**
     * Set analytics data from metadata sent by frontend
     */
    protected function setMetadataFromPayment(array & $log, $payment)
    {
        $metadata = $payment->getMetadata();

        if (isset($metadata))
        {
            foreach (self::$setKeys as $key)
            {
                $metadataKey = self::$map[$key];

                if (empty($metadata[$metadataKey]) === false)
                {
                    $log[$key] = $metadata[$key];
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
    protected function setAttempts(array & $log, $payment)
    {
        $orderId = $payment->getApiOrderId();

        if ($orderId !== null)
        {
            // $orderId = (new Order\Entity)->verifyIdAndSilentlyStripSign($orderId);

            $payments = $this->repo->payment->fetchPaymentsForOrderId($orderId);

            $attempts = $payments->count();
        }
        else
        {
            $metadata = $payment->getMetadata();

            $checkoutId = ((isset($metadata) and isset($metadata['checkout_id']))) ? $metadata['checkout_id'] : null;

            if ($checkoutId !== null)
            {
                // get from checkout id
                $oldPayments = $this->repo->payment_analytics->getRecentMerchantPaymentsForCheckoutId($checkoutId);

                $count = $oldPayments->count();

                if (($count > 0) and
                    ($count !== $oldPayments->first()->getAttempts()))
                {
                    $this->trace->warning(
                        TraceCode::PAYMENT_CHECKOUT_INVALID_ID,
                        ['checkout_id' => $checkoutId]);

                    return;
                }

                $attempts = $count + 1;
            }
            else
            {
                $attempts = 1;
            }
        }

        $log[Entity::ATTEMPTS] = $attempts;

        return;
    }

    /**
     * Overrides analytics column values by giving preference to value passed
     * from front-end over that parsed from user-agent
     */
    protected function updateMetadataFromPayment(array & $log, $payment)
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

            if (isset($metadata[$metadataKey]) === true)
            {
                $logValueForKey = $log[$key] ?? null;

                if ($logValueForKey !== $metadata[$metadataKey])
                {
                    // collect anomalies
                    $this->collectMismatch($logValueForKey, $metadata[$metadataKey], $key, $anomalies);

                    $log[$key] = $metadata[$metadataKey];
                }
            }
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