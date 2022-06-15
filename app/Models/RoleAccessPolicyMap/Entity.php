<?php

namespace RZP\Models\RoleAccessPolicyMap;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Base\Traits\HardDeletes;

class Entity extends Base\PublicEntity
{
    use HardDeletes;

    protected $entity  = Table::ROLE_ACCESS_POLICY_MAP;

    const ID                                    = 'id';
    const ROLE_ID                               = 'role_id';
    const AUTHZ_ROLES                           = 'authz_roles';
    const ACCESS_POLICY_IDS                     = 'access_policy_ids';
    const CREATED_AT                            = 'created_at';
    const UPDATED_AT                            = 'updated_at';

    protected static $generators = [
        self::ID,
    ];

    protected $casts = [
        self::AUTHZ_ROLES        => 'array',
        self::ACCESS_POLICY_IDS  => 'array'
    ];

    protected $fillable = [
        self::ROLE_ID,
        self::AUTHZ_ROLES,
        self::ACCESS_POLICY_IDS,
    ];

    protected $visible = [
        self::ID,
        self::ROLE_ID,
        self::AUTHZ_ROLES,
        self::ACCESS_POLICY_IDS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ROLE_ID,
        self::ACCESS_POLICY_IDS,
        self::AUTHZ_ROLES
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function getAuthzRoles()
    {
        return $this->getAttribute(self::AUTHZ_ROLES);
    }

    public function getAccessPolicyIds()
    {
        return $this->getAttribute(self::ACCESS_POLICY_IDS);
    }

    public function getRoleId()
    {
        return $this->getAttribute(self::ROLE_ID);
    }

}
