<?php

namespace RZP\Services;

use GuzzleHttp\Client;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class SegmentClient extends Base\Core
{
    /**
     * Events array to be sent to segment.
     * @var array
     */
    protected $events = array();

    /**
     * lumberjack segment url endpoint
     */
    const LUMBERJACK_SEGMENT_URLPATTERN = 'segment_post';

    /**
     * Current version of this implementation
     */
    const VERSION = "1.0";

    /**
     * Guzzle timeout for posting to lumberjack
     */
    const CONNECT_TIMEOUT = 5;

    /**
     * seperator for array flattening
     */
    const SEPERATOR = ':';

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

    /**
     * Lumberjack config array
     */
    protected $lgConfig;

    /**
     * Whether segmetn is mocked.
     */
    protected $mock;

    /**
     * Unique id of the current request
     */
    protected $request;

    protected $anonId;

    protected $ids;

    public function __construct($app)
    {
        parent::__construct();

        $this->ljConfig = $app['config']->get('applications.lumberjack');

        $this->mock = $this->app['config']->get('segment.is_mock');

        $this->events = [];

        $this->request = $app['request'];
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

    protected function fillDefaults(Payment\Entity $payment, $event)
    {
        $metadata = $payment->getMetadata();

        $isInternational = null;

        if ($payment->getCardId() !== null)
        {
            $isInternational = $payment->isInternational();
        }

        $terminalId = null;

        $terminalDetails = [];

        if ($payment->getTerminalId() !== null)
        {
            $terminal = $payment->terminal;

            $terminalDetails = $this->fetchTerminalData($terminal, $payment);
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
            'metadata'          => $metadata,
            'version'           => self::VERSION,
            'timestamp'         => UniqueIdEntity::getNanotimeInteger(),
        ];

        if (count($terminalDetails) > 0)
        {
            $properties['terminal'] = $terminalDetails;
        }

        $merchant = $payment->merchant;

        $properties['fee_bearer'] = $merchant->isFeeBearerCustomer();

        $id = $this->recordSegmentIdsAndGetAnonId($payment, $metadata, $properties);

        $properties = flatten_array($properties, self::SEPERATOR);

        $defaults = [
            'anonymousId'   => $id,
            'event'         => $event,
            'properties'    => $properties
        ];

        return $defaults;
    }

    public function buildRequestAndSend()
    {
        if (count($this->events) === 0)
        {
            return;
        }

        $ljConfig = $this->ljConfig;

        $url = $ljConfig['url'].self::LUMBERJACK_SEGMENT_URLPATTERN;

        $secret = $ljConfig['secret'];

        $data = json_encode($this->events);

        $signature = hash_hmac('sha1', $data, $secret);

        $headers = [
            'content-type' => 'application/json',
            'x-signature' => $signature
        ];

        $this->sendLumberjackRequest($headers, $url, $this->events);
    }

    protected function sendLumberjackRequest($headers, $url, $events)
    {
        if ($this->mock)
        {
            return;
        }

        // TODO: make this async using guzzler async events
        $client = new Client(['headers' => $headers, 'http_errors' => false]);

        try
        {
            $options = ['json' => $events, 'connect_timeout' => self::CONNECT_TIMEOUT];

            $response = $client->request('POST', $url, $options);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SEGMENT_POST_FAILED);
        }

        // empty the events array here
        $this->events = [];
    }

    protected function removeSensitiveInformation(array & $properties)
    {
        foreach (self::SENSITIVE_KEYS as $name => $key)
        {
            unset($properties[$key]);
        }
    }

    protected function removeCommonProperties($customProperties)
    {
        unset($customProperties['payment_id']);

        unset($customProperties['order_id']);

        return flatten_array($customProperties, self::SEPERATOR);
    }

    protected function recordSegmentIdsAndGetAnonId($payment, $metadata, array & $properties)
    {
        $id = null;

        $ids = [];

        if ($this->anonId !== null)
        {
            $id = $this->anonId;
            $ids = $this->ids;
        }
        else
        {
            if ($payment->hasOrder())
            {
                $ids['id_type'] = 'order';

                $ids['order_id'] = $payment->getPublicOrderId();

                $id = $ids['order_id'];
            }

            if (empty($metadata[AnalyticsEntity::CHECKOUT_ID]) === false)
            {
                $checkoutId = $metadata[AnalyticsEntity::CHECKOUT_ID];

                $ids['id_type'] = 'checkout';

                $ids['checkout_id'] = $checkoutId;

                if (empty($id) === true)
                {
                    $id = $checkoutId;
                }
            }

            // This is a case where we do not have both checkout id and order id.
            // So instead of tracing anything, we want to get some data. Using
            // payment_id as the anonymousId
            if (empty($id) === true)
            {
                $id = $payment->getPublicId();

                $ids['id_type'] = 'payment';
            }
        }

        $this->anonId = $id;
        $this->ids = $ids;

        return $id;
    }


    public function trackPayment(Payment\Entity $payment, $event, array $customProperties = [])
    {
        if ($this->mock === true)
        {
            return;
        }

        try
        {
            $defaults = $this->fillDefaults($payment, $event);

            if (empty($defaults) === true)
            {
                return;
            }

            $customProperties = $this->removeCommonProperties($customProperties);

            $properties = array_merge($defaults['properties'], $customProperties);

            $this->removeSensitiveInformation($properties);

            $defaults['properties'] = $properties;

            $this->events[] = $defaults;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SEGMENT_POST_FAILED);
        }
    }
}
