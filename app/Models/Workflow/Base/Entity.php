<?php

namespace RZP\Models\Workflow\Base;

use RZP\Models\Admin\Org;
use RZP\Models\Base as BaseModel;

class Entity extends BaseModel\PublicEntity
{
    public function setPublicOrgIdAttribute(array &$attributes)
    {
        $orgId = $this->getAttribute(static::ORG_ID);

        if ($orgId !== null)
        {
            $attributes[static::ORG_ID] = Org\Entity::getSignedId($orgId);
        }
    }
}
