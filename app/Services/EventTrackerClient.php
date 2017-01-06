<?php

namespace RZP\Services;

use RZP\Constants\Mode;
use GuzzleHttp\Client;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class EventTrackerClient extends Base\Core
{
    protected $key;

    protected $mock;

    protected $events;

    protected $defaults;

    protected $request;

    protected $ljConfig;

    protected $paymentContext;

    const CONTEXT_KEYS = [
        'ip',
        'checkout_id',
        'user_agent',
        'library',
        'library_version',
        'platform',
        'platform_version',
        'referer',
        'browser',
        'os',
        'os_version',
        'device',
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

        $this->key = $this->ljConfig['key'];

        $this->mock = $this->ljConfig['is_mock'];

        $this->events = array();

        $this->defaults = array();

        $this->paymentContext = array();

    }

    public function buildRequestAndSend()
    {
        if (count($this->events) === 0)
        {
            return;
        }

        $url = $this->ljConfig['url'].self::TRACK_EVENT_URLPATTERN;

        $headers = [
            'content-type' => 'application/json',
        ];

        $this->sendLumberjackRequest($headers, $url);
    }

    protected function sendLumberjackRequest($headers, $url)
    {
        $client = new Client(['headers' => $headers, 'http_errors' => false]);

        try
        {
            $this->defaults['events'] = $this->events;

            $options = ['json' => $this->defaults];

            if (($this->mock) or
                ($this->mode === Mode::TEST))
            {
                return;
            }

            $response = $client->request('POST', $url, $options);
        }

        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_POST_FAILED);
        }

        $this->events = [];
        $this->defaults = [];
    }

    protected function getDefaults(Payment\Entity $payment)
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
                    'key'           => $this->key,
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

    protected function appendEvent(Payment\Entity $payment, $eventName, array $customProperties = [])
    {
        $properties = $this->fillEventProperties($payment);

        $event = array(
            'event'         => $eventName,
            'timestamp'     => UniqueIdEntity::getNanotimeInteger(),
        );

        if (empty($customProperties) === false)
        {
            $customProperties = $this->removeCommonProperties($customProperties);

            $properties = array_merge($properties, $customProperties);
        }

        $this->removeSensitiveInformation($properties);

        $event['properties'] = $properties;

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

    protected function fillEventProperties(Payment\Entity $payment)
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

        $terminalDetails = [];

        if ($payment->getTerminalId() !== null)
        {
            $terminal = $payment->terminal;

            $terminalDetails = $this->fetchTerminalData($terminal, $payment);
        }

        if (count($terminalDetails) > 0)
        {
            $properties['terminal'] = $terminalDetails;
        }

        $merchant = $payment->merchant;

        $properties['fee_bearer'] = $merchant->isFeeBearerCustomer();

        return $properties;
    }

    protected function fetchTerminalData(Terminal\Entity $terminal, Payment\Entity $payment)
    {
        $data = [];

        $data['id'] = $terminal->getPublicId();

        $data['gateway'] = $terminal->getGateway();

        $data['acquirer'] = $terminal->getGatewayAcquirer();

        $data['category'] = $terminal->getCategory();

        $data['shared'] = $terminal->getShared();

        $data['recurring'] = $terminal->getRecurring();

        $method = $payment->getMethod();

        $data['method'] = $method;

        // note: using individual here instead of getMethodWithDetail
        // as PaymentCancelTest fails on Payment\Entity::getFormattedCard
        if ($method === Method::NETBANKING)
        {
            $data['bank']  = $payment->getBankName();
        }

        if ($method === Method::WALLET)
        {
            $data['wallet'] = ucfirst($payment->getWallet());
        }

        if ($method === Method::UPI)
        {
            $data['vpa'] = $payment->getVpa();
        }

        return $data;
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
                if (array_key_exists($key, $metadata) === true)
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

        $paymentId = $payment->getPublicId();

        // caching paymentContext
        if (isset($this->paymentContext[$paymentId]) === false)
        {
            $analytics['payment_id'] = $paymentId;

            if (empty($payment->getOrderId()) === false)
            {
                $analytics['order_id'] = $payment->getOrderId();
            }

            $pa = $this->repo->analytics->findForPayment($paymentId, true);

            foreach ($this->contextKeys as $key)
            {
                // generates getter function
                $getterName = 'get'.studly_case($key);

                if (empty($pa->$getterName()) === false)
                {
                    $analytics[$key] = $pa->getterName();
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

            $this->getDefaults($payment);
        }

        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::LUMBERJACK_POST_FAILED);
        }
    }
}
