<?php

namespace RZP\Trace;

use RZP\Exception\CardNumberTraceException;

class Trace extends TraceWriter
{
    // used as channel for Monolog\Logger
    const CHANNEL = "Razorpay API";

    protected $app;

    protected $env;

    protected $config = array();

    protected $debug = false;

    protected $testHandler = null;

    public function __construct($app)
    {
        parent::__construct(static::CHANNEL);

        $this->app = $app;

        $this->env = $app->environment();

        $this->getConfig($this->app['config']);

        $this->defineHandlers();

        $this->defineProcessors();
    }

    public function addRecord($level, $message, array $context = array())
    {
        $traceCode = $message;

        TraceCode::checkCode($traceCode);

        $context = $this->getContext($traceCode, $context);

        try
        {
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

        if ($level = Trace::CRITICAL)
        {
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

    public function traceException(\Throwable $exception, $level = null, $code = null)
    {
        $this->app['exception.handler']->traceException($exception, $level, $code);
    }

    protected function sendMailAboutTracingFailure($exception, $level, $message, $context)
    {
        $env = $this->env;

        if (in_array($env, ['production']))
        {
            $data = array(
                'type'          => get_class($exception),
                'message'       => $exception->getMessage(),
                'code'          => $exception->getCode(),
                'file'          => $exception->getFile(),
                'line'          => $exception->getLine(),
                'trace'         => $exception->getTraceAsString(),
                'environment'   => $env,
                'mode'          => $this->mode,
                'level'         => $level,
                'trace_message' => $message,
                'instance'      => $app['instance']->getInstanceData(),
                'context'       => $context
            );

            $msg = json_encode($data, JSON_PRETTY_PRINT);

            $subject = self::CHANNEL . ' - ' . $environment . ' - Critical error occurred';

            // No point checking it's return value at this point because have
            // already experienced a critical failure upstream and this is
            // just a mechanism for out-of-band notification.
            // Just pray that it's working actually _/\_

            mail('developers@razorpay.com', $subject, $msg);
        }
    }

    protected function sendMailAboutFailureOnCriticalRoute($code, $traceData)
    {
        $mode = $this->mode;

        try
        {
            Mail::queue(
                'email.message',
                $msg = json_encode($traceData, JSON_PRETTY_PRINT),
                function ($message)
                {
                    $subject = self::CHANNEL . ' - ' . $mode . ' - Critical error occurred';
                    $message->subject($subject);

                    $message->from('errors@razorpay.com');
                    $message->subject('Razorpay | Critical error occurred');
                    $message->replyTo('developers@razorpay.com');
                }
            );
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
        return $this->app['rzp.mode'];
    }
}
