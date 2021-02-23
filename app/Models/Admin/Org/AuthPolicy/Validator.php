<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use RZP\Base;
use RZP\Models\Admin\Admin;

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
        Entity::PASSWORD_EXPIRY                 => 'required|numeric|min:30',
    ];

    protected $passwordCreatePolicyRules = [
        Entity::MIN_LENGTH,
        Entity::MAX_LENGTH,
        Entity::MAX_PASSWORD_RETAIN,
        Entity::STRONG_PASSWORD,
        Entity::SPECIAL_CHARACTERS,
        Entity::UPPER_LOWER_CASE,
    ];

    protected $beforeLoginPolicyRules = [
        Entity::LOCKED_ACCOUNT,
        Entity::MAX_FAILED_ATTEMPTS,
    ];

    protected $afterLoginPolicyRules = [
        Entity::PASSWORD_EXPIRY
    ];

    public function validate(Admin\Entity $admin, array $data, string $op)
    {
        // password create policy rules should be checked for everyone.
        if ($admin->isSuperAdmin() === false or $op === 'passwordCreate')
        {
            $prop = $op . 'PolicyRules';

            $attributes = $this->entity->toArray();

            foreach ($this->{$prop} as $rule)
            {
                $class = 'RZP\Models\Admin\Org\AuthPolicy\Rules\\' . studly_case($rule) . 'Rule';

                // In some cases like Locked Account Rule
                // we won't need a value from the Policy Entity
                if (isset($attributes[$rule]))
                {
                    $classOb = new $class($attributes[$rule]);
                }
                else
                {
                    $classOb = new $class();
                }

                $classOb->validate($admin, $data);
            }
        }
    }
}
