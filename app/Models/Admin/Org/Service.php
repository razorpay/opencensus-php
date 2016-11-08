<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function fetchMultiple(array $input)
    {
        $orgs = $this->repo->org->fetch($input);

        return $orgs->toArrayPublic();
    }

    public function createOrg(array $input)
    {
        $org = $this->core->create($input);

        return $org->toArrayPublic();
    }

    public function getOrg(string $id)
    {
        $org = $this->repo->org->findOrFailPublic($id);

        return $org->toArrayPublic();
    }

    public function deleteOrg(string $id)
    {
        return $this->core->delete($id);
    }

    public function editOrg(string $id, array $input)
    {
        $org = $this->repo->org->findOrFailPublic($id);

        $org->fill($input);

        $this->repo->saveOrFail($org);

        return $org->toArrayPublic();
    }
}
