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
        $this->build($input);

        throw new \RZP\Exception\RuntimeException("Not implemented, core Implementation is on the way");
    }
}
