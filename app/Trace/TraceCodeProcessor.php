<?php

namespace RZP\Trace;

use App;

/**
 * Adds trace code field to the record at the starting position
 * This ensures that trace code is near starting and catches first
 * attention since it gives direct info about the event being
 * logged
 *
 * Also logs the message associated with the trace code
 */
class TraceCodeProcessor
{
    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $code = $record['message'];

        $message = TraceCode::getMessage($code);

        if ($message === null)
            $message = $code;

        $record['message'] = $message;

        $record = ['code' => $code] + $record;

        $app = App::getFacadeRoot();

        $record['mode'] = $app['basicauth']->getMode();

        return $record;
    }
}
