<?php

namespace RZP\Models\Merchant\Product\TncMap;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        (new Validator)->validateInput('create', $input);

        $request = (new Entity)->generateId();

        $request->build($input);

        $this->repo->saveOrFail($request);

        return $request;
    }

    public function fetch(string $id)
    {
        $entity = $this->repo->tnc_map->findOrFailPublic($id);

        return $entity;
    }

    public function fetchAll(array $input)
    {
        $entities = $this->repo->tnc_map->fetch($input);

        return $entities;
    }

    public function update(Entity $tnc, $input)
    {
        (new Validator)->validateInput('edit', $input);

        $tnc->edit($input);

        $this->repo->saveOrFail($tnc);

        return $tnc;
    }
}
