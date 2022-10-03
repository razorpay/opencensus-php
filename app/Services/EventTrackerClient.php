<?php

namespace RZP\Services;

use App;
use Exception;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Analytics\Entity as Analytics;

class EventTrackerClient extends AbstractEventClient
{
    protected $request;

    protected $payment = null;

    protected $urlPattern;

    protected $config;

    protected $mock;

    protected $sns;

    protected $hmacAlgo = 'sha256';

    const TRACK_EVENT_URL_PATTERN = 'track';

    const SNS_CLIENT = 'lumberjack';

    const CONTEXT_KEYS = [
        Analytics::IP,
        Analytics::CHECKOUT_ID,
        Analytics::USER_AGENT,
        Analytics::LIBRARY,
        Analytics::LIBRARY_VERSION,
        Analytics::PLATFORM,
        Analytics::PLATFORM_VERSION,
        Analytics::REFERER,
        Analytics::BROWSER,
        Analytics::OS,
        Analytics::OS_VERSION,
        Analytics::DEVICE,
    ];

    const VERSION = '2.0';

    public function __construct($app)
    {
        parent::__construct();

        $this->urlPattern = self::TRACK_EVENT_URL_PATTERN;

        $this->request = $app['request'];

        $this->config = $app['config']->get('applications.lumberjack');

        $this->mock = $this->config['mock'];

        $this->sns = $app['sns'];
    }

    /**
     * Method to build all the events together
     */
    public function buildRequestAndSend()
    {
        return parent::buildRequestAndSend();
    }

    /**
     *
     * Gets metadata and key
     * sets in the default array for event
     */
    protected function getEventContext()
    {
        try
        {
            return $this->fetchAndFilterMetadata();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::EVENT_CONTEXT_FETCH_FAILED);
        }

