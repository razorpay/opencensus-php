<?php

namespace Trace;

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

        $data = $app['instance']->getInstanceData();

        $record['instance'] = $data;

        return $record;
    }
}