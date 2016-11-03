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

    public function createOrg(array $input)
    {
        $this->core->create($input);
    }

    public function deleteOrg(string $id)
    {
    }

    public function editOrg(string $id, array $input)
    {
    }
}
