<?php

namespace RZP\Models\Admin\Org\PasswordPolicy;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        self::STRENGTH                      => 'required|numeric|in:low,medium,high',
        self::MAX_FAILED_ATTEMPTS           => 'required|numeric|min:5',
        self::FORCE_CHANGE_INITIAL_PASSWORD => 'required|boolean',
        self::MAX_PASSWORD_RETAIN           => 'required|numeric|min:5',
        self::EXPIRIES_IN                   => 'required|numeric|min:30',
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
