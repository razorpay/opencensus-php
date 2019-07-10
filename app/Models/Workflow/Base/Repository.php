<?php

namespace RZP\Models\Workflow\Base;

use RZP\Models\Admin\Org;
use RZP\Base\RepositoryManager;
use RZP\Models\Workflow\Action;
use RZP\Base\Repository as BaseRepository;

/**
 * Class Repository
 *
 * @package RZP\Models\Workflow\Base
 *
 * @property RepositoryManager $repo
 */
class Repository extends BaseRepository
{
    const ORG_ID = 'org_id';
    const ACTION_ID = 'action_id';

    public function findByIdAndOrgId(string $id, string $orgId, array $relations = [])
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->where(Entity::ID, '=', $id)
                    ->with($relations)
                    ->get();
    }

    public function findByIdAndOrgIdWithRelations($id, $orgId, $relations = [])
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->with($relations)
                    ->where(Entity::ID, '=', $id)
                    ->firstOrFailPublic();
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
}
