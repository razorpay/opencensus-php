<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class Validator
{
    /**
     * Validator functions that will be
     * run during build on input data
     */
    protected static $validators = array();

    /**
     * Rules to validate input values for building
     * the domain object
     */
    protected static $createRules = array();

    public function __construct()
    {
        ;
    }

    public static function createValidate(array $input)
    {
        $validator = new static();

        $validator->validateInput($input, 'create');
    }

    /**
     * Verifies validity of input values and keys.
     * @param  array    $input     Input array supplied
     * @param  string   $operation Operation for which input
     *                             is supplied
     *
     * @return void     throws exception for error
     */
    public function validateInput($input, $operation)
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

    public static function getCreateInputKeys()
    {
        return array_keys(static::$createRules);
    }
}