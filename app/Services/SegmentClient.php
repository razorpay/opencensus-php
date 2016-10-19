<?php

namespace RZP\Services;

use Segment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;

class SegmentClient
{
    protected $mode;

    protected $config;

    protected $trace;

    protected $version;

    public function __construct($app)
    {
        $this->mode = $app['rzp.mode'];

        $this->trace = $app['trace'];

        $this->config = $app['config'];

        $key = $this->config['segment.write_key'];

        $this->version = "1.0";

        Segment::init($key, [
                             'consumer'     => 'file',
                             'debug'        => $this->config['segment.debug'],
                             'filename'     => $this->config['segment.storage_path']
                             ]);
    }

    protected function fillDefaults($payment, $event)
    {
        $metadata = $payment->getMetadata();

        $properties = [
            'payment_id'        => $payment->getPublicId(),
            'mode'              => $this->mode,
            'merchant_id'       => $payment->merchant->getId(),
            'merchant_name'     => $payment->merchant->getName(),
            'amount'            => $payment->getAmount(),
            'method'            => $payment->getMethod(),
            'gateway'           => $payment->getGateway(),
            'metadata'          => $metadata,
            'version'           => $this->version,
        ];

        $merchant = $payment->merchant;

        $properties['fee_bearer'] = $merchant->isFeeBearerCustomer();

        $order = $payment->order;

        $id = null;

        if (empty($order) === false)
        {
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

        $properties = flatten_array($properties);

        $defaults = [
            'anonymousId'   => $id,
            'event'         => $event,
            'properties'    => $properties
        ];

        return $defaults;
    }

    protected function processCustomProperties(array $customProperties)
    {
        if (empty($customProperties['terminals']) === false)
        {
            $terminals = $customProperties['terminals'];

            unset($customProperties['terminals']);

            $terminalIds = [];

            foreach ($terminals as $terminal)
            {
                $terminalIds[] = $terminal->getId();
            }

            $customProperties['terminal_ids'] = $terminalIds;

            $customProperties['terminals_count'] = count($terminalIds);
        }

        $flattened = flatten_array($customProperties);

        return $flattened;
    }

    public function trackPayment(PaymentEntity $payment, $event, array $customProperties = [])
    {
        $defaults = $this->fillDefaults($payment, $event);

        if (empty($defaults) === true)
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
