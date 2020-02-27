<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\PaymentEvent as PE;
use RZP\Models\Payment;

trait PaymentEvent
{
    public function trackPaymentEvent(
        array $eventData,
        Payment\Entity $payment = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new PE($payment, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $eventData, $properties);
    }

    public function trackVerifyPaymentEvent(
        array $event,
        Payment\Entity $payment = null,
        \Throwable $ex = null)
    {
        $metaDetails = [
            'metadata' => [
                'payment' => [
                    'id'        => $payment->getPublicId(),
                    'status'    => $payment->getStatus(),
                    'bucket'    => $payment->getVerifyBucket()
                ]
            ],
            'read_key' => array('payment.id'),
            'write_key' => 'payment.id'
        ];

        $customProperties = [
            'status'    => $payment->getStatus(),
            'bucket'    => $payment->getVerifyBucket()
        ];

        $this->trackPaymentEventV2($event, $payment, $ex, $metaDetails, $customProperties);
    }

    public function trackGatewayPaymentEvent(
        array $eventData,
        array $gatewayInput,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $event = new PE(null, $ex, $customProperties);

        $properties = $event->parseGatewayProperties($gatewayInput);

        $metaDetails = [
            'metadata' => [
                'payment' => [
                    'id'      => 'pay_' . $gatewayInput['payment']['id'],
                ]
            ],
            'read_key' => array('payment.id'),
            'write_key' => ''
        ];

        $customProperties+= [
            'auth_type' => $gatewayInput['payment']['auth_type'],
            'gateway'   => $gatewayInput['payment']['gateway']
        ];

        $this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $eventData, $properties);

        $this->trackPaymentEventV2($eventData,null, $ex, $metaDetails, $customProperties);
    }

    public function trackPaymentEventV2(
        array $eventData,
        Payment\Entity $payment = null,
        \Throwable $ex = null,
        array $metaDetails = [],
        array $customProperties = [])
    {
        $event = new PE($payment, $ex, $customProperties, $metaDetails);

        $properties = $event->getProperties();

        $metaDetails = $event->getMetaDetails();

        if (empty($metaDetails) === false)
        {
            $this->trackEvent(PE::EVENT_TYPE, 'v2', $eventData, $properties, $metaDetails['metadata'], $metaDetails['read_key'], $metaDetails['write_key']);
        }
        else
        {
            $this->trackEvent(PE::EVENT_TYPE, 'v2', $eventData, $properties);
        }

        //push events of v1 as well for now
        $this->trackPaymentEvent($eventData, $payment, $ex, $customProperties);

    }
}
