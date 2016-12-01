<?php

namespace RZP\Base;

use RZP\Exception;

class JitValidator extends Validator
{
    /**
     * Object calling the validator.
     */
    protected $caller;

    protected static $rules = [];

    protected $input = [];

    /**
     * If strict is set, then input keys
     * need to be within the keys for
     * which rules are defined.
     */
    protected $strict = true;

    protected $validators;

    public function rules(array $rules)
    {
        static::$rules = $rules;

        return $this;
    }

    public function caller($caller)
    {
        $this->caller = $caller;

        return $this;
    }

    public function strict(bool $strict)
    {
        $this->strict = $strict;

        return $this;
    }

    public function input(array $input)
    {
        $this->input = $input;

        return $this;
    }

    public function validate($input = null)
    {
        if ($input !== null)
        {
            $this->input($input);
        }

        $this->validateInput('', $this->input);
    }

    /**
     * Ideally, the custom function should have been defined in this class.
     * However, this being JitValidator means the caller class is not
     * extending base validator class and we need to call the validateCustom
     * function of the caller class which in turn will internally
     * call the $func which is usually defined as protected.
     */
    protected function callCustomRuleValidatorFunction($func, $attribute, $value, $parameters)
    {
        $this->caller->validateCustom($func, $attribute, $value, $parameters);
    }
}
