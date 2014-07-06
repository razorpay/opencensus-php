<?php

namespace Models\Manager;

use EE\Error\ErrorCode;
use EE\Exception;

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
     * Fields which will be modified before
     * input validation
     *
     * @var array
     */
    protected static $modifiers = array();

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

    protected static $sign = '';

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

        $this->modify($input);

        $this->validateInput($input, 'create');

        $this->generate($input);

        $this->unsetInput($input, 'create');

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

        $this->runValidators($input, $operation);
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
            throw new Exception\ExtraFieldsException($invalid_keys);
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
            throw new Exception\ValidationFailureException($validation->messages());
        }
    }

    protected function runValidators($input, $operation)
    {
        $operation_function_var = $operation.'Validators';

        if (isset(static::$$operation_function_var))
        {
            foreach (static::$$operation_function_var as $validator)
            {
                $validate_func = 'validate'.studly_case($validator);

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

    public function modify(& $input)
    {
        foreach (static::$modifiers as $field)
        {
            $this->modifyField($field, $input);
        }
    }

    public function generateField($field, $input)
    {
        $this->{'generate'.studly_case($field)}($input);
    }

    public function modifyField($field, & $input)
    {
        $this->{'modify'.studly_case($field)}($input);
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
        if (array_key_exists($key, $this->data))
        {
            return $this->data[$key];
        }
        else
            throw new Exception\InvalidArgumentException($key . ' is not a valid attribute');
    }

    public function setField($key, $value)
    {
        $this->data[$key] = $value;
    }

    public static function getCreateInputKeys()
    {
        return array_keys(static::$createRules);
    }

    public function getData()
    {
        return $this->data;
    }

    public static function verifyIdAndStripSign(& $id)
    {
        self::stripSignOrFail($id);

        UniqueId::verifyUid($id, true);
    }

    protected static function stripSignOrFail(& $id)
    {
        if (strpos($id, static::$sign) === false)
        {
            throw new Exception\BadRequestException(null, ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $len = strlen(static::$sign);

        //
        // add 1 to $len to account for dash
        //
        $id = substr($id, $len + 1);
    }

    public function generateId($input)
    {
        $this->setField('id', UniqueId::generateId());
    }
}