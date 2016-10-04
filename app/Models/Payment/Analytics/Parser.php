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

    public function recordPaymentRequestData(array & $input, $payment)
    {
        $input[Entity::PAYMENT_ID] = $payment->getId();

        $this->setHttpRequestData($input);

        $this->setAttempts($input, $payment);

        $this->setMetadataFromPayment($input, $payment);

        $this->updateMetadataFromPayment($input, $payment);

        return;
    }

    public function traceUnrecognizedData($paymentAnalytics)
    {
        $pa = $paymentAnalytics->toArrayPublic();

        $invalidData = [];

        foreach ($pa as $key => $value) {

            if (Analytics\Metadata::isInvalidValue($value))
            {
                $invalidData[$key] = $value;
            }
        }

        if (empty($invalidData) === false)
        {
            $this->trace->warning(TraceCode::PAYMENT_ANALYTICS_UNRECOGNIZED_DATA,
                ['invalid_data' => $invalidData,
                 'payment_id'   => $paymentAnalytics->getPaymentId()]);
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

    // set analytics data from metadata
    protected function setMetadataFromPayment(array & $log, $payment)
    {
        $metadata = $payment->getMetadata();

        if (isset($metadata))
        {
            foreach (self::$setKeys as $key)
            {
                if (empty($metadata[$key]) === false)
                {
                    $log[$key] = $metadata[$key];
                }
            }
        }
    }

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
                        [
                            'checkout_id' => $checkoutId
                        ]);

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

    protected function updateMetadataFromPayment(array & $log, $payment)
    {
        $metadata = $payment->getMetadata();

        if (isset($metadata))
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