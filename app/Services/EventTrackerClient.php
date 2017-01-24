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

    protected $events = array();

    protected $defaults = array();

    protected $paymentContext = array();

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
        if (count($this->events) === 0)
        {
            return;
        }

        $url = $this->ljConfig['url'].self::TRACK_EVENT_URLPATTERN;

        $headers = [
            'content-type'  => 'application/json',
            'x-signature'   =>  $this->generateSignature(),
            'x-identifier'  =>  $this->ljConfig['identifier'],
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
    protected function sendLumberjackRequest($headers, $url)
    {
        $client = new Client(['headers' => $headers, 'http_errors' => false]);

        try
        {
            $this->defaults['events'] = $this->events;

            $options = ['json' => $this->defaults];

            /*if (($this->mock) or
                ($this->mode === Mode::TEST))
            {
                return;
            }*/

            $response = $client->request('POST', $url, $options);
        }

        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_POST_FAILED);
        }

        $this->events = [];
        $this->defaults = [];
    }

    protected function generateSignature()
    {
        $key = $this->ljConfig['key'];

        $secret = $this->ljConfig['secret'];

        $signature = hash_hmac('sha1', $key, $secret);

        return $signature;
    }

    /**
    *
    * Gets metadata and key
    * sets in the default array for event
    *
    * @param $payment Payment\Entity
    */
    protected function getEventContext(Payment\Entity $payment)
    {
        if (empty($this->events) === true)
        {
            return;
        }

        try
        {
            if (empty($this->defaults) === true)
            {
                $defaults = array(
                    'key'           => $this->ljConfig['key'],
                    'context'       => $this->fetchAndFilterMetadata($payment),
                );

                $this->defaults = $defaults;
            }

            return $this->defaults;
        }

        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_POST_FAILED);
        }
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
    protected function appendEvent(Payment\Entity $payment, $eventName, array $customProperties = [])
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

    protected function removeCommonProperties($customProperties)
    {
        unset($customProperties['payment_id']);

        unset($customProperties['order_id']);

        return $customProperties;
    }

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
                'mode'              => $this->mode,
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
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_MISSING_PAYMENT_PROPERTY);
        }
    }

    /**
    *
    * Gets Payment Metadata
    * from payment entity
    *
    * @param $payment Payment\Entity
    * @return $analytics array (context)
    *
    */
    protected function fetchAndFilterMetadata(Payment\Entity $payment)
    {
        $metadata = $payment->getMetadata();

        if (empty($metadata) === true)
        {
            return $this->fetchPaymentAnalytics($payment);
        }

        $paymentId = $payment->getPublicId();

        // cache context for paymentId
        if (isset($this->paymentContext[$paymentId]) === false)
        {
            $analytics['payment_id'] = $paymentId;

            // filter metadata for required keys
            foreach (self::CONTEXT_KEYS as $key)
            {
                if (isset($key, $metadata) === true)
                {
                    $analytics[$key] = $metadata[$key];
                }
            }

            $this->paymentContext[$paymentId] = $analytics;
        }

        return $this->paymentContext[$paymentId];
    }

    /**
    *
    * Gets data from Payment\Analytics Entity
    * corresponding to the paymentId
    *
    * @param $payment Payment\Entity
    * @return $analytics array (metadata)
    *
    */
    protected function fetchPaymentAnalytics(Payment\Entity $payment)
    {
        // if payment does not have analytics relation set
        // return
        if (count($payment->analytics) === 0)
        {
            return;
        }

        $paymentId = $payment->getId();

        // caching paymentContext
        if (isset($this->paymentContext[$paymentId]) === false)
        {
            $analytics['payment_id'] = $payment->getPublicId();

            if (empty($payment->getOrderId()) === false)
            {
                $analytics['order_id'] = $payment->getOrderId();
            }

            $pa = $this->repo->payment_analytics->findForPaymentRecent($paymentId);

            foreach (self::CONTEXT_KEYS as $key)
            {
                // generates getter function
                $getterName = 'get'.studly_case($key);

                $getterValue = $pa->$getterName();

                if (empty($getterValue) === false)
                {
                    $analytics[$key] = $getterValue;
                }
            }

            $this->paymentContext[$paymentId] = $analytics;
        }

        return $this->paymentContext[$paymentId];
    }

    public function trackPayment(Payment\Entity $payment, $eventName, array $customProperties = [])
    {
        if ($this->mock === true)
        {
            return;
        }

        try
        {
            $this->appendEvent($payment, $eventName, $customProperties);

            $this->getEventContext($payment);
        }
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_TRACK_FAILED);
        }
    }
}
