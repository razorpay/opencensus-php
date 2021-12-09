<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Entity;
use RZP\Exception\RuntimeException;
use RZP\Models\Base\PublicCollection;

/**
 * * @property Core $core
 */
class Core extends Base\Core
{
    public function create(): Entity
    {
        throw new RuntimeException('Not implemented, Core Implementation is on the way');
    }

    public function update(Entity $mandate, array $input): Entity
    {
        throw new RuntimeException('Not implemented, Core Implementation is on the way');
    }

    public function fetch(string $input , $withTrashed = false): Entity
    {
        throw new RuntimeException('Not implemented, Core Implementation is on the way');
    }

    public function fetchAll(array $input): PublicCollection
    {
        throw new RuntimeException('Not implemented, Core Implementation is on the way');
    }
}
