<?php

namespace Trace;

use Config;
use App;
use Request;
use Monolog\Logger;
use Monolog\Processor;
use Monolog\Handler;
use Monolog\Formatter;

class TraceWriter extends Logger
{
    // used as channel for Monolog\Logger
    const CHANNEL = "Razorpay API";

    protected $config = array();

    protected $debug = false;

    protected $testHandler = null;

    public function __construct()
    {
        parent::__construct(static::CHANNEL);

        $this->config = Config::get('trace');

        $this->debug = Config::get('app.debug');

        $this->defineHandlers();

        $this->defineProcessors();
    }

    protected function defineHandlers()
    {
        $this->pushStreamHandler();

        if ($this->debug)
        {
            $this->pushTestHandler();
        }

        if ($this->debugOption('browser'))
        {
            $browserHandle = new Handler\BrowserConsoleHandler();

            $this->pushHandler($browserHandle);
        }

        if ($this->debugOption('chrome'))
        {
            $chromePHPHandle = new Handler\ChromePHPHandler();

            $this->pushHandler($chromePHPHandle);
        }
    }

    protected function defineProcessors()
    {
        $this->pushProcessor(new SplunkTimestampProcessor);

        $this->pushProcessor(new TraceCodeProcessor);

        if ($this->config['introspection'])
        {
            $this->pushIntrospectionProcessor();
        }

        $this->pushProcessor(new WebProcessor);
    }

    protected function pushIntrospectionProcessor()
    {
        $skipClassesPartials = array('Trace\\', 'Monolog\\');

        $processor = new Processor\IntrospectionProcessor(Logger::DEBUG, $skipClassesPartials);

        $this->pushProcessor($processor);
    }

    protected function pushTestHandler()
    {
        $scalarFormatter = new Formatter\ScalarFormatter();

        $testHandler = new Handler\TestHandler();

        $testHandler->setFormatter($scalarFormatter);

        $this->pushHandler($testHandler);

        $this->testHandler = $testHandler;
    }

    protected function pushStreamHandler()
    {
        $stream = new Handler\StreamHandler(
            $this->config['logpath']);

        $jsonFormatter = new Formatter\JsonFormatter();

        $stream->setFormatter($jsonFormatter);

        $minLevel = $this->debug ? Logger::DEBUG : Logger::INFO;

        $filter = new Handler\FilterHandler($stream, $minLevel);

        $this->pushHandler($filter);
    }

    public function fire($job, $trace)
    {
        $recorder = new self();

        $writer->addRecord($trace['level'], $trace['message'], $trace['context']);

        $job->delete();
    }

    protected function debugOption($option)
    {
        if ($this->debug)
        {
            if (isset($this->config['debug_options'][$option]))
            {
                return $this->config['debug_options'][$option];
            }
            else
                throw new \InvalidArgumentException($option . ' in debug not defined');
        }
        else
            return false;
    }

    public function addRecord($level, $message, array $context = array())
    {
        if ($this->config['queue'])
        {
            // Queue the logging the record
            $this->queueRecord(
                $level,
                $message,
                $context);
        }
        else
        {
            parent::addRecord($level, $message, $context);
        }
    }

    public function queueRecord($level, $message, array $context = array())
    {
        Queue::push(__NAMESPACE__.'\TraceWriter', array(
            'level' => $level,
            'message' => $message,
            'context' => $context));
    }

    /**
     * In debug mode, this function returns all
     * the log records logged till now
     *
     * @return array Log records with context and extras
     */
    public function getRecords()
    {
        if ($this->testHandler !== null)
        {
            return $this->testHandler->getRecords();
        }
    }

    /**
     * In debug mode, this function returns all the
     * log records logged till now.
     * The array returned is only one level deep
     * with sub-arrays keys combined with their parent
     * ones
     *
     * @return array One level deep log records
     */
    public function getFlattenedRecordsForScreen()
    {
        $records = $this->getRecords();

        $rec = array();

        $i = 0;

        foreach ($records as $record)
        {
            unset(
                $record['formatted'],
                $record['level']);

            $record = array_assoc_flatten($record, $i);

            //
            // Add a null for better output
            //
            array_push($record, null);

            $rec = array_merge($rec, $record);

            $i++;
        }

        return $rec;
    }
}