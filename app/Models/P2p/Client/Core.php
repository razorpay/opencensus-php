<?php

namespace RZP\Models\P2p\Client;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Client\Repository;
use RZP\Models\P2p\Client\Validator;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        // TODO: Use $this->build , remove this
        $client = $this->repo->getEntityObject();

        $client->build($input);

        $this->repo->saveOrFail($client);

        return $client;
    }
}
