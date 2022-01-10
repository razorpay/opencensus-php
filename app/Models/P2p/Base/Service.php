<?php

namespace RZP\Models\P2p\Base;

use App;

// Todo:: It will extend later
class Service //extends Base\Service
{
    /**
     * @var Processor
     */
    protected $processor;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->processor = $this->getNewProcessor();

        $this->trace = $app['trace'];
    }

    // TODO: Logic will change after entity naming convention
    protected function getNewProcessor()
    {
        $className = str_replace('\Service', '\Processor', static::class);

        return new $className;
    }
}
