<?php

namespace RZP\Models\Admin\Org\PasswordPolicy;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Admin\Admin;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const ORG_ID                        = 'org_id';
    const NAME                          = 'name';
    const TYPE                          = 'type';
    const MIN_LENGTH                    = 'min_length';
    const MAX_LENGTH                    = 'max_length';
    const MAX_FAILED_ATTEMPTS           = 'max_failed_attempts';
    const FORCE_CHANGE_INITIAL_PASSWORD = 'force_change_initial_password';
    const MAX_PASSWORD_RETAIN           = 'max_password_retain';
    const EXPIRIES_IN                   = 'expiries_in';

    protected $entity = 'password_policy';

    public $incrementing = true;

    protected $fillable = [
        self::ORG_ID,
        self::TYPE,
        self::NAME,
        self::MIN_LENGTH,
        self::MAX_LENGTH,
        self::MAX_FAILED_ATTEMPTS,
        self::FORCE_CHANGE_INITIAL_PASSWORD,
        self::MAX_PASSWORD_RETAIN,
        self::EXPIRIES_IN,
    ];

    protected $visible = [
        self::ID,
        self::ORG_ID,
        self::TYPE,
        self::NAME,
        self::MIN_LENGTH,
        self::MAX_LENGTH,
        self::MAX_FAILED_ATTEMPTS,
        self::FORCE_CHANGE_INITIAL_PASSWORD,
        self::MAX_PASSWORD_RETAIN,
        self::EXPIRIES_IN,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ORG_ID,
        self::TYPE,
        self::NAME,
        self::MIN_LENGTH,
        self::MAX_LENGTH,
        self::MAX_FAILED_ATTEMPTS,
        self::FORCE_CHANGE_INITIAL_PASSWORD,
        self::MAX_PASSWORD_RETAIN,
        self::EXPIRIES_IN,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::TYPE                          => 'alpha_numeric',
        self::MAX_FAILED_ATTEMPTS           => 10,
        self::FORCE_CHANGE_INITIAL_PASSWORD => true,
        self::MAX_PASSWORD_RETAIN           => 10,
        self::EXPIRIES_IN                   => 30,
    ];

    protected $casts = [
        self::MIN_LENGTH                    => 'int',
        self::MAX_LENGTH                    => 'int',
        self::MAX_FAILED_ATTEMPTS           => 'int',
        self::FORCE_CHANGE_INITIAL_PASSWORD => 'bool',
        self::MAX_PASSWORD_RETAIN           => 'int',
        self::EXPIRIES_IN                   => 'int',
    ];

    protected $createRules = [
        self::TYPE,
        self::MIN_LENGTH,
        self::MAX_LENGTH,
    ];

    public function toArray()
    {
        return [
            self::ORG_ID                        => null,
            self::TYPE                          => 'alpha_numeric',
            self::MIN_LENGTH                    => 4,
            self::MAX_LENGTH                    => 16,
            self::MAX_FAILED_ATTEMPTS           => 10,
            self::FORCE_CHANGE_INITIAL_PASSWORD => true,
            self::MAX_PASSWORD_RETAIN           => 10,
            self::EXPIRIES_IN                   => 30,
        ];
    }

    public function rules($operation = 'create')
    {
        $rules = [];

        $rulesKey = $operation . 'Rules';

        $attributes = $this->toArray();

        foreach ($this->{$rulesKey} as $rule)
        {
            $class = 'Rules\\' . studly_case($rule) . 'Rule';

            $rules[] = new $class($attributes[$rule]);
        }

        return $rules;
    }
}
