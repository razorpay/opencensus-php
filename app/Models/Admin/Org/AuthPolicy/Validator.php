<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TYPE                            => 'required|string',
        Entity::SECOND_FACTOR                   => 'required|boolean',
        Entity::MIN_LENGTH                      => 'required|numeric|min:6',
        Entity::MAX_LENGTH                      => 'required|numeric|max:50',
        Entity::MAX_FAILED_ATTEMPTS             => 'required|numeric|min:5',
        Entity::ENFORCE_INITIAL_PASSWORD_CHANGE => 'required|boolean',
        Entity::MAX_PASSWORD_RETAIN             => 'required|numeric|min:5',
        Entity::EXPIRES_IN                      => 'required|numeric|min:30',
    ];

    public function setPolicy(Entity $policy)
    {
        $this->policy = $policy;

        return $this;
    }

    public function validate($admin, $password, $op = 'create')
    {
        foreach ($this->policy->rules($op) as $rule)
        {
            $response = $rule->validate($admin, $password);

            if ($response !== null)
            {
                return $response;
            }
        }
    }
}
