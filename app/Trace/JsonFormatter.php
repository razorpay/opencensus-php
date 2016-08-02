<?php

namespace App\Trace;

use Monolog\Formatter;

/**
 * Encodes whatever record data is passed to it as json
 *
 * This can be useful to log to databases or remote APIs
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class JsonFormatter extends Formatter\JsonFormatter
{
    public function format(array $record)
    {
        return json_encode($record) . ($this->appendNewline ? "\n" : '');
    }

    /**
     * Return a JSON-encoded array of records.
     *
     * @param  array  $records
     * @return string
     */
    protected function formatBatchJson(array $records)
    {
        return json_encode($records);
    }
}
