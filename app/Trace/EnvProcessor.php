<?php

namespace RZP\Trace;

use App;

/**
 * Adds cloud instance data to trace
 */
class EnvProcessor
{
    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        // $env = App::environment();

        $context = App::make('config')->get('app.context');

        $record['environment'] = $context;

        return $record;
    }
}