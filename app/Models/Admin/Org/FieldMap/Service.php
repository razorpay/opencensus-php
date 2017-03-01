<?php

namespace RZP\Models\Admin\Org\FieldMap;

use RZP\Models\Admin\Org;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function createFieldMapForEntity(string $orgId, array $input)
    {
        $input['org_id'] = Org\Entity::verifyIdAndStripSign($orgId);

        $entityMap = (new Entity)->generateId();

        $entityMap->build($input);

        $this->repo->saveOrFail($entityMap);

        return $entityMap->toArrayPublic();
    }

    public function editFieldMapForEntity(
        string $orgId,
        string $entity,
        array $input)
    {
        $entityMap = $this->repo->org_field_map
                                ->findByOrgIdAndEntity($orgId, $entity);

        $entityMap->edit($input);

        $this->repo->saveOrFail($entityMap);

        return $entityMap->toArrayPublic();
    }

    public function getFieldsForEntity(string $orgId, string $entity)
    {
        $entityMap = $this->repo->org_field_map
                                ->findByOrgIdAndEntity($orgId, $entity);

        return $entityMap->toArrayPublic();
    }

    public function deleteFieldMapForEntity(string $orgId, string $entity)
    {
        $entityMap = $this->repo->org_field_map
                                ->findByOrgIdAndEntity($orgId, $entity);

        $this->repo->deleteOrFail($entityMap);

        return $entityMap->toArrayDeleted();
    }
}
