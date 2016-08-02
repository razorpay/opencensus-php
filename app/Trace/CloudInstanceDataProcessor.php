<?php

namespace App\Trace;

use Requests;

/**
 * Adds cloud instance data to trace
 */
class CloudInstanceDataProcessor
{
    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $app = \App::getFacadeRoot();

        $data = $app['trace.instance']->getInstanceData();

        $record['trace.instance'] = $data;

        return $record;
    }
}