<?php

namespace RZP\Gateway\Cybersource;

use RZP\Gateway\Cybersource\Entity;

class CpsGatewayEntitySync
{
    const ENTITY_MAP = [
        'gateway_reference_id1'     => Entity::REF,
        'gateway_reference_id2'     => Entity::GATEWAY_TRANSACTION_ID,
        'gateway_reference_id3'     => Entity::AUTHORIZATION_CODE,
        'rrn'                       => Entity::RECEIPT_NUMBER,
        'enrollment_status'         => Entity::VERES_ENROLLED,
        'authentication_status'     => Entity::PARES_STATUS,
        'avs_code'                  => Entity::AVS_CODE,
        'cv_code'                   => Entity::CV_CODE,
        'processor_code'            => Entity::MERCHANT_ADVICE_CODE,
        'status'                    => Entity::STATUS,
        'payment_id'                => Entity::PAYMENT_ID,
        'commerce_indicator'        => Entity::COMMERCE_INDICATOR,
        'eci'                       => Entity::ECI,
        'reason_code'               => Entity::REASON_CODE,
        'xid'                       => Entity::XID,
        'cavv'                      => Entity::CAVV,
        'refund_id'                 => Entity::REFUND_ID,
        'refundAmount'              => Entity::AMOUNT,
        'received'                  => Entity::RECEIVED,
        'processorResponse'         => Entity::PROCESSOR_RESPONSE,
        'card_category'             => Entity::CARD_CATEGORY,
        'card_group'                => Entity::CARD_GROUP,
    ];

    protected function getMappedAttributes($attributes)
    {
        $mappedAttributes = [];

        foreach ($attributes as $key => $value)
        {
            if (isset(self::ENTITY_MAP[$key]) === true)
            {
                $newKey                    = self::ENTITY_MAP[$key];
                $mappedAttributes[$newKey] = $value;
            }
        }

        return $mappedAttributes;
    }

    public function syncGatewayTransaction(array $gatewayTransaction, array $input)
    {
        $attributes = $this->getMappedAttributes($gatewayTransaction);

        (new Gateway)->syncGatewayTransactionDataFromCps($attributes, $input);
    }
}
