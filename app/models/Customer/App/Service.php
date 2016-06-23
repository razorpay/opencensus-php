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

    public function deleteAppTokensForGlobalCustomer($appToken, $input)
    {
        App\Entity::verifyIdAndStripSign($appToken);

        $app = $this->repo->findByIdAndMerchantId($appToken, $this->merchant->getId());

        $data = (new App\Core)->deleteAppTokensForGlobalCustomer($app->customer, $input);

        return $data;
    }
}
