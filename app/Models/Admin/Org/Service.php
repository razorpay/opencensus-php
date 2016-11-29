<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $org = $this->core()->create($input);

        return $org->toArrayPublic();
    }

    public function fetch(string $id)
    {
        $org = $this->core()->fetch($id);

        return $org->toArrayPublic();
    }

    public function fetchByHostname(string $hostname)
    {
        $org = $this->repo->org->findOrFailByHostname($hostname);

        return $org->toArrayPublic();
    }

    public function delete(string $id)
    {
        return $this->core()->delete($id);
    }

    public function edit(string $id, array $input)
    {
        $org = $this->core()->edit($id, $input);

        return $org->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $orgs = $this->repo->org->fetch($input);

        return $orgs->toArrayPublic();
    }
}
