<?php

namespace RZP\Diag\Event;

class PaymentEvent extends Event
{
    const EVENT_TYPE = 'payment-events';
    const EVENT_VERSION = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addPaymentDetails($properties);

        $this->addMerchantDetails($properties);

        return $properties;
    }

    public function parseGatewayProperties(array $input)
    {
        $properties = [
            'payment' => [
                'id'            => 'pay_' . $input['payment']['id'],
                'amount'        => $input['payment']['amount'],
                'currency'      => $input['payment']['currency'],
                'method'        => $input['payment']['method'],
                'gateway'       => $input['payment']['gateway'],
                'international' => $input['payment']['international'],
            ],
            'merchant'  => [
                'id'        => $input['merchant']['id'],
                'name'      => $input['merchant']['billing_label'],
                'mcc'       => $input['merchant']['category'],
                'category'  => $input['merchant']['category2'],
            ]
        ];

        if (isset($input['card']) === true)
        {
            $properties['payment'] += [
                'card_iin'         => $input['card']['iin'],
                'card_last4'       => $input['card']['last4'],
                'card_network'     => $input['card']['network'],
                'card_type'        => $input['card']['type'],
                'card_country'     => $input['card']['country'],
            ];
        }

        $properties['properties'] = $this->customProperties;

        return $properties;
    }

    protected function removeSenstiveFields()
    {
        // currently just doing based on the input keys, can add strict validations like luhn check etc
        unset($this->customProperties['card']);
        unset($this->customProperties['card_number']);
        unset($this->customProperties['number']);
        unset($this->customProperties['notes']);

        unset($this->metaDetails['metadata']['payment']['card']);
        unset($this->metaDetails['metadata']['payment']['card_number']);
        unset($this->metaDetails['metadata']['number']);
        unset($this->metaDetails['metadata']['notes']);

        unset($this->customProperties['payment']['card']);
        unset($this->customProperties['payment']['card_number']);
        unset($this->customProperties['payment']['number']);
        unset($this->customProperties['payment']['notes']);
    }

    private function addMerchantDetails(array &$properties)
    {
        $merchant = $this->entity->merchant;

        $properties['merchant'] = [
                'id'        => $merchant->getId(),
                'name'      => $merchant->getBillingLabel(),
                'mcc'       => $merchant->getCategory(),
                'category'  => $merchant->getCategory2(),
        ];
    }

    private function addPaymentDetails(array &$properties)
    {
        $payment = $this->entity;

        $properties['payment'] = [
                'id'             => $payment->getPublicId(),
                'amount'         => $payment->getAmount(),
                'base_amount'    => $payment->getBaseAmount(),
                'currency'       => $payment->getCurrency(),
                'method'         => $payment->getMethod(),
                'issuer'         => $payment->getIssuer(),
                'type'           => $payment->getTransactionType(),
                'gateway'        => $payment->getGateway(),
                'recurring'      => $payment->isRecurring(),
                'recurring_type' => $payment->getRecurringType(),
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
                'card_last4'        => $card->getLast4(),
                'card_iin_headless' => $card->isHeadLessOtp(),
                'card_network'      => $card->getNetwork(),
                'card_type'         => $card->getType(),
                'card_country'      => $card->getCountry(),
                'card_fingerprint'  => $card->getGlobalFingerPrint(),
                'international'     => $payment->isInternational(),
            ];
        }

        if ($payment->hasOrder() === true)
        {
            $order = $payment->order;

            $properties['order'] = [
                'id'       => $order->getPublicId(),
                'amount'   => $order->getAmount(),
                'currency' => $order->getCurrency()
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
                        'payment' => [
                            'id' => $this->entity->getPublicId()
                        ]
                    ],
                    'read_key' => array('payment.id'),
                    'write_key' => ''
                ];
            }

            if (empty($this->entity->getMetadata()) === false)
            {
                $this->metaDetails['metadata']['payment']['metadata'] = $this->entity->getMetadata();

                $this->metaDetails['write_key'] = 'payment.id';
            }
        }

        return $this->metaDetails;
    }
}
