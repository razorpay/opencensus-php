<?php

namespace RZP\Models\Admin\Base;

use RZP\Models\Admin\Org;
use RZP\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    public function findByPublicIdAndOrgId(string & $id, string & $orgId)
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->findOrFailPublic($id);
    }

    public function findByIdAndOrgId($id, $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->findOrFailPublic($id);
    }

    public function findByPublicIdAndOrgIdWithRelations(
        $id,
        $orgId,
        $relations = [])
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->findByIdAndOrgIdWithRelations($id, $orgId, $relations);
    }

    public function findByIdAndOrgIdWithRelations($id, $orgId, $relations = [])
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->with($relations)
                    ->findOrFailPublic($id);
    }

    public function fetchByOrgId(string & $orgId, $relations = [])
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->with($relations)
                    ->get();
    }

    public function findByPublicIdWithRelations(
        string $id,
        array $relations = [])
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->newQuery()
                    ->where(Entity::ID, '=', $id)
                    ->with($relations)
                    ->firstOrFailPublic();
    }
}
