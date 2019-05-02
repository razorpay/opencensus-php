<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Constants\Entity as E;

class Repository extends Base\Repository
{
    protected $entity = 'transfer';

    protected $expands = [
        Entity::TO,
    ];

    protected $entityFetchParamRules = [
        Entity::RECIPIENT               => 'sometimes|string|max:20',
        Entity::RECIPIENT_SETTLEMENT_ID => 'filled|string|public_id',
        self::EXPAND . '.*'             => 'filled|string|in:recipient_settlement,',
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

    /**
     * Fetch transfer using id and linked account merchant id
     *
     * @param string          $transferId
     * @param string          $paymentId
     * @param Merchant\Entity $merchant
     */
    public function fetchByPublicIdAndLinkedAccountMerchant(string $id, Merchant\Entity $merchant)
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->newQuery()
                    ->where(Entity::TO_ID, $merchant->getId())
                    ->where(Entity::TO_TYPE, E::MERCHANT)
                    ->where(Entity::SOURCE_TYPE, E::PAYMENT)
                    ->merchantId($merchant->parent->getId())
                    ->findOrFailPublic($id);
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

    protected function addQueryParamRecipientSettlementId($query, $params)
    {
        $id = $params[Entity::RECIPIENT_SETTLEMENT_ID];

        Settlement\Entity::verifyIdAndStripSign($id);

        $query->where(Entity::RECIPIENT_SETTLEMENT_ID, $id);
    }
}
