<?php

namespace RZP\Trace;

use RZP\Exception\CardNumberTraceException;
use RZP\Trace\TraceCode;
use RZP\Trace\TraceFields;

class Trace extends TraceWriter
{
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
        catch (\Exception $exception)
        {
            $environment = \App::make('config')->get('app.context');

            if (in_array($environment, ['production', 'beta']))
            {
                $app = \App::getFacadeRoot();

                $data = array(
                    'type'          => get_class($exception),
                    'message'       => $exception->getMessage(),
                    'code'          => $exception->getCode(),
                    'file'          => $exception->getFile(),
                    'line'          => $exception->getLine(),
                    'trace'         => $exception->getTraceAsString(),
                    'environment'   => $environment,
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

                // Since tracing is not a critical requirement here for execution
                // we are going to continue with our normal code run.
            }
        }
    }

    /**
     * Returns context array to be logged with trace record
     *
     * @param array $record
     */
    protected function getContext($code, $record)
    {
        $values = array();

        $fields = TraceFields::getFields($code);

        foreach($record as $key => $value)
        {
            $values[$key] = $record[$key];
        }

        $context = $values;

        TraceFields::checkFields($code, array_keys($context));

        return $context;
    }

    public function traceException(\Exception $exception, $level = null, $code = null)
    {
        $this->app['exception.handler']->traceException($exception, $level, $code);
    }
}
