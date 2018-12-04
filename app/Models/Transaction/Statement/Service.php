<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Transaction;

/**
 * Class Service
 *
 * @package RZP\Models\Transaction\Statement
 */
class Service extends Transaction\Service
{
    public function fetchMultiple(array $input)
    {
        // TODO get banking balance id
        $input[Entity::BALANCE_ID]  = $this->merchant->bankingBalance->getId();

        $statements = $this->repo->statement->fetch($input, $this->merchant->getId());

        return $statements->toArrayPublic();
    }

    public function fetch(string $id, array $input)
    {
        $statement = $this->repo->statement->findByPublicIdAndMerchant($id, $this->merchant);

        return $statement->toArrayPublic();
    }
}
