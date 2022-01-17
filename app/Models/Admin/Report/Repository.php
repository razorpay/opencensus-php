<?php

namespace RZP\Models\Admin\Report;

use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\Admin\Org\Hostname;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Permission;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'admin_report';
}
