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

        $org = $this->repo->org->findOrFailPublic($orgId);

        $entityMap->build($input);

        $entityMap->org()->associate($org);

        $this->repo->saveOrFail($entityMap);

        return $entityMap->toArrayPublic();
    }

    public function editFieldMapForEntity(
        string $orgId,
        string $id,
        array $input)
    {
        $entityMap = $this->repo->org_field_map
                                ->findByPublicIdAndOrgId($id, $orgId);

        $entityMap->edit($input);

        $this->repo->saveOrFail($entityMap);

        return $entityMap->toArrayPublic();
    }

    public function fetchMultiple(string $orgId)
    {
        $collection = $this->repo->org_field_map
                                ->fetchByOrgId($orgId);

        return $collection->toArrayPublic();
    }

    public function getFieldsForEntity(string $orgId, string $id)
    {
        $entityMap = $this->repo->org_field_map
                                ->findByPublicIdAndOrgId($id, $orgId);

        return $entityMap->toArrayPublic();
    }

    public function getByEntity(string $orgId, string $entity)
    {
        $entityMap = $this->repo->org_field_map
                                ->findByOrgIdAndEntity($orgId, $entity);

        return $entityMap->toArrayPublic();
    }

    public function deleteFieldMapForEntity(string $orgId, string $id)
    {
        $entityMap = $this->repo->org_field_map
                                ->findByPublicIdAndOrgId($id, $orgId);

        $this->repo->deleteOrFail($entityMap);

        return $entityMap->toArrayDeleted();
    }
}
