<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'risk';

    protected $merchantIdRequiredForMultipleFetch = false;

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = [
        Entity::PAYMENT_ID    => 'sometimes|public_id',
        Entity::FRAUD_TYPE    => 'sometimes|string|max:20',
        Entity::SOURCE        => 'sometimes|boolean',
        Entity::COMMENTS      => 'sometimes|string|max:255',
        Entity::MAXMIND_SCORE => 'sometimes|integer',
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID   => 'sometimes|public_id',
        Entity::PAYMENT_ID    => 'sometimes|public_id',
        Entity::FRAUD_TYPE    => 'sometimes|string|max:20',
        Entity::SOURCE        => 'sometimes|string|max:20',
        Entity::MAXMIND_SCORE => 'sometimes|integer',
        Entity::COMMENTS      => 'sometimes|string|max:255',
    ];


    public function fetchByPaymentId(string $paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->get();
    }

    public function fetchByPaymentIdAndMerchantId(
        string $paymentId,
        string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->get();
    }
}
