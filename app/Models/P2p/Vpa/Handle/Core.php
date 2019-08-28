<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Exception;
use RZP\Models\P2p\Base;
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
        $handle->edit($input);

        $this->repo->saveOrFail($handle);

        return $handle;
    }
}
