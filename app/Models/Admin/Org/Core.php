<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $org = (new Entity)->build($input);

        $this->repo->saveOrFail($org);

        return $org;
    }

    public function delete($id)
    {
        $org = $this->repo->org->findOrFail($id);

        $this->repo->deleteOrFail($org);

        return ['success' => true];
    }
}
