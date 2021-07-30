<?php

namespace RZP\Models\FundAccount\DetailsPropagator;

use App;
use RZP\Models\FundAccount\Entity as FundAccountEntity;

abstract class Base
{

    protected $app;

    protected $repo;

    public function __construct()
    {

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    abstract function update(FundAccountEntity $fundAccount, string $mode);
}
