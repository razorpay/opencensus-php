<?php

namespace RZP\Models\PaymentLink\PaymentPageItem;

use RZP\Models\Base;
use RZP\Models\Item;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::PAYMENT_LINK_ID,
        Item\Entity::NAME,
        Item\Entity::DESCRIPTION,
        Entity::ITEM_DELETED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $queryFields = [
        Entity::ID,
        Item\Entity::NAME,
        Item\Entity::DESCRIPTION,
        Entity::ITEM_DELETED_AT,
        Entity::MERCHANT_ID,
        Entity::PAYMENT_LINK_ID,
    ];

    public function buildQueryForItemDeletedAt(array & $query, $value)
    {
        if ($value === null)
        {
            $this->addNullFilterForField($query, Entity::ITEM_DELETED_AT);

            return;
        }

        $this->addNotNullFilterForField($query, Entity::ITEM_DELETED_AT);
    }

    public function buildQueryForPaymentLinkId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::PAYMENT_LINK_ID, $value);
    }
}
