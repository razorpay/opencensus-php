<?php

namespace RZP\Models\Base\Audit;

use DB;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Partitions;
use RZP\Models\Base\Traits\PartitionRepo;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive, PartitionRepo;

    protected $mode;

    protected $entity = 'audit_info';

    protected function getPartitionStrategy() : string
    {
        return Partitions::DAILY;
    }

    protected function getDesiredOldPartitionsCount() : int
    {
        return 7;
    }
}
