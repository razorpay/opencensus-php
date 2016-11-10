<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Admin\Admin;

class Entity extends Base\PublicEntity
{
    const ID                                = 'id';
    const ORG_ID                            = 'org_id';
    const NAME                              = 'name';
    const TYPE                              = 'type';
    const SECOND_FACTOR                     = 'second_factor';
    const MIN_LENGTH                        = 'min_length';
    const MAX_LENGTH                        = 'max_length';
    const STRONG_PASSWORD                   = 'strong_password';
    const MAX_FAILED_ATTEMPTS               = 'max_failed_attempts';
    const ENFORCE_INITIAL_PASSWORD_CHANGE   = 'enforce_initial_password_change';
    const MAX_PASSWORD_RETAIN               = 'max_password_retain';
    const EXPIRES_IN                        = 'expires_in';

    protected $entity = 'auth_policy';

    public $incrementing = true;

    protected $fillable = [
        self::ORG_ID,
        self::TYPE,
        self::NAME,
        self::SECOND_FACTOR,
        self::MIN_LENGTH,
        self::MAX_LENGTH,
        self::STRONG_PASSWORD,
        self::MAX_FAILED_ATTEMPTS,
        self::ENFORCE_INITIAL_PASSWORD_CHANGE,
        self::MAX_PASSWORD_RETAIN,
        self::EXPIRES_IN,
    ];

    protected $visible = [
        self::ID,
        self::ORG_ID,
        self::TYPE,
        self::NAME,
        self::SECOND_FACTOR,
        self::MIN_LENGTH,
        self::MAX_LENGTH,
        self::STRONG_PASSWORD,
        self::MAX_FAILED_ATTEMPTS,
        self::ENFORCE_INITIAL_PASSWORD_CHANGE,
        self::MAX_PASSWORD_RETAIN,
        self::EXPIRES_IN,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ORG_ID,
        self::TYPE,
        self::NAME,
        self::MIN_LENGTH,
        self::MAX_LENGTH,
        self::STRONG_PASSWORD,
        self::MAX_FAILED_ATTEMPTS,
        self::ENFORCE_INITIAL_PASSWORD_CHANGE,
        self::MAX_PASSWORD_RETAIN,
        self::EXPIRES_IN,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::TYPE                            => 'alpha_numeric_underscore',
        self::SECOND_FACTOR                   => false,
        self::MIN_LENGTH                      => 8,
        self::MAX_LENGTH                      => 16,
        self::MAX_FAILED_ATTEMPTS             => 10,
        self::STRONG_PASSWORD                 => true,
        self::ENFORCE_INITIAL_PASSWORD_CHANGE => true,
        self::MAX_PASSWORD_RETAIN             => 10,
        self::EXPIRES_IN                      => 30,
    ];

    protected $casts = [
        self::SECOND_FACTOR                   => 'bool',
        self::MIN_LENGTH                      => 'int',
        self::MAX_LENGTH                      => 'int',
        self::MAX_FAILED_ATTEMPTS             => 'int',
        self::STRONG_PASSWORD                 => 'bool',
        self::ENFORCE_INITIAL_PASSWORD_CHANGE => 'bool',
        self::MAX_PASSWORD_RETAIN             => 'int',
        self::EXPIRES_IN                      => 'int',
    ];

    protected $createRules = [
        self::MIN_LENGTH,
        self::MAX_LENGTH,
        // self::TYPE,
        self::STRONG_PASSWORD
    ];

    protected $loginRules = [
        self::MAX_FAILED_ATTEMPTS,
        self::ENFORCE_INITIAL_PASSWORD_CHANGE
    ];

    public function toArray()
    {
        return [
            self::ORG_ID                          => null,
            self::SECOND_FACTOR                   => false,
            self::TYPE                            => 'alpha_numeric_underscore',
            self::MIN_LENGTH                      => 8,
            self::MAX_LENGTH                      => 16,
            self::STRONG_PASSWORD                 => true,
            self::MAX_FAILED_ATTEMPTS             => 10,
            self::ENFORCE_INITIAL_PASSWORD_CHANGE => true,
            self::MAX_PASSWORD_RETAIN             => 10,
            self::EXPIRES_IN                      => 30,
        ];
    }

    public function org()
    {
        return $this->hasOne('RZP\Models\Org\Entity');
    }

    public function getMaxPasswordToRetain()
    {
        return $this->getAttribute(self::MAX_PASSWORD_RETAIN);
    }

    public function rules($operation = 'create')
    {
        $rules = [];

        $rulesKey = $operation . 'Rules';

        $attributes = $this->toArray();

        foreach ($this->{$rulesKey} as $rule)
        {
            $class = 'RZP\Models\Admin\Org\AuthPolicy\Rules\\' . studly_case($rule) . 'Rule';

            $rules[] = new $class($attributes[$rule]);
        }

        return $rules;
    }
}
