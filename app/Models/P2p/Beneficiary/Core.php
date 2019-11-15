<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function findOrCreate(Base\Entity $beneficiary, array $input): Entity
    {
        $input = [
            Entity::ENTITY_TYPE => $beneficiary->getP2pEntityName(),
            Entity::ENTITY_ID   => $beneficiary->getId(),
        ];

        $existing = $this->repo->fetchByEntity($input);

        if ($existing instanceof Entity)
        {
            return $existing;
        }

        $input[Entity::NAME] = $input[Entity::NAME] ?? $beneficiary->getBeneficiaryName();

        $entity = $this->build($input);

        $this->repo->saveOrFail($entity);

        return $entity;
    }

    public function deregister()
    {
        $query = $this->repo->newP2pQuery();

        return $query->delete();
    }
}
