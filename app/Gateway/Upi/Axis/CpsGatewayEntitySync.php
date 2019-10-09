<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Gateway\Upi\Base\Entity;

class CpsGatewayEntitySync
{
    const ENTITY_MAP = [
        'id'                  => Entity::ID,
        'action'              => Entity::ACTION,
        'vpa'                 => Entity::VPA,
        'type'                => Entity::TYPE,
        'acquirer'            => Entity::ACQUIRER,
        'bank'                => Entity::BANK,
        'provider'            => Entity::PROVIDER,
        'contact'             => Entity::CONTACT,
        'merchant_reference'  => Entity::MERCHANT_REFERENCE,
        'gateway_merchant_id' => Entity::GATEWAY_MERCHANT_ID,
        'gateway_payment_id'  => Entity::GATEWAY_PAYMENT_ID,
        'npci_reference_id'   => Entity::NPCI_REFERENCE_ID,
        'npci_txn_id'         => Entity::NPCI_TXN_ID,
        'reconciled_at'       => Entity::RECONCILED_AT,
        'email'               => Entity::EMAIL,
        'code'                => Entity::STATUS_CODE,
        'account_number'      => Entity::ACCOUNT_NUMBER,
        'ifsc'                => Entity::IFSC,
        'refundAmount'        => Entity::AMOUNT,
        'payment_id'          => Entity::PAYMENT_ID,
        'amount'              => Entity::AMOUNT,
        'expiry_time'         => Entity::EXPIRY_TIME,

    ];

    public function syncGatewayTransaction(array $gatewayTransaction, array $input)
    {
        $attributes = $this->getMappedAttributes($gatewayTransaction);

        (new Gateway)->syncGatewayTransactionDataFromCps($attributes, $input);
    }

    protected function getMappedAttributes($attributes)
    {
        $mappedAttributes = [];

        foreach($attributes as $key => $value)
        {
            if(isset(self::ENTITY_MAP[$key]) === true)
            {
                $newKey = self::ENTITY_MAP[$key];
                $mappedAttributes[$newKey] = $value;
            }
        }

        return $mappedAttributes;
    }
}
