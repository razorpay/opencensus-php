<?php

namespace Models\DO;

class DomainObject
{

	/**
	 * The input using which DO is created
	 *
	 * @var array
	 */
	protected $input = array();

	/**
	 * Object's property data
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Links to domain objects
	 *
	 */
	protected $objects = array();

	/**
	 * Fields of the object which can be filled.
	 *
	 * @var  array
	 */
	protected static $fields = array();

	/**
	 * 'one' defines a single Domain Object
	 * 'many' means multiple Domain Objects
	 * These are actually stored in $objects
	 * array
	 *
	 * @var array
	 */
	protected static $do = array()
		'one'	=>	array(),
		'many'	=>	array());

	/**
	 * Visible fields for arrays
	 *
	 * @var array
	 */
	protected static $visible = array();

	/**
	 * Fields hidden for arrays
	 *
	 * @var array
	 */
	protected static $hidden = array();

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
	 * before calling fill during build
	 */
	protected static $unsetInput = array();

	/**
	 * The accessors to append to the object's array form.
	 *
	 * @var array
	 */
	protected static $appends = array();

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

	/**
	 * Whether or not to throw error when
	 * calling setting a field not defined
	 * in $fields array. By default such keys
	 * are ignored. If this bool is true, then
	 * we throw an exception.
	 * 
	 * @var boolean
	 */
	protected static bool $errorOnUnknownKeys = true;

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
    	$this->validateInput($input);

    	$this->generate($input);

    	$this->unsetInput($input);

    	$this->setBuilt(true);

    	$this->fill($input);
    }

    public static create(array $input)
    {
    	$do = new static();

    	$do->build($input);

    	return $do;
    }

    protected function validateInput($input)
    {
    	$this->validateInputKeys($input);

    	$this->validateInputValues($input);
    }

    protected function validateInputValues($input)
    {
    	$validation = Validator::make(
                        $input, 
                        self::$createRules);

        if ($validation->fails()) 
        {
        	throw new \InvalidArgumentException($validation->errors->all());
        }

        $this->runValidators($input);        
    }

    protected function runValidators($input)
    {
    	foreach (self::$validators as $validator)
    	{
    		$this->{'validate'.$validator}($input);
    	}
    }

    public function unsetInput(& $input)
    {
    	foreach (self::$unsetInput as $key)
    	{
    		unset($input[$key]);
    	}
    }

    public function generate($input)
    {
    	foreach (self::$generators as $field)
    	{
    		$this->generateField($field, $input);
    	}
    }

    public function generateField($field, $input)
    {
		return $this->{'generate'.studly_case($field).'Field'}($input);
    }

    protected function validateInputKeys($input)
    
        $invalid_keys = array_diff_key($input, self::$createRules);

        if (count($invalid_keys) !== 0)
        {
            throw new InvalidKeysException($invalid_keys);
        }
    }

    public function validateSetKeys(array $keys)
    {
    	$invalid_keys = array_diff($keys, self::$fields);

    	if (count($invalid_keys) !== 0)
    	{
    		throw new \InvalidKeyException($invalid_keys);
    	}
    }

    public function fill(array $values)
    {
        $this->validateSetKeys(array_keys($values);

        foreach ($values as $key => $value)
        {
        	$this->setField($key, $value);
        }
    }

    public function setField($key, $value)
    {
    	if (! array_key_exists($key, $this->field))
    	{
    		//
    		// First we will check for the presence of a mutator for the set operation
			// which simply lets the developers tweak the field as it is set on
			// the model.
			//
			 
			if ($this->hasSetMutator($key))
			{
				$method = 'set'.studly_case($key).'Field';

				return $this->{$method}($value);
			}

			//
			// No field defined and no mutator defined.
			// We throw exception if bool variable is set to true.
			// 
			
			if ($this->errorOnUnknownKeys)
			{
		        throw new \InvalidKeyException($key . " is not a valid key.");
		    }
    	}
    	else
    	{
			$this->data[$key] = $value;
		}
    }

 	/**
 	 * Returns value of the field
 	 * 
 	 * @param  string  $key
 	 * @return mixed
 	 */
    public function getField($key)
    {
    	if (! in_array($key, $this->field))
    	{
    		throw new \InvalidKeyException;
    	}

    	if (array_key_exists($key, $this->data))
		{
			return $this->field[$key];
		}
    }

    /**
     * Sets the object in $objects
     * 
     * @param [type] $key [description]
     * @param [type] $obj [description]
     */
    public function setObject($key, $obj)
    {
    	if (array_key_exists($key, self::$do['one']))
    	{
    		$this->objects[$key] = $obj;
    	}
    	else if (array_key_exists($key, self::$do['many']))
    	{
    		if (isset($this->objects[$key]))
    		{
    			array_push($this->objects[$key], $obj);
    		}
    		else
    		{
    			$this->objects[$key] = array($obj);
    		}
    	}
    	else
    	{
    		throw new \InvalidKeyException($key . ' is not defined for this domain object');
    	}
    }

    public function getObject($key)
    {
    	if ((! array_key_exists($key, self::$do['one'])) and
    		(! array_key_exists($key, self::$do['many'])))
    	{
    		if ($this->errorOnUnknownKeys))
			{
	    		throw new \InvalidKeyException($key . ' is not defined for this domain object')
	    	}
	    }
    	else if (isset($this->objects[$key]))
    	{
    		return $this->objects[$key];
    	}
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

	/**
	 * Determine if a set mutator exists for a field
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function hasSetMutator($key)
	{
		return method_exists($this, 'set'.studly_case($key).'Field');
	}

	/**
	 * DEFAULT: Visible or Non-hidden fields with appends
	 * FIELDS: Visible or non-hidden fields
	 * ALL_FIELDS: All fields
	 * APPENDS: Appends
	 *
	 * Child class flags should start from 0x256
	 * 
	 */

	const DEFAULT = 0x0;
	const FIELDS = 0x1;
	const ALL_FIELDS = 0x2;
	const APPENDS = 0x3;

	/**
	 * Return object properties as array
	 *
	 * @return array
	 */
	public function toArray($flag = 0x0)
	{
		$array = array();

		if (($flag & self::FIELDS) or
			($flag & self::DEFAULT))
		{
			$array = $this->getArrayableFields();
		}
		else if ($flag & self::ALL_FIELDS)
		{
			$array = $this->data;
		}

		if (($flag & self::APPENDS) or
			($flag & self::DEFAULT))
		{
			$array = array_merge($array, $this->getAppends());
		}

		return $array
	}

	/**
	 * 
	 */
	public function getAppends()
	{
		$appends = array();

		//
		// Here we will grab all of the appended, calculated fields to this object
		// as these fields are not really in the fields array, but are run
		// when we need to array or JSON the object for convenience to the coder.
		// 

		foreach (self::$appends as $key)
		{
			$appends[$key] = $this->getField($key);
		}

		return $appends;
	}

	public function getArrayableFields()
	{
		if (count($this->visible) > 0)
		{
			return array_intersect_key($values, array_flip($this->visible));
		}

		return array_diff_key($values, array_flip($this->hidden));
	}

	public static function getVisible()
	{
		return self::$visible;
	}

	public static function getHidden()
	{
		return self::$hidden;
	}

	public static function getCreateInputKeys()
	{
		return array_keys(self::$createRules);
	}
}