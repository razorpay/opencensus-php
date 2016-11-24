<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Models\Transfer;
use RZP\Models\Transaction;

class Core extends Base\Core
{
    /**
     * Creates and saves a new transfer entity
     *
     * @param  Base\Entity        $from        Source entity for transfer
     * @param  Base\Entity        $to          Recieving entity for transfer
     * @param  Transaction\Entity $transaction Transaction Entity
     * @return Transfer\Entity
     */
    public function createTransfer(Base\Entity $from, Base\Entity $to, Transaction\Entity $transaction)
    {
        $transfer = new Entity;

        $transferData = [
            Entity::FROM            => $from->getEntityName(),
            Entity::FROM_ID         => $from->getId(),
            Entity::TO              => $to->getEntityName(),
            Entity::TO_ID           => $to->getId(),
            Entity::AMOUNT          => $transaction->getAmount()
        ];

        $transfer->transaction()->associate($transaction);

        $transfer->build($transferData);

        $this->repo->saveOrFail($transfer);

        return $transfer;
    }
}
