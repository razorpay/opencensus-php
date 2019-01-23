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
    public function createUpi(Entity $transaction, string $action)
    {
        $input = [
            UpiTransaction\Entity::STATUS   => $transaction->getStatus(),
            UpiTransaction\Entity::ACTION   => $action,
        ];

        $upi = (new UpiTransaction\Core)->create($transaction, $input);

        return $upi;
    }

    public function updateUpi(Entity $transaction, array $input)
    {
        $upi = (new UpiTransaction\Core)->update($transaction, $input);

        return $upi;
    }
}
