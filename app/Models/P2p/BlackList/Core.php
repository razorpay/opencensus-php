<?php

namespace RZP\Models\P2p\BlackList;

use http\Exception\RuntimeException;
use RZP\Models\P2p\Base;

class Core extends Base\Core
{
    /**
     * This function is to create the blacklist
     * @param Base\Entity $blacklist
     * @param array $input
     * @return Entity
     * @throws \RZP\Exception\RuntimeException
     */
    public function create(Base\Entity $blacklist, array $input): Entity
    {
        $entity = $this->build($input);

        $this->repo->saveOrFail($entity);

        return $entity;
    }
}
