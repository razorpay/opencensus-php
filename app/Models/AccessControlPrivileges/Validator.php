<?php

namespace RZP\Models\AccessControlPrivileges;

use RZP\Base;

class Validator extends Base\Validator
{
    const CREATE_ON_AUTHZ = 'create_on_authz';

    const UPDATE_ON_AUTHZ = 'update_on_authz';

    const CREATE_PRIVILEGE_ROLE_MAPPING_ON_AUTHZ = 'create_privilege_role_mapping_on_authz';

    const UPDATE_PRIVILEGE_ROLE_MAPPING_ON_AUTHZ = 'update_privilege_role_mapping_on_authz';

    protected static $createRules = [
        Entity::NAME            => 'required|string',
        Entity::LABEL           => 'required|string',
        Entity::DESCRIPTION     => 'sometimes|string',
        Entity::PARENT_ID       => 'sometimes|string|size:14',
        Entity::VISIBILITY      => 'sometimes|int|in:0,1',
        Entity::EXTRA_DATA      => 'sometimes|array',
        Entity::VIEW_POSITION   => 'required|int',
    ];

    protected static $createOnAuthzRules = [
        Entity::NAME            => 'required|string|max:100',
        Entity::DESCRIPTION     => 'required|string|max:255',
        Entity::LABEL           => 'required|string|max:100',
        Entity::PARENT_ID       => 'sometimes|string|size:14',
        Entity::VISIBILITY      => 'sometimes|int|in:0,1',
        Entity::VIEW_POSITION   => 'required|int',
    ];

    protected static $updateOnAuthzRules = [
        Entity::ID              => 'required|string|size:14',
        Entity::NAME            => 'sometimes|string|max:100',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        Entity::LABEL           => 'sometimes|string|max:100',
        Entity::PARENT_ID       => 'sometimes|string|size:14',
        Entity::VISIBILITY      => 'sometimes|int|in:0,1',
        Entity::VIEW_POSITION   => 'sometimes|int',
    ];

    protected static $createPrivilegeRoleMappingOnAuthzRules = [
        Constants::PRIVILEGE_ID  => 'required|string|size:14',
        Constants::ACTION        => 'required|string|max:100',
        Constants::ROLE_IDS      => 'required|array',
        Constants::METADATA      => 'required|array',
    ];

    protected static $updatePrivilegeRoleMappingOnAuthzRules = [
        Entity::ID               => 'required|string|size:14',
        Constants::PRIVILEGE_ID  => 'sometimes|string|size:14',
        Constants::ACTION        => 'sometimes|string|max:100',
        Constants::ROLE_IDS      => 'sometimes|array',
        Constants::METADATA      => 'sometimes|array',
    ];
}
