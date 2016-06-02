<?php

namespace Trace;

use Trace\TraceCode;
use Trace\TraceFields;

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
        catch (\Exception $e)
        {
            $data = array(
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            );

            $msg = '';

            foreach ($data as $key => $value)
            {
                $msg .= "$key => $value" . PHP_EOL;
            }

            $subject = self::CHANNEL . ' - Critical error occurred';

            // No point checking it's return value at this point because have
            // already experienced a critical failure upstream and this is
            // just a mechanism for out-of-band notification.
            // Just pray that it's working actually _/\_

            mail('developers@razorpay.com', $subject, $msg);
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

    public function traceException(\Exception $exception)
    {
        $this->app['exception.handler']->traceException($exception);
    }
}
