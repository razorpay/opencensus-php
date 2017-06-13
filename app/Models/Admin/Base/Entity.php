<?php

namespace RZP\Models\Admin\Base;

use RZP\Models\Base as BaseModel;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Admin\Permission\Entity as Permission;

class Entity extends BaseModel\PublicEntity
{
    const ORG_ID            = 'org_id';
    const PERMISSION_ID     = 'permission_id';

    const ROLES       = 'roles';
    const GROUPS      = 'groups';
    const MERCHANTS   = 'merchants';
    const PERMISSIONS = 'permissions';

    public function setPublicOrgIdAttribute(array &$attributes)
    {
        $orgId = $this->getAttribute(static::ORG_ID);

        if ($orgId !== null)
        {
            $attributes[static::ORG_ID] = Org::getSignedId($orgId);
        }
    }

    public function setPublicPermissionIdAttribute(array &$attributes)
    {
        $permissionId = $this->getAttribute(static::PERMISSION_ID);

        if ($permissionId !== null)
        {
            $attributes[static::PERMISSION_ID] = Permission::getSignedId($permissionId);
        }
    }

    public function build(array $input = array())
    {
        $this->input = $input;

        $this->modify($input);

        $validator = $this->getValidator();

        // if the entity has different fields for differnt orgs
        // validation for each org will be different
        // this flags checks if validation supports different orgs
        if ((isset($validator->isOrgSpecificValidationSupported) === true) and
            ($validator->isOrgSpecificValidationSupported === true))
        {
            $orgId = $this->getAttribute(static::ORG_ID);

            $validator->validateOrgSpecificInput('create', $input, $orgId);
        }
        else
        {
            $this->validateInput('create', $input);
        }

        $this->generate($input);

        $this->unsetInput('create', $input);

        $this->fill($input);

        return $this;
    }

    public function edit(array $input = array(), $operation = 'edit')
    {
        $validator = $this->getValidator();

        if ((isset($validator->isOrgSpecificValidationSupported) === true) and
            ($validator->isOrgSpecificValidationSupported === true))
        {
            $orgId = $this->getAttribute(static::ORG_ID);

            $validator->validateOrgSpecificInput($operation, $input, $orgId);
        }
        else
        {
            $this->validateInput($operation, $input);
        }

        $this->unsetInput($operation, $input);

        $this->fill($input);

        return $this;
    }
}
