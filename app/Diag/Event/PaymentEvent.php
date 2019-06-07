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

    protected function removeSenstiveFields()
    {
        // currently just doing based on the input keys, can add strict validations like luhn check etc
        unset($this->customProperties['card']);
        unset($this->customProperties['card_number']);
        unset($this->customProperties['number']);
        unset($this->customProperties['notes']);
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
                'id'           => $payment->getPublicId(),
                'amount'       => $payment->getAmount(),
                'currency'     => $payment->getCurrency(),
                'method'       => $payment->getMethod(),
                'issuer'       => $payment->getIssuer(),
                'type'         => $payment->getTransactionType(),
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
                'card_iin'      => $card->getIin(),
                'card_network'  => $card->getNetwork(),
                'card_type'     => $card->getType(),
                'card_country'  => $card->getCountry(),
                'international' => $payment->isInternational(),
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
}
