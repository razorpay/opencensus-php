<?php

namespace Trace;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

class Trace extends Logger
{
    const OBJECT = "trace";

    protected $log_path = '/home/abhi/tmp/rzpapi/transaction.log';

    /**
     * Name of the application component
     * eg: transaction
     *
     * @var string $component Application component
     */
    protected $component;

    /**
     * Describes the operation for which trace is performed
     *
     * @var string $trace_code Trace code
     */
    protected $trace_code;

    public function __construct($component, $trace_code)
    {
        $this->name = static::OBJECT;
        $this->handlers = array();
        $this->processors = array();

        $this->component = $component;
        $this->trace_code = $trace_code;

        $formatter = new JsonFormatter();
        $stream = new StreamHandler($this->log_path);
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
     * Adds a log record.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addRecord($level, $message, array $context = array())
    {
        if (!$this->handlers) {
            $this->pushHandler(new StreamHandler('php://stderr', static::DEBUG));
        }

        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        $record = array(
            'object' => static::OBJECT,
            'component' => $this->component,
            'trace_code' => $this->trace_code,
            'message' => (string) $message,
            'context' => $context,
            'level' => $level,
            'level_name' => static::getLevelName($level),
            'timestamp' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), static::$timezone)->setTimezone(static::$timezone),
            'extra' => array(),
            //'channel' => $this->name,
        );
        // check if any handler will handle this message
        $handlerKey = null;
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling($record)) {
                $handlerKey = $key;
                break;
            }
        }
        // none found
        if (null === $handlerKey) {
            return false;
        }

        // found at least one, process message and dispatch it
        foreach ($this->processors as $processor) {
            $record = call_user_func($processor, $record);
        }
        while (isset($this->handlers[$handlerKey]) &&
            false === $this->handlers[$handlerKey]->handle($record)) {
            $handlerKey++;
        }

        return true;
    }
}