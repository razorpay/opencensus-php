<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\P2p\Base\Traits\ApplicationTrait;

class Core
{
    use ApplicationTrait;

    public function __construct()
    {
        $this->bootApplicationTrait();

        $this->repo = $this->getNewRepository();
    }

    protected function getNewRepository()
    {
        $className = str_replace('\Core', '\Repository', static::class);

        return new $className;
    }
}
