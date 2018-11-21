<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\Base;

// Todo:: It will extend later
class Service //extends Base\Service
{
    /**
     * @var Processor
     */
    protected $processor;

    public function __construct()
    {
        $this->processor = $this->getNewProcessor();
    }

    // TODO: Logic will change after entity naming convention
    protected function getNewProcessor()
    {
        $className = str_replace('\Service', '\Processor', static::class);

        return new $className;
    }
}
