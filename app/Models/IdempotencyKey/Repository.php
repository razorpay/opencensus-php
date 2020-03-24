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

    protected function addQueryParamId(BuilderEx $query, array $params)
    {
        $id = $params[Entity::ID];

        $idColumn = $this->dbColumn(Entity::ID);

        Entity::verifyIdAndSilentlyStripSign($id);

        $query->where($idColumn, $id);
    }
}
