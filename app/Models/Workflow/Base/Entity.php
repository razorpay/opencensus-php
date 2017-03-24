<?php

namespace RZP\Models\Workflow\Base;

use RZP\Models\Base as BaseModel;
use RZP\Models\Admin\Org\Entity as Org;

class Entity extends BaseModel\PublicEntity
{
    public function setPublicOrgIdAttribute(array &$attributes)
    {
        $orgId = $this->getAttribute(static::ORG_ID);

        if ($orgId !== null)
        {
            $attributes[static::ORG_ID] = Org::getSignedId($orgId);
        }
    }
}
