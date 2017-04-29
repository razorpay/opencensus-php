<?php

namespace RZP\Trace;

use Mail;

use RZP\Exception\CardNumberTraceException;
use RZP\Mail\System\Trace as TraceMail;
use RZP\Exception;
use Monolog\Logger;
use Monolog\Processor;
use Monolog\Handler;
use Monolog\Formatter;

class Trace extends Logger
{
    /**
     * used as channel for Monolog\Logger
     */
    const CHANNEL = "Razorpay API";

    protected $app;

    protected $env;

    protected $mode;

    protected $config = array();

    protected $debug = false;

    /**
     * This will record whether we have fired a critical trace or not.
     * This helps in preventing recursion when multiple critical failures
     * pile up on top of each other.
     */
    protected $critical = false;

    protected $testHandler = null;

    public function __construct($app)
    {
        parent::__construct(static::CHANNEL);

        $this->app = $app;

        $this->env = $app->environment();

        $this->getConfig($this->app['config']);
    }

    public function init()
    {
        $this->defineHandlers();

        $this->defineProcessors();
    }

    public function addRecord($level, $message, array $context = [])
    {
        try
        {
            $traceCode = $message;

            TraceCode::checkCode($traceCode);

            $context = $this->getContext($traceCode, $context);

            return parent::addRecord($level, $traceCode, $context);
        }
        catch (CardNumberTraceException $exception)
        {
            ;
        }
        catch (\Throwable $exception)
        {
            $this->sendMailAboutTracingFailure($exception, $level, $message, $context);

            // Since tracing is not a critical requirement here for execution
            // we are going to continue with our normal code run.
        }

        if (($level = Trace::CRITICAL) and
            ($this->critical === false))
        {
            $this->critical = true;
            $this->sendMailAboutFailureOnCriticalRoute($traceCode, $context);
        }
    }

    /**
     * Returns context array to be logged with trace record
     *
     * @param string $code
     * @param array  $record
     * @return array
     */
    protected function getContext($code, $record)
    {
        $values = array();

        foreach($record as $key => $value)
        {
            $values[$key] = $record[$key];
        }

        $context = $values;

        TraceFields::checkFields($code, array_keys($context));

        return $context;
    }

    public function traceException(
        \Throwable $exception,
        $level = null,
        $code = null,
        array $extraData = [])
    {
        $this->app['exception.handler']
             ->traceException($exception, $level, $code, $extraData);
    }

    protected function sendMailAboutTracingFailure($exception, $level, $message, $context)
    {
        if ($this->isEnvironmentProd())
        {
            $data = array(
                'type'          => get_class($exception),
                'message'       => $exception->getMessage(),
                'code'          => $exception->getCode(),
                'file'          => $exception->getFile(),
                'line'          => $exception->getLine(),
                'trace'         => $exception->getTraceAsString(),
                'environment'   => $this->env,
                'mode'          => $this->getMode(),
                'level'         => $level,
                'trace_message' => $message,
                'instance'      => $this->app['instance']->getInstanceData(),
                'context'       => $context
            );

            $subject = self::CHANNEL . ' - ' . $this->env . ' - Critical error occurred';

            // No point checking it's return value at this point because have
            // already experienced a critical failure upstream and this is
            // just a mechanism for out-of-band notification.
            // Just pray that it's working actually _/\_

            $this->sendMailWithData($subject, $data);
        }
    }

    protected function isEnvironmentProd()
    {
        $env = $this->env;

        return (in_array($env, ['production']));
    }

    protected function sendMailWithData($subject, $data)
    {
        $msg = json_encode($data, JSON_PRETTY_PRINT);

        mail('developers@razorpay.com', $subject, $msg);
    }

    protected function sendMailAboutFailureOnCriticalRoute($code, $traceData)
    {
        $mode = $this->getMode();

        try
        {
            $msg = json_encode($traceData, JSON_PRETTY_PRINT);

            $traceMail = new TraceMail($msg, $mode);

            Mail::send($traceMail);
        }
        catch (\Throwable $exception)
        {
            $this->traceException($exception);

            // Since mailing is not a critical requirement here for execution
            // we are going to continue with our normal code run.
        }
    }

    protected function getMode()
    {
        if ($this->mode === null)
        {
            if (isset($this->app['rzp.mode']))
            {
                $this->mode = $this->app['rzp.mode'];
            }
        }

        return $this->mode;
    }

    protected function getConfig($config)
    {
        $this->config = $config->get('trace');

        $this->debug = $config->get('app.debug');

        $this->contextEnv = $config->get('app.context');
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
            $browserHandle = new Handler\BrowserConsoleHandler;

            $this->pushHandler($browserHandle);
        }

        if ($this->debugOption('chrome'))
        {
            $chromePHPHandle = new Handler\ChromePHPHandler;

            $this->pushHandler($chromePHPHandle);
        }
    }

    protected function defineProcessors()
    {
        $this->pushProcessor(new SplunkTimestampProcessor);

        $this->pushProcessor(new TraceCodeProcessor);

        if (($this->debug) or
            ($this->config['introspection']) or
            ($this->contextEnv === 'beta'))
        {
            $this->pushIntrospectionProcessor();
        }

        $this->pushProcessor(new WebProcessor);

        $this->pushProcessor(new CloudInstanceDataProcessor);

        $this->pushProcessor(new EnvProcessor);
    }

    protected function pushIntrospectionProcessor()
    {
        $skipClassesPartials = array('Trace\\', 'Monolog\\');

        $processor = new Processor\IntrospectionProcessor(static::DEBUG, $skipClassesPartials);

        $this->pushProcessor($processor);
    }

    protected function pushTestHandler()
    {
        $scalarFormatter = new Formatter\ScalarFormatter;

        $testHandler = new Handler\TestHandler;

        $testHandler->setFormatter($scalarFormatter);

        $this->pushHandler($testHandler);

        $this->testHandler = $testHandler;
    }

    protected function pushStreamHandler()
    {
        $stream = new Handler\StreamHandler($this->config['logpath']);

        $jsonFormatter = new JsonFormatter;

        $stream->setFormatter($jsonFormatter);

//        $minLevel = $this->debug ? static::DEBUG : static::INFO;
        $minLevel = static::DEBUG;

        $filter = new Handler\FilterHandler($stream, $minLevel);

        $this->pushHandler($filter);
    }

    public function fire($job, $trace)
    {
        parent::addRecord($trace['level'], $trace['message'], $trace['context']);

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
            {
                throw new Exception\InvalidArgumentException(
                    $option . ' in debug not defined');
            }
        }

        return false;
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
