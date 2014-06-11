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
            $chromePHPFormatter = new Formatter\ChromePHPFormatter();

            $chromePHPHandle = new Handler\ChromePHPHandler();
            
            $chromePHPHandle->setFormatter($chromePHPFormatter);
            
            $this->pushHandler($chromePHPHandle);
        }
    }

    protected function defineProcessors()
    {
        $callback = array($this, 'timestampProcessor');

        $this->pushProcessor($callback);

        if ($this->config['introspection'])
        {
            $skipClassesPartials = array('Trace\\', 'Monolog\\');

            $processor = new Processor\IntrospectionProcessor(Logger::DEBUG, $skipClassesPartials);

            $this->pushProcessor($processor);
        }

        if (\App::environment('dev') === false)
            $this->pushWebProcessor();
    }

    protected function pushTestHandler()
    {
        $lineFormatter = new Formatter\LineFormatter(null, null, true);

        $testHandler = new Handler\TestHandler();

        $jsonFormatter = new Formatter\JsonFormatter();
        $scalarFormatter = new Formatter\ScalarFormatter();

        $testHandler->setFormatter($lineFormatter);

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

    protected function pushWebProcessor()
    {
        $server = array(
            'request_uri' => Request::path(),
            'request_url' => Request::fullUrl(),
            'request_method' => Request::method(),
            'request_header' => Request::header(),
            'request_ajax' => Request::ajax(),
            'request_client_ip' => Request::getClientIp(),
            'request_server_ip' => Request::server('SERVER_ADDR'));

        $this->unsetUrlForSensitiveUrls($server);

        $processor = new Processor\WebProcessor();

        $this->pushProcessor($processor);
    }

    protected function unsetUrlForSensitiveUrls(& $server)
    {
        $sensitiveUrls = \Constants\URL::getDoNotLogURLs();

        if (in_array($server['request_url'], $sensitiveUrls))
        {
            unset(
                $server['request_uri'],
                $server['request_url']);
        }
    }

    /**
     * Adds timestamp in the format specified and needed
     * by splunk server
     * 
     * @param  [type] $record [description]
     * @return [type]         [description]
     */
    public function timestampProcessor($record)
    {
        //unset($record['datetime']);

        $timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        
        $microtime = microtime(true);
        
        $milliseconds = sprintf("%03d", round(($microtime - floor($microtime)) * 1000));
        
        $date = \DateTime::createFromFormat('U.u', sprintf('%.6F', $microtime), $timezone);
        
        $date->setTimezone($timezone);

        $timestamp = $date->format('Y-m-d\TH:i:s') . '.' . $milliseconds;

        //
        // reordering records to bring timestamp to first position
        // 
        
        $record = ['timestamp' => $timestamp] + $record;

        unset($record['datetime']);

        return $record;
    }

    public function fire($job, $trace)
    {
        $recorder = new self();

        $writer->addRecord($trace['level'], $trace['message'], $trace['context']);

        $job->delete();
    }

    protected function debugOption($option = null)
    {
        if ($option === null)
        {
            return $this->debug;
        }
        else if ($this->debug)
        {
            if (isset($this->config['debug_options'][$option]))
            {
                return $this->config['debug_options'][$option];
            }
            else
                throw new \InvalidArgumentException($option . ' in debug not defined');
        }
        else return false;
    }

    public function getRecords()
    {
        if ($this->testHandler !== null)
        {
            return $this->testHandler->getRecords();
        }
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
}