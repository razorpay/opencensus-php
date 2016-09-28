<?php

namespace RZP\Models\FileHandler;

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
        $fileHandler = (new FileHandler\Entity)->build($input);

        $this->repo->saveOrFail($fileHandler);

        return $fileHandler;
    }
}
