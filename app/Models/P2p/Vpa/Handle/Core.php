<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Client;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function add(array $input): Entity
    {
        $handle = $this->repo->getEntityObject();

        $handle->build($input);

        $this->repo->saveOrFail($handle);

        return $handle;
    }

    public function update(Entity $handle, array $input): Entity
    {
        return $this->repo->transaction(function () use ($handle, $input)
        {
            $handle->edit($input);

            $this->repo->saveOrFail($handle);

            if (isset($input[Entity::CLIENT]) === true)
            {
                $input[Entity::CLIENT][Client\Entity::HANDLE] = $handle->getCode();

                $client = (new Client\Core())->createOrUpdate($handle, $input[Entity::CLIENT]);
            }

            return $handle;
        });
    }

    public function findByAcquirer(string $acquirer, bool $active)
    {
        return $this->repo->findByAcquirer($acquirer, $active);
    }
}
