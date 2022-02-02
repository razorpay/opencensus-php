<?php


namespace RZP\Models\Merchant\Cron\Collectors\Core;

use App;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;

abstract class BaseCollector
{
    protected $args;

    protected $lastCronTime;

    protected $cronStartTime;

    public function __construct(?int $lastCronTime, ?int $cronStartTime, array $args)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->args = $args;

        $this->lastCronTime = $lastCronTime;

        $this->cronStartTime = $cronStartTime;
    }

    abstract public function collect() : CollectorDto;
}
