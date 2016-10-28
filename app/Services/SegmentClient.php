<?php

namespace RZP\Services;

use RZP\Trace\TraceCode;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use GuzzleHttp\Client;
use RZP\Models\Base\UniqueIdEntity;

class SegmentClient
{
    protected $app;

    protected $mode;

    protected $config;

    protected $trace;

    protected $version;

    // list of events that needs to be batched
    protected $events;

    // lumberjack segment url endpoint
    const LUMBERJACK_SEGMENT_URLPATTERN = 'segment_post';

    // current version of this implementation
    const VERSION = "1.0";

    // guzzle timeout for posting to lumberjack
    const CONNECT_TIMEOUT = 1;

    // seperator for array flattening
    const SEPERATOR = '_';

    // list of sensitive keys to exclude from sengding to segment
    // even if the api has these variables

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

    public function __construct($app)
    {
        $this->app = $app;

        $this->mode = $app['rzp.mode'];

        $this->trace = $app['trace'];

        $this->config = $app['config'];

        $this->events = [];
    }

    protected function fetchTerminalData(\RZP\Models\Terminal\Entity $terminal, PaymentEntity $payment)
    {
        $data = [];

        try
        {
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
        }

        catch(\Exception $e)
        {
            $traceMessage = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ];

            $this->trace->warning(TraceCode::SEGMENT_POST_FAILED, $traceMessage);
        }


        return $data;
    }

    protected function fillDefaults(PaymentEntity $payment, $event)
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

            $terminalId = $terminal->getPublicId();

            $terminalDetails = $this->fetchTerminalData($terminal, $payment);
        }

        $properties = [
            'payment_id'        => $payment->getPublicId(),
            'mode'              => $this->mode,
            'merchant_id'       => $payment->merchant->getId(),
            'merchant_name'     => $payment->merchant->getBillingLabelElseName(),
            'amount'            => $payment->getAmount(),
            'method'            => $payment->getMethod(),
            'requestId'         => $this->app['request']->getId(),
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

        $order = null;

        $id = null;

        if ($payment->getApiOrderId() !== null)
        {
            $order = $payment->order;

            $orderId = $order->getPublicId();

            $properties['id_type'] = 'order';

            $properties['order_id'] = $orderId;

            $id = $orderId;
        }

        if (empty($metadata[AnalyticsEntity::CHECKOUT_ID]) === false)
        {
            $checkoutId = $metadata[AnalyticsEntity::CHECKOUT_ID];

            $properties['id_type'] = 'checkout';

            $properties['checkout_id'] = $checkoutId;

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
            $this->trace->warning(TraceCode::SEGMENT_ID_UNAVAILABLE, $properties);

            $id = $payment->getPublicId();

            $properties['id_type'] = 'payment';
        }

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
        $ljConfig = $this->config->get('applications.lumberjack');

        $url = $ljConfig['url'].self::LUMBERJACK_SEGMENT_URLPATTERN;

        $secret = $ljConfig['secret'];

        $data = json_encode($this->events);

        $signature = hash_hmac('sha1', $data, $secret);

        $headers = [
            'content-type' => 'application/json',
            'x-signature' => $signature
        ];

        // TODO: make this async using guzzler async events
        $client = new Client(['headers' => $headers, 'http_errors' => false]);

        try
        {
            $response = $client->request('POST', $url, ['json' => $this->events,
                                                        'connect_timeout' => self::CONNECT_TIMEOUT]);
        }
        catch(\Requests_Exception $e)
        {
            $traceMessage = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ];

            $this->trace->warning(TraceCode::SEGMENT_POST_FAILED, $traceMessage);
        }
        catch(\Exception $e)
        {
            $traceMessage = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ];

            $this->trace->warning(TraceCode::SEGMENT_POST_FAILED, $traceMessage);
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

    public function trackPayment(PaymentEntity $payment, $event, array $customProperties = [])
    {
        $isMock = $this->config['segment.is_mock'];

        if ($isMock === true)
        {
            return;
        }

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
}
