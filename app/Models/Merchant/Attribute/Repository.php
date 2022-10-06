<?php


namespace RZP\Models\Merchant\Attribute;

use Illuminate\Database\Query\JoinClause;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;


class Repository extends Base\Repository
{
    protected $entity = 'merchant_attribute';

    public function getValue(Merchant\Entity $merchant, string $product, string $group, string $type)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchant->getId())
                    ->where(Entity::PRODUCT, $product)
                    ->where(Entity::GROUP, $group)
                    ->where(Entity::TYPE, $type)
                    ->firstOrFail();
    }

    public function getKeyValuesForAllProduct(string $merchantId, string $group, array $types = [])
    {
        $query = $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::GROUP, $group);

        if (empty($types) === false)
        {
            $query->whereIn(Entity::TYPE, $types);
        }

        return $query->get();
    }

    public function getKeyValues(string $merchantId, string $product, string $group, array $types = [], string $column = null, string $orderType = 'asc')
    {
        $query = $this->newQuery()
                       ->where(Entity::MERCHANT_ID, $merchantId)
                       ->where(Entity::PRODUCT, $product)
                       ->where(Entity::GROUP, $group);

        if ($column != null)
        {
            $query->orderBy($column, $orderType);
        }

        if (empty($types) === false)
        {
            $query->whereIn(Entity::TYPE, $types);
        }

        return $query->get();
    }

    public function getValueForProductGroupType(string $merchantId, string $product, string $group,string $type)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::PRODUCT, $product)
                    ->where(Entity::GROUP, $group)
                    ->where(Entity::TYPE, $type)
                    ->first();
    }

    public function updateMerchantAttributeValuesById(array $merchantAttributeIds, string $newAttributevalue)
    {
        $attributeIdColumn = $this->repo->merchant_attribute->dbColumn(Entity::ID);

        $attributeValueColumn = $this->repo->merchant_attribute->dbColumn(Entity::VALUE);

        $this->newQuery()
             ->whereIn($attributeIdColumn, $merchantAttributeIds)
             ->update([$attributeValueColumn => $newAttributevalue]);
    }
}
