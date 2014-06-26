<?php

namespace Trace;

use Queue;
use Config;
use Monolog\Logger;
use Trace\TraceHandler;
use Trace\TraceCode;
use Trace\TraceFields;

class Trace extends TraceWriter
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

    protected static $instance = null;

    public function __construct()
    {
        parent::__construct();

        //$this->traceWriter = new TraceWriter();
    }

    /**
     * Returns instance of class if present.
     * Otherwise creates one, stores it and then returns it.
     *
     * @return self the instance of class which extends
     *              this abstract class
     */
    public static function getInstance()
    {
        //$cls = get_called_class(); // late-static-bound class name

        if (!isset(self::$instance))
        {
            self::$instance = new static;
        }

        return self::$instance;
    }

    public function addRecord($level, $message, array $context = array())
    {
        $traceCode = $message;

        TraceCode::checkCode($traceCode);

        $message = TraceCode::getMessage($traceCode);

        $context = $this->getContext($traceCode, $context);

        parent::addRecord($level, $traceCode, $context);
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
}