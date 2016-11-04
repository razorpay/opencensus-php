<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'role';
}
