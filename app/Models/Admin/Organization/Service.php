<?php

namespace RZP\Models\Admin\Organization;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createOrganization(array $input)
    {
        $this->core->create($input);
    }

    public function deleteOrganization(string $id)
    {
    }

    public function editOrganization(string $id, array $input)
    {
    }
}
