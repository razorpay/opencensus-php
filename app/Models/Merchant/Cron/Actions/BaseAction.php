<?php

namespace RZP\Models\Merchant\Cron\Actions;

use App;
use RZP\Models\Merchant\Cron\Dto\ActionDto;

abstract class BaseAction
{
    protected $args;

    protected $app;

    protected $repo;

    public function __construct(array $args = [])
    {
        $this->args = $args;

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public abstract function execute($data = []) : ActionDto;
}
