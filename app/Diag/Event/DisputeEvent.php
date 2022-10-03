<?php

namespace RZP\Diag\Event;

class DisputeEvent extends Event
{
    const EVENT_TYPE = 'dispute-events';
    const EVENT_VERSION = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addDisputeDetails($properties);

        $this->addPaymentDetails($properties);

        $this->addMerchantDetails($properties);

        return $properties;
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
        $payment = $this->entity->payment;

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

    private function addDisputeDetails(array &$properties)
    {
        $dispute = $this->entity;

        $properties['dispute'] = [
                'id'           => $dispute->getPublicId(),
                'amount'       => $dispute->getAmount(),
                'base_amount'  => $dispute->getBaseAmount(),
                'currency'     => $dispute->getCurrency(),
                'phase'        => $dispute->getPhase(),
                'reason_code'  => $dispute->getReasonCode(),
                'status'       => $dispute->getStatus(),
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
                        'dispute' => [
                            'id' => $this->entity->getPublicId()
                        ]
                    ],
                    'read_key' => array('dispute.id'),
                    'write_key' => ''
                ];
            }
        }

        return $this->metaDetails;
    }
}
