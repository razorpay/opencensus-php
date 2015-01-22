<?php

namespace Trace;

use Trace\TraceCode;
use Trace\TraceFields;

class Trace extends TraceWriter
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addRecord($level, $message, array $context = array())
    {
        $traceCode = $message;

        TraceCode::checkCode($traceCode);

        $context = $this->getContext($traceCode, $context);

        parent::addRecord($level, $traceCode, $context);
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
            if (in_array($key, $fields))
            {
                $values[$key] = $record[$key];
            }
        }

        $context = $values;

        TraceFields::checkFields($code, array_keys($context));

        return $context;
    }
}