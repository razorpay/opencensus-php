<?php

namespace RZP\Models\BankingAccount\State;

use RZP\Models\Base;
use RZP\Models\BankingAccount;
use RZP\Models\Base\PublicEntity;

class Core extends Base\Core
{
    /**
     * @param array        $input
     * @param PublicEntity $maker
     * @param BankingAccount\Entity $bankingAccount
     *
     * @return Entity $state
     */
    public function createForMakerAndEntity(array $input, PublicEntity $maker, BankingAccount\Entity $bankingAccount)
    {
        $state = $this->create($input);

        $makerEntityName = $maker->getEntity();

        $state->$makerEntityName()->associate($maker);

        $state->bankingAccount()->associate($bankingAccount);

        $this->repo->saveOrFail($state);

        return $state;
    }

    /**
     * @param array $input
     *
     * @return Entity
     */
    protected function create(array $input): Entity
    {
        $state = new Entity;

        $state->build($input);

        return $state;
    }
}
