<?php

namespace Trace;

use Queue;
use Config;
use Monolog\Logger;
use Trace\TraceHandler;
use Trace\TraceEvent;
use Trace\TraceFields;

class Trace extends \Singleton
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
    protected static $commonFields = array();

    /**
     * Values for compulsory fields
     *
     * @var array $compulsoryFieldValues Values for compulsory fields
     */
    protected $commonValues = array();

    /**
     * Values corresponding to fields
     *
     * @var array $values Field values
     */
    protected $values = array();

    protected $traceWriter = null;

    protected function __construct()
    {
        parent::__construct();

        $this->traceWriter = new TraceWriter();
    }

    public function __call($name, $args)
    {
        list($code, $values) = $this->validate($args);

        $message = TraceEvent::getMessage($code);

        $context = $this->getContext($code, $values);

        $this->traceWriter->{$name}($message, $context);
    }

    protected function validate($args)
    {
        $code = $args[0];

        TraceEvent::checkCode($code);

        $context = array();

        if (isset($args[1]))
            $context = $args[1];

        if (! is_array($context))
        {
            throw new \InvalidArgumentException('Context supplied should be array');
        }

        return array($code, $context);
    }

    /**
     * Set default values of fields
     * for which developer did not provide a value
     *
     * @param string $code
     * @param array $record
     * @return array $record
     */
    public function setCommonValues($code, $record)
    {
        foreach(static::$defaults as $index => $default)
        {
            if(!array_key_exists($default, $record))
            {
                $defaults_var = 'default'.ucfirst($default);

                $record[$default] = static::${$defaults_var}[$code];
            }
        }

        return $record;
    }

    /**
     * Returns context array to be logged with trace record
     *
     * @param array $record
     */
    protected function getContext($code, $record)
    {
        $this->commonValues = array();

        $this->values = array();

        $fields = TraceFields::getFields($code);

        foreach($record as $key => $value)
        {
            if(in_array($key, static::$commonFields))
            {
                $this->commonValues[$key] = $record[$key];
            }
            else if(in_array($key, $fields))
            {
                $this->values[$key] = $record[$key];
            }
        }

        $context = array_merge($this->commonValues, $this->values);

        TraceFields::checkFields($code, array_keys($context));

        return $context;

    }

    /**
     * In debug mode, this function returns all
     * the log records logged till now
     * 
     * @return array Log records with context and extras
     */
    public function getRecords()
    {
        return $this->traceWriter->getRecords();
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