        return [];
    }

    /**
     *
     * Forms an event object with properties
     * appends it to $this->events array
     *
     * @param Payment\Entity $payment
     * @param string $eventName
     * @param array $customProperties
     */
    protected function appendEvent(Payment\Entity $payment, string $eventName, array $customProperties = [])
    {
        // payment-related properties
        $properties = $this->getPaymentProperties($payment);

        // terminal-related properties
        $terminalDetails = [];

        //
        //
        // This terminal was deleted due to Yesbank moratorium
        // This particular terminal is not a direct settlement terminal
        // Will be removing this check once the terminal is fixed.
        //
        // Slack thread for reference:
        // https://razorpay.slack.com/archives/CA66F3ACS/p1584100168218900?thread_ts=1584090894.210900&cid=CA66F3ACS
        //
        if (($payment->getTerminalId() !== null) and
            ($payment->getTerminalId() !== 'B2K2t8JD9z98vh'))
        {
            $terminal = $payment->terminal;

            $terminalDetails = $this->fetchTerminalData($terminal);
        }

        if (count($terminalDetails) > 0)
        {
            $properties['terminal'] = $terminalDetails;
        }

        // custom properties
        if (empty($customProperties) === false)
        {
            $customProperties = $this->removeCommonProperties($customProperties);

            $properties = array_merge($properties, $customProperties);
        }

        // cleaning properties of sensitive data
        $this->removeSensitiveInformation($properties);

        $event = [
            'event'         => $eventName,
            'timestamp'     => Carbon::now(self::TIMEZONE)->timestamp,
            'properties'    => $properties,
        ];

        $this->events[] = $event;
    }

    /**
     * For the custom properties sent as part of the event
     * remove the ones that are common across the entire
     * request lifecycle
     *
     * @param array $customProperties
     * @return array $customProperties
     */
    protected function removeCommonProperties(array $customProperties)
    {
        unset($customProperties['payment_id']);

        unset($customProperties['order_id']);

        return $customProperties;
    }

    /**
     *
     * Gets data related to a particular payment
     * appends it to the properties of the event
     *
     * @param Payment\Entity $payment
     * @return array $properties
     */
    protected function getPaymentProperties(Payment\Entity $payment)
    {
        try
        {
            $properties = [
                'payment_id'        => $payment->getPublicId(),
                'merchant_id'       => $payment->merchant->getId(),
                'merchant_name'     => $payment->merchant->getBillingLabel(),
                'merchant_category' => $payment->merchant->getCategory2(),
                'amount'            => $payment->getAmount(),
                'method'            => $payment->getMethod(),
                'requestId'         => $this->request->getId(),
                'version'           => self::VERSION,
                'contact'           => $payment->getContact(),
                'email'             => $payment->getEmail(),
            ];

            if (array_key_exists('created_at', $payment->getAttributes()) === true)
            {
                $properties['created_at'] = $payment->getCreatedAt();
            }

            $method = $payment->getMethod();

            $properties['method'] = $method;

            // note: using individual here instead of getMethodWithDetail
            // as PaymentCancelTest fails on Payment\Entity::getFormattedCard
            if ($method === Method::NETBANKING)
            {
                $properties['bank']  = $payment->getBankName();
            }

            if ($method === Method::WALLET)
            {
                $properties['wallet'] = ucfirst($payment->getWallet());
            }

            if ($method === Method::UPI)
            {
                $properties['vpa'] = $payment->getVpa();
            }

            if ($payment->hasCard() === true)
            {
                $properties['card_network'] = $payment->card->getNetwork();
                $properties['card_type'] = $payment->card->getType();
                $properties['card_country'] = $payment->card->getCountry();
                $properties['card_issuer'] = $payment->card->getIssuer();
                $properties['international'] = $payment->isInternational();
            }

            if ($payment->hasOrder() === true)
            {
                $properties['attempts'] = $payment->order->getAttempts();
            }

            $merchant = $payment->merchant;

            $properties['merchant_fee_bearer'] = $merchant->getFeeBearer();

            $properties['payment_fee_bearer'] = $payment->getFeeBearer();

            return $properties;
        }
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::EVENT_MISSING_PAYMENT_PROPERTY);
            return [];
        }
    }

    /**
     *
     * Gets data of terminal associated
     * with a payment entitity
     *
     * @param Terminal\Entity $terminal
     * @return array $data
     */
    protected function fetchTerminalData(Terminal\Entity $terminal)
    {
        try
        {
            $data = [
                'id'        => $terminal->getPublicId(),
                'gateway'   => $terminal->getGateway(),
                'acquirer'  => $terminal->getGatewayAcquirer(),
                'category'  => $terminal->getCategory(),
                'shared'    => $terminal->isShared(),
                'type'      => $terminal->getType(),
                'mode'      => $terminal->getMode(),
            ];

            return $data;
        }
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::EVENT_MISSING_TERMINAL_DATA);
        }
    }

    /**
     *
     * Gets Payment Metadata from payment entity
     *
     * @return array $analytics (context)
     */
    protected function fetchAndFilterMetadata()
    {
        $metadata = $this->payment->getMetadata();

        if (empty($metadata) === true)
        {
            return $this->fetchPaymentAnalytics();
        }

        $analytics['payment_id'] = $this->payment->getPublicId();

        // filter metadata for required keys
        foreach (self::CONTEXT_KEYS as $key)
        {
            if (isset($metadata[$key]) === true)
            {
                $analytics[$key] = $metadata[$key];
            }
        }

        return $analytics;
    }

    /**
     * Gets data from Payment\Analytics Entity
     * corresponding to the paymentId
     *
     * @return array $analytics
     */
    protected function fetchPaymentAnalytics()
    {
        $pa = $this->payment->analytics;

        // Return if no analytics entity for payment
        if ($pa === null)
        {
            return;
        }

        $analytics = [];

        $analytics['payment_id'] = $this->payment->getPublicId();

        if (empty($this->payment->getOrderId()) === false)
        {
            $analytics['order_id'] = $this->payment->getOrderId();
        }

        foreach (self::CONTEXT_KEYS as $key)
        {
            try
            {
                // generates getter function
                $getterName = 'get'.studly_case($key);

                $getterValue = $pa->$getterName();

                if (empty($getterValue) === false)
                {
                   $analytics[$key] = $getterValue;
                }
            }
            catch (Exception $e)
            {
                $msg = [
                    'getterName' => $getterName,
                    'key'        => $key
                ];

               $this->trace->warning(TraceCode::EVENT_MISSING_PAYMENT_CONTEXT, $msg);
            }
        }

        return $analytics;
    }

    /**
     * Track a payment through lumberjack
     *
     * @param Payment\Entity $payment
     * @param string $eventName
     * @param array $customProperties
     */
    public function trackPayment(Payment\Entity $payment, string $eventName, array $customProperties = [])
    {
        // remove comment after testing
        if ($this->mock === true)
        {
            return;
        }

        try
        {
            if (is_null($this->payment) === true)
            {
                $this->payment = $payment;
            }

            $this->appendEvent($payment, $eventName, $customProperties);
        }
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::EVENT_TRACK_FAILED);
        }
    }

    // for lumberjack, we use sha256 and base 64 encoded signature
    protected function generateSignature(string $message, string $secret)
    {
        $signature = json_encode(hash_hmac($this->hmacAlgo, $message, $secret, true));

        return $signature;
    }

    /**
     * Dispatch event data via SNS and if that fails we use SQS via RequestJob.
     *
     * @param array $headers
     * @param string $url
     * @param array $eventData
     */
    protected function sendEventRequest(array $headers, string $url, array $eventData)
    {
        try
        {
            $this->sns->publish(json_encode($eventData), self::SNS_CLIENT);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_ASYNC_REQUEST_FAILED, $eventData);

            parent::sendEventRequest($headers, $url, $eventData);
        }
    }
}
