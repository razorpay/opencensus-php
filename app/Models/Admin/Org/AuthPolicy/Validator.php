<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        self::TYPE                            => 'required|string',
        self::SECOND_FACTOR                   => 'required|boolean',
        self::MIN_LENGTH                      => 'required|numeric|min:6',
        self::MAX_LENGTH                      => 'required|numeric|max:50',
        self::MAX_FAILED_ATTEMPTS             => 'required|numeric|min:5',
        self::ENFORCE_INITIAL_PASSWORD_CHANGE => 'required|boolean',
        self::MAX_PASSWORD_RETAIN             => 'required|numeric|min:5',
        self::EXPIRES_IN                      => 'required|numeric|min:30',
    ];

    public function setPolicy(Entity $policy)
    {
        $this->policy = $policy;

        return $this;
    }

    public function validate($password, $op = 'create')
    {
        foreach ($this->policy->rules($op) as $rule)
        {
            $rule->validate($password);
        }
    }
}
