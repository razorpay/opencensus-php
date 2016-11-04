<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'admin';
}
