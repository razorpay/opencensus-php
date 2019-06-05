<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Merchant;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::TYPE,
        Entity::NOTES,
        Entity::MODEL,
        Entity::STATUS,
        Entity::SOURCE_ID,
        Entity::PARTNER_ID,
        Entity::CREATED_AT,
        Entity::SOURCE_TYPE,
        Entity::TRANSACTION_ID,
        Entity::PARTNER_CONFIG_ID,
    ];

    protected $esFetchParams = [
        Entity::ID,
        Entity::TYPE,
        Entity::MODEL,
        Entity::STATUS,
        Entity::SOURCE_ID,
        Entity::PARTNER_ID,
        Entity::SOURCE_TYPE,
        // here merchant_id is submerchant of partner
        Entity::MERCHANT_ID,
        Entity::TRANSACTION_ID,
        Entity::PARTNER_CONFIG_ID,
    ];

    protected $merchantFields = [
        Merchant\Entity::ID,
    ];

    public function getMerchantFields(): array
    {
        return $this->merchantFields;
    }

    public function buildQueryForId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::ID, $value);
    }

    public function buildQueryForSourceId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::SOURCE_ID, $value);
    }

    public function buildQueryForModel(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::MODEL, $value);
    }

    public function buildQueryForPartnerId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::PARTNER_ID, $value);
    }

    public function buildQueryForMerchantId(array &$query, string $value)
    {
        $attribute = Entity::MERCHANT. '.' .Entity::ID;

        $this->addTermFilter($query, $attribute, $value);
    }

    public function buildQueryForStatus(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::STATUS, $value);
    }

    public function buildQueryForSourceType(array &$query, string $value)
    {
        $this->addTermFilter($query, Entity::SOURCE_TYPE, $value);
    }

    public function buildQueryForTransactionId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::TRANSACTION_ID, $value);
    }

    public function buildQueryForPartnerConfigId(array &$query, string $value)
    {
        $this->addTermFilter($query, Entity::PARTNER_CONFIG_ID, $value);
    }

    public function buildQueryForType(array &$query, string $value)
    {
        $this->addTermFilter($query, Entity::TYPE, $value);
    }
}
