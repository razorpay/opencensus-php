<?php

namespace RZP\Models\Admin\Base;

use RZP\Models\Admin\Org;
use RZP\Base\Repository as BaseRepository;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends BaseRepository
{
    use RepositoryUpdateTestAndLive;

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
        $relations=[])
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

    public function fetchByOrgId(string & $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->get();
    }
}
