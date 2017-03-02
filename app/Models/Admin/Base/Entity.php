<?php

namespace RZP\Models\Admin\Base;

use RZP\Models\Base as BaseModel;
use RZP\Models\Admin\Org\Entity as Org;

class Entity extends BaseModel\PublicEntity
{
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

    public function buildWithOrg(array $input = array(), string $orgId)
    {
        $this->input = $input;

        $this->modify($input);

        $validator = $this->getValidator();

        $validator->validateOrgSpecificInput('create', $input, $orgId);

        $this->generate($input);

        $this->unsetInput('create', $input);

        $this->fill($input);

        return $this;
    }
}
