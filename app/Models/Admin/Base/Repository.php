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

    public function findByPublicIdAndOrg(string & $id, Org\Entity $org)
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->findByIdAndOrg($id, $org);
    }

    public function findByIdAndOrg($id, Org\Entity $org)
    {
        return $this->newQuery()
                    ->orgId($org->getId())
                    ->findOrFailPublic($id);
    }
}
