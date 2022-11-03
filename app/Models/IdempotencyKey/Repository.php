<?php

namespace RZP\Models\IdempotencyKey;

use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'idempotency_key';

    public function findByIdempotencyKeyAndMerchant(string $idempotencyKey, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Entity::IDEMPOTENCY_KEY, $idempotencyKey)
                    ->merchantId($merchant->getId())
                    ->first();
    }

    public function findByIdempotencyKeyAndSourceTypeAndMerchantId(string $idempotencyKey,
                                                                 string $sourceType,
                                                                 string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::IDEMPOTENCY_KEY, $idempotencyKey)
                    ->where(Entity::SOURCE_TYPE, $sourceType)
                    ->merchantId($merchantId)
                    ->first();
    }

    protected function addQueryParamId(BuilderEx $query, array $params)
    {
        $id = $params[Entity::ID];

        $idColumn = $this->dbColumn(Entity::ID);

        Entity::verifyIdAndSilentlyStripSign($id);

        $query->where($idColumn, $id);
    }

    public function getPayoutServiceIdempotencyKeyForSourceTypePayout(string $payoutId)
    {
        $tableName = 'idempotency_keys';

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where source_id = '$payoutId' and source_type = 'payout' limit 1");
    }
}
