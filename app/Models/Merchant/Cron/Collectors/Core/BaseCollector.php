<?php


namespace RZP\Models\Merchant\Cron\Collectors\Core;

use App;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;

abstract class BaseCollector
{
    protected $lastCronTime;

    protected $args;

    protected $name;

    public function __construct(int $lastCronTime, array $args)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->args = $args;

        $this->lastCronTime = $lastCronTime;
    }

    abstract public function collect() : CollectorDto;

    public function getName()
    {
        return $this->name;
    }
}
