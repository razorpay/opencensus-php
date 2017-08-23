<?php

namespace RZP\Trace;

use App;
use Request;
use RZP\Exception;

class ApiTraceProcessor
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function __invoke(array $record)
    {
        $this->addMode($record);

        return $record;
    }

    protected function addMode(&$record)
    {
        $record['mode'] = $this->app['basicauth']->getMode();
    }
}
