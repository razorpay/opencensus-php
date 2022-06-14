<?php

namespace RZP\Models\P2p\Base;

use RZP\Base;
use RZP\Models\P2p\Base\Upi;
use RZP\Models\P2p\Base\Libraries\Rules;

class Validator extends Base\Validator
{
    protected static $fetchAllRules;
    protected static $fetchRules;

    /**
     * @var \RZP\Models\P2p\Base\Entity
     */
    protected $entity;

    protected $context;

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
        $method = 'make' . ucfirst($ruleName);

        $rules = $this->{$method}();

        $commonRules = $this->getCommonRules();

        static::$$ruleName = array_merge($commonRules, $rules->toArray());
    }

    /**
     * These are common rules and can be available in any request
     * @return array
     */
    protected function getCommonRules()
    {
        return [
            Entity::CALLBACK    => 'sometimes',
            Entity::SDK         => 'sometimes',
            Entity::SMS         => 'sometimes',
            Entity::POLL        => 'sometimes',
        ];
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
    protected function makeRules(array $with = [])
    {
        return (new Rules($this->rules()))->with($with);
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

    public function makeEntityIdRules(array $with = [])
    {
        $default = [
            Entity::ID => 'required|string'
        ];

        return $this->makeRules(array_merge($with, $default));
    }

    public function makePublicIdRules(array $with = [])
    {
        $default = [
            Entity::ID => 'required|string|custom',
        ];

        return $this->makeRules(array_merge($with, $default));
    }

    public function makeDeviceIdRules(array $with = [])
    {
        $default = [
            Entity::DEVICE_ID => 'required|string|size:21',
        ];

        return $this->makeRules(array_merge($with, $default));
    }

    public function makeFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeFetchRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    protected function validateId($attribute, $value)
    {
        // A work around to validate the public id
        $this->entity->verifyIdAndStripSign($value);
    }

    protected function validateTxn()
    {

    }

    public function withContext($context)
    {
        $this->context = $context;

        return $this;
    }
}
