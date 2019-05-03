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
            UpiTransaction\Entity::STATUS   => $transaction->getInternalStatus(),
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

    public function findAllUpi(string $action, array $input = [])
    {
        $defined = [
            UpiTransaction\Entity::ACTION                   => $action,
        ];

        $networkTransactionId = $input[UpiTransaction\Entity::NETWORK_TRANSACTION_ID] ?? null;

        if (empty($networkTransactionId) === false)
        {
            $defined[UpiTransaction\Entity::NETWORK_TRANSACTION_ID] = $networkTransactionId;
        }
        else
        {
            throw $this->logicException('Invalid find parameters', $input);
        }

        $upi = (new UpiTransaction\Core)->findAll($defined);

        return $upi;
    }
}
