<?php

namespace Models\Settlement\Details;

use Models\Base;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;
    }
}