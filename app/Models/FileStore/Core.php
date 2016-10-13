<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;

class Core extends Base\Core
{
    /**
     * Creates a file handler entry and saves to db.
     * Returns file handler entity
     *
     * @param
     * @return array
     */
    public function create($input)
    {
        $fileStore = (new Entity)->build($input);

        $this->repo->saveOrFail($fileStore);

        return $fileStore;
    }
}
