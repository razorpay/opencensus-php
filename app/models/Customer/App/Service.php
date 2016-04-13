<?php 

namespace Models\Customer\App;

use Models\Base;
use Models\Customer\App;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new App\Repository;
    }
}
