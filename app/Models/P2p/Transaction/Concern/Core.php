<?php

namespace RZP\Models\P2p\Transaction\Concern;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Transaction;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function create(Transaction\Entity $transaction, array $input): Entity
    {
        $entity = $this->build($input);

        $entity->associateTransaction($transaction);

        $this->repo->saveOrFail($entity);

        return $entity;
    }

    public function update(Entity $concern, array $input): Entity
    {
        unset($input[Entity::ID], $input[Entity::TRANSACTION_ID]);

        $concern->edit($input);

        $this->repo->saveOrFail($concern);

        return $concern;
    }

    public function findAll(array $input)
    {
        return $this->repo->findAll($input);
    }
}
