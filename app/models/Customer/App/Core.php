<?php

namespace Models\Customer\App;

use Models\Base;
use Models\Customer\App;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new App\Repository;
    }

    public function create($input)
    {
        $app = (new App\Entity)->build($input);

        $this->repo->saveOrFail($app);

        return $app;
    }
}