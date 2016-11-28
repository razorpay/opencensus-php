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

    public function create(array $input)
    {
        $org = $this->core->create($input);

        return $org->toArrayPublic();
    }

    public function fetch(string $id)
    {
        $org = $this->core->fetch($id);

        return $org->toArrayPublic();
    }

    public function fetchByHostname(string $hostname)
    {
        $org = $this->repo->org->findOrFailByHostname($orgId);

        return $org->toArrayPublic();
    }

    public function delete(string $id)
    {
        return $this->core->delete($id);
    }

    public function edit(string $id, array $input)
    {
        $org = $this->core->edit($id);

        return $org->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $orgs = $this->repo->org->fetch($input);

        return $orgs->toArrayPublic();
    }
}
