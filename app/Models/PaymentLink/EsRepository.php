<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;
use RZP\Constants\Es;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::USER_ID,
        Entity::STATUS,
        Entity::STATUS_REASON,
        Entity::VIEW_TYPE,
        Entity::RECEIPT,
        Entity::TITLE,
        Entity::CREATED_AT,
        Entity::SHORT_URL,
    ];

    protected $queryFields = [
        Entity::RECEIPT,
        Entity::TITLE,
        Entity::VIEW_TYPE,
    ];

    public function buildQueryForUserId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::USER_ID, $value);
    }

    public function buildQueryForStatus(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::STATUS, $value);
    }

    public function buildQueryForStatusReason(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::STATUS_REASON, $value);
    }

    public function buildQueryForReceipt(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::RECEIPT, $value);
    }

    public function buildQueryForViewType(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::VIEW_TYPE, $value);
    }
}
