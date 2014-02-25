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
	 * Fields of the object which can be filled.
	 *
	 * @var  array
	 */
	protected $fields = array();
	
	/**
	 * Visible fields for arrays
	 *
	 * @var array
	 */
	protected $visible = array();

	/**
	 * Fields hidden for arrays
	 *
	 * @var array
	 */
	protected $hidden = array();

	/**
	 * Fields which will be generated
	 * during build
	 * @var array
	 */
	protected $generate = array();

	/**
	 * The accessors to append to the object's array form.
	 *
	 * @var array
	 */
	protected $appends = array();

	/**
	 * Rules to validate input values for building
	 * the domain object
	 */
	protected $buildRules = array();

	public function __construct()
	{
		;
	}

    public function build(array $input)
    {
    	$this->validateInput($input);

    	$this->generateFields();

    	$this->set($input);
    }

    protected function validateInput($input)
    {
    	$this->validateInputKeys($input);

    	$this->validateInputValues($input);
    }

    protected function validateInputValues()
    {
    	$validation = Validator::make(
                        $input, 
                        $this->inputRules);

        if ($validation->fails()) 
        {
        	throw new \InvalidArgumentException($validation->errors->all());
        }
        
    }

    public function generateField($field)
    {
		return $this->{'generate'.studly_case($field).'Field'}();
    }

    protected function validateInputKeys($input)
    {
        $invalid_keys = array_diff_key($input, self::$buildRules);

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

    public function set(array $values)
    {
        $this->validateSetKeys(array_keys($values);

        foreach ($values as $key => $value)
        {
        	$this->setField($key, $value);
        }
    }

    public function setField($key, $value)
    {
    	if (! array_key_exists($key, self::$field))
    	{
	        throw new \InvalidArgumentException($key . " is not a valid key.");
    	}
		$this->data[$key] = $value;
    }

    public function getField($key)
    {
    	if (! in_array($key, Rfield))
    	{
    		throw new \InvalidKeyException;
    	}

    	if (array_key_exists($key, $this->data))
		{
			return $this->attributes[$key];
		}
    }

	/**
	 * Dynamically set attributes on the object.
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
	 * Dynamically retrieve attributes on the object.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getField($key);
	}

	/**
	 * Return object properties as array
	 *
	 * @return array
	 */
	public function toArray($flag = 0x0)
	{
		$attributes = $this->getArrayableFields();

		return $attributes
	}

	public function getArrayableFields()
	{
		if (count($this->visible) > 0)
		{
			return array_intersect_key($values, array_flip($this->visible));
		}

		return array_diff_key($values, array_flip($this->hidden));
	}

	public function getVisible()
	{
		return $this->visible;
	}

	public function getHidden()
	{
		return $this->hidden;
	}


}