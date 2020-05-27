<?php


namespace RZP\Models\Merchant\Attribute;

use Illuminate\Database\Query\JoinClause;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Merchant;


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

    public function updateMerchantAttributeValuesById(array $merchantAttributeIds, string $newAttributevalue)
    {
        $attributeIdColumn = $this->repo->merchant_attribute->dbColumn(Entity::ID);

        $attributeValueColumn = $this->repo->merchant_attribute->dbColumn(Entity::VALUE);

        $this->newQuery()
             ->whereIn($attributeIdColumn, $merchantAttributeIds)
             ->update([$attributeValueColumn => $newAttributevalue]);
    }

    /**
     * Get's all the attributeIds that were updateAt between $start and $end
     * such that the corresponding merchants have not yet onboarded (completed at least one payout)
     * @param string $product
     * @param string $group
     * @param string $type
     * @param string $value
     * @param int $start
     * @param int $end
     * @return mixed
     */
    public function getAttributeIdsSetBetweenForMerchantsNotOnboarded(string $product, string $group, string $type, string $value, int $start, int $end)
    {
        $balanceTable = $this->repo->balance->getTableName();

        $payoutsTable = $this->repo->payout->getTableName();

        $attributeIdColumn = $this->repo->merchant_attribute->dbColumn(Entity::ID);

        $attributeMerchantIdColumn = $this->repo->merchant_attribute->dbColumn(Entity::MERCHANT_ID);

        $attributeProductColumn = $this->repo->merchant_attribute->dbColumn(Entity::PRODUCT);

        $attributeGroupColumn = $this->repo->merchant_attribute->dbColumn(Entity::GROUP);

        $attributeTypeColumn = $this->repo->merchant_attribute->dbColumn(Entity::TYPE);

        $attributeValueColumn = $this->repo->merchant_attribute->dbColumn(Entity::VALUE);

        $payoutMerchantIdcolumn = $this->repo->payout->dbColumn(Payout\Entity::MERCHANT_ID);

        $payoutsBalanceIdColumn = $this->repo->payout->dbColumn(Payout\Entity::BALANCE_ID);

        $payoutsStatusColumn = $this->repo->payout->dbColumn(Payout\Entity::STATUS);

        $balanceIdColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ID);

        $balanceTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $attributeUpdatedAtColumn = $this->repo->merchant_attribute->dbColumn(Entity::UPDATED_AT);

        return $this->newQuery()
                    ->selectRaw($attributeIdColumn .','. $attributeMerchantIdColumn. ', count(' .$payoutMerchantIdcolumn. ') as count')
                    ->leftJoin($payoutsTable, function(JoinClause $join) use ($attributeMerchantIdColumn, $payoutMerchantIdcolumn, $payoutsStatusColumn)
                        {
                            $join->on($attributeMerchantIdColumn, '=', $payoutMerchantIdcolumn);
                            $join->where($payoutsStatusColumn, '=', Payout\Status::PROCESSED);

                        })
                    ->leftJoin($balanceTable, function(JoinClause $join) use($payoutsBalanceIdColumn, $balanceIdColumn, $balanceTypeColumn)
                        {
                            $join->on($payoutsBalanceIdColumn, '=', $balanceIdColumn);
                            $join->where($balanceTypeColumn,'=', 'banking');
                        })
                    ->where($attributeProductColumn, $product)
                    ->where($attributeGroupColumn, $group)
                    ->where($attributeTypeColumn, $type)
                    ->where($attributeValueColumn, $value)
                    ->where($attributeUpdatedAtColumn, '>', $start)
                    ->where($attributeUpdatedAtColumn, '<', $end)
                    ->groupBy($attributeIdColumn, $attributeMerchantIdColumn)
                    ->having('count', '=', 0)
                    ->get();
    }
}
