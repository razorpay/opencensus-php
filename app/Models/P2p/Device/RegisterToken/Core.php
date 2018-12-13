<?php

namespace RZP\Models\P2p\Device\RegisterToken;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function create(array $input)
    {
        $entity = $this->repo->newEntity();

        $entity->build($input);

        $this->repo->saveOrFail($entity);

        return $entity;
    }

    public function createWithDeviceData(array $input)
    {
        return $this->create([Entity::DEVICE_DATA => $input]);
    }
}
