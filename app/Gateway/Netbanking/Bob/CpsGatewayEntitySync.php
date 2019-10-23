<?php


namespace RZP\Gateway\Netbanking\Bob;

use RZP\Gateway\Netbanking\Base;

class CpsGatewayEntitySync
{
    const ENTITY_MAP = [
        'amount'                     => Base\Entity::AMOUNT,
        'id'                         => Base\Entity::ID,
        'payment_id'                 => Base\Entity::PAYMENT_ID,
        'bank_payment_id'            => Base\Entity::BANK_PAYMENT_ID,
        'status'                     => Base\Entity::STATUS,
        'account_number'             => Base\Entity::ACCOUNT_NUMBER,
        'gateway_merchant_id'        => Base\Entity::MERCHANT_CODE,
        'bank'                       => Base\Entity::BANK
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

        (new Gateway())->syncGatewayTransactionDataFromCps($attributes, $input);
    }
}
