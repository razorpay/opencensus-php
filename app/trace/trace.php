<?php

namespace Trace;

use Queue;
use Config;
use Monolog\Logger;
use Trace\TraceHandler;

class Trace
{

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
     * Values for compulsory fields
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

    public function __call($name, $arguments)
    {
        $code = $arguments[0];
        $traceMessage = $arguments[1];

        $level;

        $message = $traceMessage['message'];

        unset($traceMessage['message']);

        $this->updateAllValues($code, $traceMessage);

        // determine level based on function called
        $level = strtoupper($name);
        
        // Queue logging the record
        $this->queueRecord(
            constant('\Monolog\Logger::'.$level), 
            $message);
    }

    /**
     * Updates compulsory as well as other values
     *
     * @param array $traceMessage
     */
    protected function updateAllValues($code, $traceMessage)
    {
        $this->compulsoryFieldValues = array();
        $this->values = array();

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

    public function queueRecord($level, $message, array $context = array())
    {
        $context = array_merge($this->compulsoryFieldValues, $this->values);

        Queue::push('Trace\TraceHandler', array(
            'level' => $level,
            'message' => $message,
            'context' => $context));
    }
}