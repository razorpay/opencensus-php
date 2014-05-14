<?php

namespace Models\DO;

class DomainObject extends \Eloquent
{

	/**
	 * The input using which DO is created
	 *
	 * @var array
	 */
	protected $input = array();

	/**
	 * Fields which will be generated
	 * during build
	 * @var array
	 */
	protected $generators = array();

	/**
	 * Validator functions that will be 
	 * run during build on input data
	 */
	protected $validators = array();

	/**
	 * Input keys which will be unset
	 * before hitting save during create
	 */
	protected $unsetCreateInput = array();

	/**
	 * Rules to validate input values for create
	 * the domain object
	 */
	protected $createRules = array();

	protected $updateRules = array();

	/**
	 * Whether or not to throw error when
	 * calling setting a field not defined
	 * in $fields array. By default such keys
	 * are ignored. If this bool is true, then
	 * we throw an exception.
	 * 
	 * @var boolean
	 */
	protected bool $errorOnUnknownKeys = true;

	public function __construct(array $attributes = array())
	{
		parent::construct(array $attributes = array());
	}

    public function save(array $options = array())
    {
    	if (! $this->exists)
    	{
	    	$this->validateInput('create');

	    	$this->generate();

	    	$this->unsetCreateInput();
	    }

	    parent::save($options);
    }

    public function update()
    {
    	if (! $this->exists)
    		throw new \LogicException('Exists should be true');

    	$this->validateInput('Update');
    }

    protected function validateInput($operation)
    {
    	$this->validateInputKeys($operation);

    	$this->validateInputValues($opration);
    }

    protected function validateInputValues($operation)
    {
    	$validation = Validator::make(
                        $this->attributes,
                        $this->{$operation.'Rules'});

        if ($validation->fails()) 
        {
        	throw new \InvalidArgumentException($validation->errors->all());
        }

        $this->runValidators($operation);
    }

    protected function runValidators($operation)
    {
    	foreach ($this->{$operation.'Validators' as $validator)
    	{
    		$this->{'validate'.$validator}($input);
    	}
    }

    public function unsetCreateInput()
    {
    	foreach ($this->$unsetInput as $key)
    	{
    		unset($this->attributes[$key]);
    	}
    }

    public function generate()
    {
    	foreach ($this->generators as $attribute)
    	{
    		$this->generateField($attribute);
    	}
    }

    public function generateAttribute($attribute)
    {
		return $this->{'generate'.studly_case($attribute).'Attribute'}();
    }

    protected function validateInputKeys($operation)
    {
        $invalid_keys = array_diff_key($this->attributes, $this->{$operation.'Rules'});

        if (count($invalid_keys) !== 0)
        {
            throw new Exceptions\InvalidKeysException($invalid_keys);
        }
    }


	const DEFAULT = 0x0;
	const FIELDS = 0x1;
	const ALL_FIELDS = 0x2;
	const APPENDS = 0x3;

	/**
	 * Return object properties as array
	 *
	 * @return array
	 */
	public function toArrayCustom($flag = 0x0)
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

		foreach ($this->appends as $key)
		{
			$appends[$key] = $this->getField($key);
		}

		return $appends;
	}

	public static function getVisible()
	{
		return $this->$visible;
	}

	public static function getHidden()
	{
		return $this->$hidden;
	}

	public static function getInputKeys($operation)
	{
		return array_keys($this->{$operation.'Rules'});
	}
}