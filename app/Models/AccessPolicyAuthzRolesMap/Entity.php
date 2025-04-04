<?php


namespace RZP\Models\AccessPolicyAuthzRolesMap;

use RZP\Constants;
use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    const PRIVILEGE_ID  = 'privilege_id';
    const ACTION        = 'action';
    const AUTHZ_ROLES   = 'authz_roles';
    const META_DATA     = 'meta_data';

    const ACTION_TYPE_VIEW = 'view';
    const ACTION_TYPE_VIEW_ALL = 'view_all';
    const ACTION_TYPE_CREATE = 'create';
    const ACTION_TYPE_APPROVE = 'approve';
    const ACTION_TYPE_UPDATE = 'update';
    const ACTION_TYPE_AMEND = 'amend';

    const ACTION_TYPES = [
        self::ACTION_TYPE_VIEW,
        self::ACTION_TYPE_CREATE,
        self::ACTION_TYPE_VIEW_ALL,
        self::ACTION_TYPE_APPROVE,
        self::ACTION_TYPE_UPDATE,
        self::ACTION_TYPE_AMEND,
    ]; 

    protected $entity = Constants\Table::ACCESS_POLICY_AUTHZ_ROLES_MAP;

    protected static $generators = [
        self::ID,
    ];

    protected $casts = [
        self::AUTHZ_ROLES => 'array',
        self::META_DATA   => 'array'
    ];

    protected $fillable = [
        self::PRIVILEGE_ID,
        self::ACTION,
        self::AUTHZ_ROLES,
        self::META_DATA,
    ];

    protected $visible = [
        self::ID,
        self::PRIVILEGE_ID,
        self::ACTION,
        self::AUTHZ_ROLES,
        self::META_DATA,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::PRIVILEGE_ID,
        self::ACTION,
        self::META_DATA,
    ];

    protected $defaults = [
        self::META_DATA => [
            "tool_tip" => "-",
        ],
    ];

    public function setPrivilegeId(string $privilegeId)
    {
        $this->setAttribute(self::PRIVILEGE_ID, $privilegeId);
    }

    public function setAction(string $action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setAuthzRoles(array $authzRoles)
    {
        $this->setAttribute(self::AUTHZ_ROLES, $authzRoles);
    }

    public function setMetaData(array $metaData)
    {
        $this->setAttribute(self::META_DATA, $metaData);
    }

    public function getPrivilegeId()
    {
        return $this->getAttribute(self::PRIVILEGE_ID);
    }


    public function getAction()
    {
        return $this->getAttribute(self::ACTION);
    }

    public function getAuthzRoles()
    {
        return $this->getAttribute(self::AUTHZ_ROLES);
    }

    public function getMetaData()
    {
        return $this->getAttribute(self::META_DATA);
    }
}
