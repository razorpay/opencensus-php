<?php

namespace RZP\Trace;

use DateTime;
use DateTimeZone;

/**
 * Adds timestamp in the format specified and needed
 * by splunk server
 */
class SplunkTimestampProcessor
{
    protected $timezone;

    const TIME_ZONE = 'UTC';

    const DATE_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct()
    {
        $this->timezone = new DateTimeZone(self::TIME_ZONE);
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $microtime = microtime(true);

        $milliseconds = sprintf("%03d", round(($microtime - floor($microtime)) * 1000));

        $date = DateTime::createFromFormat('U.u', sprintf('%.6F', $microtime), $this->timezone);

        $date->setTimezone($this->timezone);

        $timestamp = $date->format(self::DATE_FORMAT) . '.' . $milliseconds;

        //
        // reordering records to bring timestamp to first position
        //

        $record = ['timestamp' => $timestamp] + $record;

        unset($record['datetime']);

        return $record;
    }
}
