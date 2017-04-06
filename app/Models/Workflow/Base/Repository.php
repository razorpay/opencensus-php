<?php

namespace RZP\Models\Workflow\Base;

use RZP\Models\Admin\Org;
use RZP\Models\Workflow\Action;
use RZP\Base\Repository as BaseRepository;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends BaseRepository
{
    use RepositoryUpdateTestAndLive;

    const ORG_ID = 'org_id';
    const ACTION_ID = 'action_id';

    public function findByIdAndOrgId(string $id, string $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->where(Entity::ID, '=', $id)
                    ->get();
    }

    public function fetchByActionId(string $actionId)
    {
        Action\Entity::verifyIdAndSilentlyStripSign($actionId);

        return $this->newQuery()
                    ->where(self::ACTION_ID, '=', $actionId)
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
