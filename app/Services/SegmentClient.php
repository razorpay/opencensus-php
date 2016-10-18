<?php

namespace RZP\Services;

use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use Segment;
use RZP\Trace\TraceCode;

class SegmentClient
{
    protected $mode;

    protected $config;

    protected $trace;

    protected $repo;

    protected $version;

    public function __construct($app)
    {
        $this->mode = $app['rzp.mode'];

        $this->trace = $app['trace'];

        $this->config = $app['config'];

        $this->repo = $app['repo'];

        $key = $this->config['segment.write_key'];

        $this->version = "1.0";

        Segment::init($key, ['consumer' => 'file',
                             'debug' => true,
                            'filename' => $this->config['segment.storage_path']]);

    }

    protected function fillDefaults($payment, $event)
    {
        $metadata = $payment->getMetadata();

        $properties = [
            'payment_id' => $payment->getId(),
            'mode' => $this->mode,
            'merchantId' => $payment->merchant->getId(),
            'amount' => $payment->getAmount(),
            'method' => $payment->getMethod(),
            'metadata' => $metadata,
            'version' => $this->version,
        ];

        $merchant = $payment->merchant;

        $properties['fee_bearer'] = $merchant->isFeeBearerCustomer();

        $order = $payment->order;

        $id = null;

        $orderId = null;

        $checkoutId = null;

        if ($order !== null)
        {
            $properties['order'] = $order->toArrayPublic();

            $orderId = $order->getId();
        }

        if (isset($metadata[AnalyticsEntity::CHECKOUT_ID]))
        {
            $checkoutId = $metadata[AnalyticsEntity::CHECKOUT_ID];
        }

        $properties['order_id'] = $orderId;

        $properties['checkout_id'] = $checkoutId;

        $id  = $orderId !== null ? $orderId : $checkoutId;

        // This is a case where we do not have both checkout id and order id. So
        // for the moment, we do not want to send data to segment. Fixing this
        // needs api to generate a checkout id, when not available and allowing
        // frontend/custom checkout to consume so.
        if ($id === null)
        {
            $this->trace->warning(TraceCode::SEGMENT_ID_UNAVAILABLE, $properties);

            return null;
        }

        $properties = flatten_array($properties);

        $defaults = [
            'anonymousId' => $id,
            'event' => $event,
            'properties' => $properties
        ];

        return $defaults;
    }

    protected function processCustomProperties(array $customProperties)
    {
        if (isset($customProperties['terminals']))
        {
            $terminals = $customProperties['terminals'];

            unset($customProperties['terminals']);

            $terminalIds = [];

            foreach($terminals as $terminal)
            {
                $terminalIds[] = $terminal->getId();
            }

            $customProperties['terminal_ids'] = $terminalIds;

            $customProperties['terminals_count'] = count($terminalIds);
        }

        $flattened  =  flatten_array($customProperties);

        return $flattened;
    }

    public function trackPayment(PaymentEntity $payment, $event, array $customProperties=[])
    {
        $defaults = $this->fillDefaults($payment, $event);

        if ($defaults === null)
        {
            return;
        }

        $customProperties = $this->processCustomProperties($customProperties);

        $properties = array_merge($defaults['properties'], $customProperties);

        $defaults['properties'] = $properties;

        //TODO: do something with the status and also handle exceptions here if any
        $status = Segment::track($defaults);
    }
}