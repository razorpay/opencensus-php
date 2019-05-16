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
        $refId                = $this->context()->getRequestId();
        $networkTransactionId = $this->context()->handlePrefix() . $this->app['request']->getId();

        $default = [
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID   => $networkTransactionId,
            UpiTransaction\Entity::REF_ID                   => $refId,
        ];

        $cleaned = $this->cleanUpiInput(array_merge($default, $input));

        $defined = [
            UpiTransaction\Entity::STATUS                   => $transaction->getInternalStatus(),
            UpiTransaction\Entity::ACTION                   => $action,
        ];

        $upi = (new UpiTransaction\Core)->create($transaction, array_merge($cleaned, $defined));

        return $upi;
    }

    public function updateUpi(Entity $transaction, array $input)
    {
        $cleaned = $this->cleanUpiInput($input);

        $upi = (new UpiTransaction\Core)->update($transaction, $cleaned);

        return $upi;
    }

    public function findAllUpi(string $action, array $input = [])
    {
        $defined = [
            UpiTransaction\Entity::ACTION                   => $action,
        ];

        $transactionId = $input[UpiTransaction\Entity::TRANSACTION_ID] ?? null;
        $networkTransactionId = $input[UpiTransaction\Entity::NETWORK_TRANSACTION_ID] ?? null;

        if (empty($transactionId) === false)
        {
            $defined[UpiTransaction\Entity::TRANSACTION_ID] = $transactionId;
        }
        else if (empty($networkTransactionId) === false)
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

    protected function cleanUpiInput(array $input): array
    {
        unset($input[UpiTransaction\Entity::TRANSACTION_ID],
              $input[UpiTransaction\Entity::ACTION],
              $input[UpiTransaction\Entity::HANDLE],
              $input[UpiTransaction\Entity::TRANSACTION]);

        return $input;
    }
}
