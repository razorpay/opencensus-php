<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $risk = new Entity;

        $risk->build($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function edit(Entity $risk, array $input)
    {
        $risk->edit($input);

        return $this->repo->saveOrFail($risk);
    }

    public function get(string $id)
    {
        return $this->repo->risk->findOrFailPublic($id);
    }
}
