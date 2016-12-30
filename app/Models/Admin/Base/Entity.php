<?php

namespace RZP\Models\Admin\Base;

use RZP\Models\Base as BaseModel;

class Entity extends BaseModel\PublicEntity
{
    const ROLES       = 'roles';
    const GROUPS      = 'groups';
    const MERCHANTS   = 'merchants';
    const PERMISSIONS = 'permissions';

    public function setPublicOrgIdAttribute(array & $attributes)
    {
        $orgId = $this->getAttribute(self::ORG_ID);

        if ($orgId !== null)
        {
            $attributes[self::ORG_ID] = Org::getSignedId($orgId);
        }
    }
}