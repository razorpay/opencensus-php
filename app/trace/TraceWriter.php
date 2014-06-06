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

        if ($this->debugOption('screen'))
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
        $callback = array($this, 'timestampProcessor');

        $this->pushProcessor($callback);

        if ($this->config['introspection'])
        {
            $skipClassesPartials = array('Trace\\', 'Monolog\\');

            $processor = new Processor\IntrospectionProcessor(Logger::DEBUG, $skipClassesPartials);

            $this->pushProcessor($processor);
        }

        $this->pushWebProcessor();
    }

    protected function pushTestHandler()
    {
        $htmlFormatter = new Formatter\HtmlFormatter();

        $testHandler = new Handler\TestHandler();
        
        $testHandler->setFormatter($htmlFormatter);

        $this->pushHandler($testHandler);
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

        $processor = new Processor\WebProcessor();

        $this->pushProcessor($processor);
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
}