<?php

namespace Models\Manager;

class EntityManager
{

	/**
	 * The input provided
	 *
	 * @var array
	 */
	protected $input = array();

	/**
	 * Data formed after verifying and validating input
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Fields which will be generated
	 * during build
	 * @var array
	 */
	protected static $generators = array();

	/**
	 * Validator functions that will be 
	 * rung during build on input data
	 */
	protected static $validators = array();

	/**
	 * Input keys which will be unset
	 * before calling 'fill'
	 */
	protected static $unsetCreateInput = array();

	/**
	 * Rules to validate input values for building
	 * the domain object
	 */
	protected static $createRules = array();

	/**
	 * Denotes whether the current object
	 * has been built from input (true) or
	 * loaded from storage (false)
	 * 
	 * @var boolean
	 */
	protected $built = false;

	public function __construct()
	{
		;
	}

	/**
	 * The "booting" method of the model.
	 *
	 * @return void
	 */
	protected static function boot()
	{
		;
	}

    public function build(array $input)
    {
    	$this->input = $input;

    	$this->validateInput($input, 'create');

    	$this->generate($input);

    	$this->unsetInput($input, 'create');

    	$this->setBuilt(true);

    	$this->fill($input);
    }

    public static function createValidate(array $input)
    {
    	$manager = new static();

    	$manager->build($input);

    	return $manager;
    }

    /**
     * Verifies validity of input values and keys.
     * @param  array    $input     Input array supplied
     * @param  string   $operation Operation for which input 
     *                             is supplied
     * 
     * @return void     throws exception for error
     */
    protected function validateInput($input, $operation)
    {
    	$this->validateInputKeys($input, $operation);

    	$this->validateInputValues($input, $operation);
    }

    /**
     * Checks that all keys present in the input are allowed.
     * 
     * @param  array    $input     Input array supplied
     * @param  string   $operation Operation for which input 
     *                             is supplied
     *                             
     * @return void     throws exception for error
     */
    protected function validateInputKeys($input, $operation)
    {
    	$rules_var = $operation.'Rules';
    	
    	$invalid_keys = array_keys(array_diff_key($input, static::$$rules_var));

        if (count($invalid_keys) > 0)
        {
            throw new \InvalidKeysException($invalid_keys);
        }
    }

    /**
     * Checks validity and presence of input values.
     * 
     * @param  array    $input     Input array supplied
     * @param  string   $operation Operation for which input 
     *                             is supplied
     *                             
     * @return void     throws exception for error
     */
    protected function validateInputValues($input, $operation)
    {	
    	$rules_var = $operation.'Rules';
    	$validation = \Validator::make(
                        $input,
                        static::$$rules_var);

        if ($validation->fails())
        {
        	var_dump($validation->messages()->all()); die();
        	throw new \InvalidArgumentException('message');
        }

        $this->runValidators($input, $operation);        
    }

    protected function runValidators($input, $operation)
    {
    	$operation_function_var = $operation.'Validators';

    	if (isset(static::$$operation_function_var))
    	{
	    	foreach (static::$$operation_function_var as $validator)
	    	{
	    		$validate_func = 'validate'.ucfirst($validator);

	    		$this->$validate_func($input);
	    	}
	    }
    }

    public function unsetInput(& $input, $operation)
    {
    	foreach (static::${'unset'.ucfirst($operation).'Input'} as $key)
    	{
    		unset($input[$key]);
    	}
    }

    public function generate($input)
    {
    	foreach (static::$generators as $field)
    	{
    		$this->generateField($field, $input);
    	}
    }

    public function generateField($field, $input)
    {
		$this->{'generate'.studly_case($field)}($input);
    }

    public function fill(array $values)
    {
    	$this->data = array_merge($values, $this->data);
    }

 	/**
 	 * Returns value of the field
 	 * 
 	 * @param  string  $key
 	 * @return mixed
 	 */
    public function getField($key)
    {
    	if ((isset($this->field)) and 
    		(! in_array($key, $this->field)))
    	{
    		throw new \InvalidKeysException;
    	}

    	if (array_key_exists($key, $this->data))
		{
			return $this->data[$key];
		}
    }

    public function setField($key, $value)
    {
    	if ((count($this->field) > 0) and 
    		(! in_array($key, $this->field)))
    	{
    		throw new \InvalidKeysException;
    	}

    	$this->data[$key] = $value;
    }

	/**
	 * Dynamically set fields on the object.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setField($key, $value);
	}

	/**
	 * Dynamically retrieve fields on the object.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getField($key);
	}

	public function setBuilt($value)
	{
		if (! is_bool($value))
		{
			throw new \InvalidArgumentException('Argument should be boolean');
		}

		$this->built = $value;
	}

	public function isBuilt()
	{
		return $this->built;
	}


	public static function getCreateInputKeys()
	{
		return array_keys(static::$createRules);
	}

	public function getData()
	{
		return $this->data;
	}
}