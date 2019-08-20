<?php

namespace RZP\Models\P2p\BankAccount\Bank;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function fetchAll(array $input): PublicCollection
    {
        $banks = $this->repo->newP2pQuery()
                            ->get();

        return $banks;
    }

    public function retrieveById(string $id): Entity
    {
        $bank = $this->repo->newP2pQuery()
                           ->findOrFailPublic($id);

        return $bank;
    }

    public function createOrUpdateMany(array $input): PublicCollection
    {
        $entities = [];

        $allBanks = $this->repo->newP2pQuery()->get();

        foreach ($input as $item)
        {
            $existing = $this->getExistingBank($allBanks, $item);

            if ($existing instanceof Entity)
            {
                $entities[] = $this->update($existing, $item);
            }
            else
            {
                $entities[] = $this->create($item);
            }
        }

        return new PublicCollection($entities);
    }

    public function create(array $input): Entity
    {
        $bank = $this->repo->getEntityObject();

        $bank->build($input);

        $this->repo->saveOrFail($bank);

        return $bank;
    }

    public function update(Entity $bank, array $input): Entity
    {
        $bank->edit($input);

        $this->repo->saveOrFail($bank);

        return $bank;
    }

    public function getExistingBank(PublicCollection $allBanks, array $input)
    {
        foreach ($allBanks as $bank)
        {
            // Current Logic is on UPI IIN
            if ($bank->getUpiIin() === $input[Entity::UPI_IIN])
            {
                return $bank;
            }
        }
    }
}
