<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'transfer';

    protected $expands = [
        Entity::TO,
    ];

    protected $entityFetchParamRules = [
        Entity::RECIPIENT           => 'sometimes|string|max:20',
        self::EXPAND . '.*'         => 'string|in:recipient_settlement,',
    ];

    protected $appFetchParamRules = [
        Entity::TRANSACTION_ID      => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|size:14',
        Entity::SOURCE              => 'sometimes|string|min:14',
        Entity::RECIPIENT           => 'sometimes|string|min:14'
    ];

    /**
     * Fetch all transfers from a merchant, done on a payment
     *
     * @param string          $type
     * @param string          $paymentId
     * @param Merchant\Entity $merchant
     */
    public function fetchBySourceTypeAndIdAndMerchant(string $type, string $paymentId, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_TYPE, $type)
                    ->where(Entity::SOURCE_ID, $paymentId)
                    ->merchantId($merchant->getId())
                    ->get();
    }

    protected function addQueryParamSource($query, $params)
    {
        $sourceId = $params[Entity::SOURCE];

        Entity::stripSignWithoutValidation($sourceId);

        $query->where(Entity::SOURCE_ID, $sourceId);
    }

    protected function addQueryParamRecipient($query, $params)
    {
        $toId = $params[Entity::RECIPIENT];

        Entity::stripSignWithoutValidation($toId);

        $query->where(Entity::TO_ID, $toId);
    }
}
