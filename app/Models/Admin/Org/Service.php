<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function fetchMultiple(array $input)
    {
        $orgs = $this->repo->org->fetch($input);

        return $orgs->toArrayPublic();
    }

    public function createOrg(array $input)
    {
        $org = $this->core()->create($input);

        return $org->toArrayPublic();
    }

    public function getOrg(string $id)
    {
        $org = $this->repo->org->findByPublicId($id);

        return $org->toArrayPublic();
    }

    public function deleteOrg(string $id)
    {
        $org = $this->repo->org->findByPublicId($id);

        return $this->core()->delete($org);
    }

    public function editOrg(string $id, array $input)
    {
        $org = $this->repo->org->findOrFailPublic($id);

        $org->edit($input);

        $this->repo->saveOrFail($org);

        return $org->toArrayPublic();
    }

    public function getOrgMultiple(array $input)
    {
        $orgs = $this->repo->org->fetch($input);

        return $orgs->toArrayPublic();
    }
}
