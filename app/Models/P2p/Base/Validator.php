<?php

namespace RZP\Models\P2p\Base;

use RZP\Base;
use RZP\Models\P2p\Base\Libraries\Rules;

class Validator extends Base\Validator
{
    /**
     * Overriding this method allows us to register rules for defined action
     *
     * @param $operation
     * @return string
     */
    protected function getRulesVariableName($operation)
    {
        $ruleName = parent::getRulesVariableName($operation);

        $this->registerRulesForName($ruleName);

        return $ruleName;
    }

    /**
     * We can resolve static rules on run time
     *
     * @param string $ruleName
     */
    protected function registerRulesForName(string $ruleName)
    {
        $method = 'get' . ucfirst($ruleName);

        $rules = $this->{$method}();

        static::$$ruleName = $rules->toArray();
    }

    /**
     * All common rules can be defined in this function
     *
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * Make Rules provide easy way to access or modify rules
     *
     * @return Rules
     */
    protected function makeRules(array $with)
    {
        return (new Rules($this->rules(), $with));
    }

    protected function arrayRules(string $prepend, array $rules)
    {
        $prepended = [
            $prepend    => 'sometimes|array',
        ];

        foreach ($rules as $key => $rule)
        {
            $prepended[$prepend . '.' . $key] = $rule;
        }

        return $prepended;
    }
}
