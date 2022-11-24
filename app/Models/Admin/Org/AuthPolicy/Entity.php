<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use App;
use RZP\Models\Base\Traits\RevisionableTrait;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Admin\Admin;

class Entity extends Base\PublicEntity
{
    use RevisionableTrait;

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
    const INACTIVITY_LOCK                   = 'inactivity_lock';
    const PASSWORD_EXPIRY                   = 'password_expiry';
    const SPECIAL_CHARACTERS                = 'special_characters';
    const UPPER_LOWER_CASE                  = 'upper_lower_case';

    // Not used by the Entity but by the Validator
    const LOCKED_ACCOUNT                    = 'locked_account';

    protected $entity = 'auth_policy';

    public $incrementing = true;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $dontKeepRevisionOf = [
        self::SECOND_FACTOR,
        self::STRONG_PASSWORD,
        self::ENFORCE_INITIAL_PASSWORD_CHANGE,
        self::MAX_PASSWORD_RETAIN
    ];

    protected $fillable = [
        self::TYPE,
        self::NAME,
        self::SECOND_FACTOR,
        self::MIN_LENGTH,
        self::MAX_LENGTH,
        self::STRONG_PASSWORD,
        self::MAX_FAILED_ATTEMPTS,
        self::ENFORCE_INITIAL_PASSWORD_CHANGE,
        self::MAX_PASSWORD_RETAIN,
        self::PASSWORD_EXPIRY,
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
        self::PASSWORD_EXPIRY,
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
        self::PASSWORD_EXPIRY,
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
        self::PASSWORD_EXPIRY                 => 30,
    ];

    protected $casts = [
        self::SECOND_FACTOR                   => 'bool',
        self::MIN_LENGTH                      => 'int',
        self::MAX_LENGTH                      => 'int',
        self::MAX_FAILED_ATTEMPTS             => 'int',
        self::STRONG_PASSWORD                 => 'bool',
        self::ENFORCE_INITIAL_PASSWORD_CHANGE => 'bool',
        self::MAX_PASSWORD_RETAIN             => 'int',
        self::PASSWORD_EXPIRY                 => 'int',
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
            self::PASSWORD_EXPIRY                 => 30,
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

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
