<?php

namespace RZP\Diag\Event;

use RZP\Models\Payment;

class PaymentFraudEvent extends Event
{
    const EVENT_TYPE = 'payment-fraud-events';
    const EVENT_VERSION = 'v1';

    protected $payment = null;
    protected $merchant = null;

    protected function getEventProperties()
    {
        $properties = [];

        $this->payment = (new Payment\Repository)->findOrFailPublic($this->entity->getPaymentId());

        $this->addPaymentFraudDetails($properties);

        $this->addPaymentDetails($properties);

        $this->addMerchantDetails($properties);

        return $properties;
    }

    private function addPaymentFraudDetails(array &$properties)
    {
        $paymentFraud = $this->entity;

        $properties['payment_fraud'] = [
            'id'                      => $paymentFraud->getPublicId(),
            'payment_id'              => $paymentFraud->getPaymentId(),
            'base_amount'             => (int)$paymentFraud->getBaseAmount(),
            'reported_to_razorpay_at' => (int)$paymentFraud->getReportedToRazorpayAt(),
            'reported_to_issuer_at'   => (int)$paymentFraud->getReportedToIssuerAt(),
        ];
    }

    private function addPaymentDetails(array &$properties)
    {
        $payment = $this->payment;

        $properties['payment'] = [
            'id'           => $payment->getPublicId(),
            'amount'       => $payment->getAmount(),
            'base_amount'  => $payment->getBaseAmount(),
            'currency'     => $payment->getCurrency(),
            'method'       => $payment->getMethod(),
            'issuer'       => $payment->getIssuer(),
            'type'         => $payment->getTransactionType(),
            'gateway'      => $payment->getGateway(),
            'created_at'   => $payment->getCreatedAt(),
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

    private function addMerchantDetails(array &$properties)
    {
        $merchant = $this->payment->merchant;

        $properties['merchant'] = [
            'id'        => $merchant->getId(),
            'name'      => $merchant->getBillingLabel(),
            'mcc'       => $merchant->getCategory(),
            'category'  => $merchant->getCategory2(),
        ];
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
