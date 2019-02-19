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
}
