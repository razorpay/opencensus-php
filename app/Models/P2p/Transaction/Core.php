<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function create(Properties $properties, array $input): Entity
    {
        $transaction = $this->build($input);

        $properties->attachToTransaction($transaction);

        $this->repo->saveOrFail($transaction);

        return $transaction;
    }

    public function update(Entity $transaction, array $input): Entity
    {
        $this->repo->saveOrFail($transaction);

        return $transaction;
    }

    public function createUpi(Entity $transaction, string $action, array $input = [])
    {
        $defined = [
            UpiTransaction\Entity::STATUS   => $transaction->getStatus(),
            UpiTransaction\Entity::ACTION   => $action,
        ];

        $upi = (new UpiTransaction\Core)->create($transaction, array_merge($input, $defined));

        return $upi;
    }

    public function updateUpi(Entity $transaction, array $input)
    {
        $upi = (new UpiTransaction\Core)->update($transaction, $input);

        return $upi;
    }
}
