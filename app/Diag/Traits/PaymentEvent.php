<?php

namespace RZP\Diag\Traits;

use RZP\Diag\Event\PaymentEvent as PE;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
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

    public function trackTimeoutPaymentEvent(
        array $event,
        Payment\Entity $payment = null,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $metaDetails = [
            'metadata' => [
                'payment' => [
                    'id'        => $payment->getPublicId(),
                    'status'    => $payment->getStatus()
                ]
            ],
            'read_key' => array('payment.id'),
            'write_key' => 'payment.id'
        ];

        $customProperties +=[
            'status'              => $payment->getStatus(),
            'timeout_window'      => $payment->getTimeoutWindow(),
            'created_at'          => $payment->getCreatedAt(),
            'internal_error_code' => $payment->getInternalErrorCode(),
            'error_desc'          => $payment->getErrorDescription(),
        ];

        $this->trackPaymentEventV2($event, $payment, $ex, $metaDetails, $customProperties);
    }

    public function trackVerifyPaymentEvent(
        array $event,
        Payment\Entity $payment = null,
        \Throwable $ex = null,
        array $customProperties = [])
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

        $customProperties +=[
            'status'              => $payment->getStatus(),
            'bucket'              => $payment->getVerifyBucket(),
            'verify_at'           => $payment->getVerifyAt(),
            'created_at'          => $payment->getCreatedAt(),
            'internal_error_code' => $payment->getInternalErrorCode(),
            'error_desc'          => $payment->getErrorDescription(),
        ];

        $this->trackPaymentEventV2($event, $payment, $ex, $metaDetails, $customProperties);
    }

    public function trackGatewayPaymentEvent(
        array $eventData,
        array $gatewayInput,
        \Throwable $ex = null,
        array $customProperties = [])
    {
        $customProperties+= [
            'auth_type' => $gatewayInput['payment']['auth_type'],
            'gateway'   => $gatewayInput['payment']['gateway']
        ];

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

        //$this->trackEvent(PE::EVENT_TYPE, PE::EVENT_VERSION, $eventData, $properties);

        $this->trackEvent(PE::EVENT_TYPE, 'v2', $eventData, $properties, $metaDetails['metadata'], $metaDetails['read_key'], $metaDetails['write_key']);
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
            $event = $this->trackEvent(PE::EVENT_TYPE, 'v2', $eventData, $properties, $metaDetails['metadata'], $metaDetails['read_key'], $metaDetails['write_key']);
        }
        else
        {
            $event = $this->trackEvent(PE::EVENT_TYPE, 'v2', $eventData, $properties);
        }

        //Deprecating v1 events
        //$this->trackPaymentEvent($eventData, $payment, $ex, $customProperties);

        return $event;
    }
}
