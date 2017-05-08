<?php

namespace RZP\Services;

use Exception;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Analytics\Entity as Analytics;

use GuzzleHttp\Client;

class EventTrackerClient extends Base\Core
{
    protected $mock;

    protected $request;

    protected $ljConfig;

    protected $events = [];

    protected $payment = null;

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

    /**
     * List of sensitive keys to exclude from sengding to segment
     */
    const SENSITIVE_KEYS = [
        'CARD_NUMBER'           => 'card.number',
        'GATEWAY_CARD_NUMBER'   => 'terminal_gateway_input.card.number',
        'CVV'                   => 'card.cvv',
        'CARD_ID'               => 'card.id',
        'GATEWAY_CVV'           => 'terminal_gateway_input.card.cvv',
        'CARD_EXP_MONTH'        => 'card.expiry_month',
        'CARD_EXP_YEAR'         => 'card.expiry_year',
        'PAYMENT_CARD_ID'       => 'payment.card_id',
        'VPC_ACCESSCODE'        => 'request.content.vpc_AccessCode',
        'VPC_CARDEXP'           => 'request.content.vpc_CardExp',
    ];

    const VERSION = '2.0';

    const TRACK_EVENT_URLPATTERN = 'track';

    public function __construct($app)
    {
        parent::__construct();

        $this->request = $app['request'];

        $this->ljConfig = $app['config']->get('applications.lumberjack');

        $this->mock = $this->ljConfig['is_mock'];
    }

    /**
     * constructs headers and fetches url
     * to be sent to lumberjack
     */
    public function buildRequestAndSend()
    {
        $url = $this->ljConfig['url'] . self::TRACK_EVENT_URLPATTERN;

        $headers = [
            'content-type'  => 'application/json',
            'x-signature'   => $this->generateSignature(),
            'x-identifier'  => $this->ljConfig['identifier'],
        ];

        $this->sendLumberjackRequest($headers, $url);
    }

    /**
     * Sends POST request to Lumberjack
     * url_pattern = /v1/track
     *
     * Sets events and defaults null after request
     *
     * @param $headers array
     * @param $url string
     */
    protected function sendLumberjackRequest(array $headers, string $url)
    {
        try
        {
            // remove comment after testing
            if (($this->mock) or
                ($this->mode === Mode::TEST))
            {
                return;
            }

            $eventData = $this->getEventTrackerData();

            if (empty($eventData) === true)
            {
                return;
            }

            $request  = [
                'url'       => $url,
                'headers'   => $headers,
                'content'   => json_encode($eventData),
                'options'   => [],
            ];

            $job = new RequestJob($request);

            $this->dispatch($job);
        }
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_QUEUE_SEND_FAILED);
        }
        finally
        {
            $this->events = [];

            $this->payment = null;
        }
    }

    /**
     * Generates hmac signature for authenticating request
     * @return string
     */
    protected function generateSignature()
    {
        $key = $this->ljConfig['key'];

        $secret = $this->ljConfig['secret'];

        $signature = hash_hmac('sha1', $key, $secret);

        return $signature;
    }

    /**
     * Formats and builds the lumberjack event data
     * before posting to the lumberjack service.
     * @return array|void
     */
    protected function getEventTrackerData()
    {
        if (empty($this->events) === true)
        {
            return [];
        }

        $defaults = [
            'key'       => $this->ljConfig['key'],
            'mode'      => $this->mode,
            'events'    => $this->events
        ];

        $context = $this->getEventContext();

        if ((isset($context) === true) and (empty($context) === false))
        {
            $defaults['context'] = $context;
        }

        return $defaults;
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
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_CONTEXT_FETCH_FAILED);
        }

        return [];
    }

    /**
     *
     * Forms an event object with properites
     * appends it to $this->events array
     *
     * @param $payment Payment\Entity
     * @param $eventName string
     * @param $customProperties array
     */
    protected function appendEvent(Payment\Entity $payment, string $eventName, array $customProperties = [])
    {
        // payment-related properties
        $properties = $this->getPaymentProperties($payment);

        // terminal-related properties
        $terminalDetails = [];

        if ($payment->getTerminalId() !== null)
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

        $event = array(
            'event'         => $eventName,
            'timestamp'     => time(),
            'properties'    => $properties,
        );

        array_push($this->events, $event);
    }

    /**
     * For the custom properties sent as part of the event
     * remove the ones that are common across the entire
     * request lifecycle
     * @param array $customProperties
     * @return array
     */
    protected function removeCommonProperties(array $customProperties)
    {
        unset($customProperties['payment_id']);

        unset($customProperties['order_id']);

        return $customProperties;
    }

    /**
     * Remove all sorts of sensitive information
     * @param array $properties
     */
    protected function removeSensitiveInformation(array & $properties)
    {
        foreach (self::SENSITIVE_KEYS as $name => $key)
        {
            unset($properties[$key]);
        }
    }

    /**
     *
     * Gets data related to a particular payment
     * appends it to the properties of the event
     *
     * @param $payment Payment\Entity
     * @return $properties array
     */
    protected function getPaymentProperties(Payment\Entity $payment)
    {
        try
        {
            $isInternational = null;

            if ($payment->getCardId() !== null)
            {
                $isInternational = $payment->isInternational();
            }

            $properties = [
                'payment_id'        => $payment->getPublicId(),
                'merchant_id'       => $payment->merchant->getId(),
                'merchant_name'     => $payment->merchant->getBillingLabelElseName(),
                'amount'            => $payment->getAmount(),
                'method'            => $payment->getMethod(),
                'requestId'         => $this->request->getId(),
                'international'     => $isInternational,
                'version'           => self::VERSION,
            ];

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

            $merchant = $payment->merchant;

            $properties['fee_bearer'] = $merchant->isFeeBearerCustomer();

            return $properties;
        }
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_MISSING_PAYMENT_PROPERTY);
        }
    }

    /**
     *
     * Gets data of terminal associated
     * with a payment entitity
     *
     * @param $terminal Terminal\Entity
     * @return $data array
     *
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
                'shared'    => $terminal->getShared(),
                'recurring' => $terminal->getRecurring(),
            ];

            return $data;
        }
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_MISSING_TERMINAL_DATA);
        }
    }

    /**
     *
     * Gets Payment Metadata
     * from payment entity
     *
     * @return $analytics array (context)
     *
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
     * @return array
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

               $this->trace->warning(TraceCode::LUMBERJACK_MISSING_PAYMENT_CONTEXT, $msg);
            }
        }

        return $analytics;
    }

    public function trackPayment(Payment\Entity $payment, $eventName, array $customProperties = [])
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
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_TRACK_FAILED);
        }
    }
}
