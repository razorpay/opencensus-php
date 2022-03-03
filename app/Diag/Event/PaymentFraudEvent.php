<?php

namespace RZP\Diag\Event;

use RZP\Models\Payment;

class PaymentFraudEvent extends Event
{
    const EVENT_TYPE = 'payment-fraud-events';
    const EVENT_VERSION = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addPaymentFraudDetails($properties);

        $this->addPaymentDetails($properties);

        return $properties;
    }

    private function addPaymentFraudDetails(array &$properties)
    {
        $paymentFraud = $this->entity;

        $properties['payment_fraud'] = [
            'id'                      => $paymentFraud->getPublicId(),
            'payment_id'              => $paymentFraud->getPaymentId(),
            'reported_to_razorpay_at' => (int)$paymentFraud->getReportedToRazorpayAt(),
            'reported_to_issuer_at'   => (int)$paymentFraud->getReportedToIssuerAt(),
        ];
    }

    private function addPaymentDetails(array &$properties)
    {
        $payment = (new Payment\Repository)->findOrFailPublic($this->entity->getPaymentId());

        $properties['payment'] = [
            'id'           => $payment->getPublicId(),
            'amount'       => $payment->getAmount(),
            'base_amount'  => $payment->getBaseAmount(),
            'currency'     => $payment->getCurrency(),
            'method'       => $payment->getMethod(),
            'issuer'       => $payment->getIssuer(),
            'type'         => $payment->getTransactionType(),
            'gateway'      => $payment->getGateway(),
        ];

        // upi properties
        if ($payment->isUpi() === true)
        {
            $properties['payment'] += [
                'vpa'   => $payment->getVpa()
            ];
        }

        // card properties
        if ($payment->hasCard() === true)
        {
            $card = $payment->card;

            $properties['payment'] += [
                'card_iin'          => $card->getIin(),
                'card_iin_headless' => $card->isHeadLessOtp(),
                'card_network'      => $card->getNetwork(),
                'card_type'         => $card->getType(),
                'card_country'      => $card->getCountry(),
                'international'     => $payment->isInternational(),
            ];
        }

        $metadata = $payment->getMetadata();

        if (empty($metadata) === false)
        {
            $properties['metadata'] = $metadata;
        }
    }

    protected function getEventMetaDetails()
    {
        if ($this->entity !== null)
        {
            if ((empty($this->metaDetails) === true))
            {
                $this->metaDetails = [
                    'metadata' => [
                        'payment_fraud' => [
                            'id' => $this->entity->getPublicId()
                        ]
                    ],
                    'read_key' => array('payment_fraud.id'),
                    'write_key' => ''
                ];
            }
        }

        return $this->metaDetails;
    }
}
