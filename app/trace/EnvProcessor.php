<?php

namespace Trace;

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
        $record['environment'] = App::environment();

        return $record;
    }
}