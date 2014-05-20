<?php

namespace Trace;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

class Trace extends Logger
{
    // used as channel for Monolog\Logger
    const CHANNEL = "trace";

    // path of file used for logging
    const LOGPATH = '/home/abhi/tmp/rzpapi/transaction.log';

    /**
     * Name of the application component
     * eg: transaction
     *
     * @var string $component Application component
     */
    protected $component;

    /**
     * Fields required for each trace
     *
     * @var array $compulsoryFields Compulsory fields
     */
    protected static $compulsoryFields = array();

    /**
     * Values for compulsory fields required for each trace
     *
     * @var array $compulsoryFieldValues Values for compulsory fields
     */
    protected $compulsoryFieldValues = array();

    /**
     * Fields corresponding to a particular trace
     *
     * @var array $values Fields
     */
    protected static $fields = array();

    /**
     * Values corresponding to fields
     *
     * @var array $values Field values
     */
    protected $values = array();

    public function __construct()
    {
        parent::__construct(static::CHANNEL);

        $formatter = new JsonFormatter();
        $stream = new StreamHandler(static::LOGPATH);
        $stream->setFormatter($formatter);

        $this->pushHandler($stream);

        $this->pushProcessor(function($record)
            {
                $record['extra']['client_ip'] = \Request::getClientIp();
                $record['extra']['server_ip'] = \Request::server('SERVER_ADDR');

                return $record;
            });
    }

    /**
     * Updates compulsory as well as other values
     *
     * @param array $traceMessage
     */
    protected function updateAllValues($code, $traceMessage)
    {
        foreach($traceMessage as $key => $value)
        {
            if(in_array($key, static::$compulsoryFields))
            {
                $this->compulsoryFieldValues[$key] = $traceMessage[$key];
            }
            else if(in_array($key, static::$fields[$code]))
            {
                $this->values[$key] = $traceMessage[$key];
            }
        }
    }

    public function addRecord($level, $message, array $context = array())
    {
        $context = array_merge($this->compulsoryFieldValues, $this->values);

        parent::addRecord($level, $message, $context);
    }
}