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

    public function deleteAppTokens($appToken, $input)
    {
        App\Entity::verifyIdAndStripSign($appToken);

        $app = $this->repo->findOrFail($appToken);

        $data = (new App\Core)->deleteCustomerTokens($app->customer, $input);

        return $data;
    }
}
