<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $org = new Entity;
        $org->generateId();

        $org->build($input);

        $this->repo->saveOrFail($org);

        return $org;
    }

    public function fetch(string $orgId)
    {
        $orgId = Entity::verifyIdAndStripSign($orgId);

        return $this->repo->org->findOrFailPublic($orgId);
    }

    public function edit(string $orgId, array $input)
    {
        $orgId = Entity::verifyIdAndStripSign($orgId);

        $org = $this->repo->org->findOrFailPublic($orgId);

        $org->edit($input);

        $this->repo->saveOrFail($org);

        return $org;
    }

    public function delete($id)
    {
        $id = Entity::verifyIdAndStripSign($id);

        $org = $this->repo->org->findOrFail($id);

        $this->repo->deleteOrFail($org);

        return ['success' => true];
    }
}
