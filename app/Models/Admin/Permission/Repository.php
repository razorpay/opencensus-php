<?php

namespace RZP\Models\Admin\Permission;

use RZP\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'permission';
}
