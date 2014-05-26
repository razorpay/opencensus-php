<?php

namespace Trace;

use Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FilterHandler;
use Monolog\Formatter\JsonFormatter;

class TraceWriter extends Logger
{
    // used as channel for Monolog\Logger
    const CHANNEL = "trace";

    public function __construct()
    {
        parent::__construct(static::CHANNEL);

        $formatter = new JsonFormatter();

        $stream = new StreamHandler(Config::get('trace.logpath'));
        $stream->setFormatter($formatter);

        $minLevel = Config::get('app.debug') ? Logger::DEBUG : Logger::INFO;
        $filter = new FilterHandler($stream, $minLevel);

        $this->pushHandler($filter);

        $this->pushProcessor(function($record)
            {
                unset($record['datetime']);

                $timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
                $microtime = microtime(true);
                $milliseconds = sprintf("%03d", round(($microtime - floor($microtime)) * 1000));
                $date = \DateTime::createFromFormat('U.u', sprintf('%.6F', $microtime), $timezone);
                $date->setTimezone($timezone);

                $timestamp = $date->format('Y-m-d\TH:i:s') . '.' . $milliseconds;

                // reordering record to bring timestamp to first position
                $tmp = array();
                $tmp['timestamp'] = $timestamp;
                foreach($record as $key => $value)
                {
                    $tmp[$key] = $value;
                }

                return $tmp;
            });
    }
}