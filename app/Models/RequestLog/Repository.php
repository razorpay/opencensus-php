<?php
namespace RZP\Models\RequestLog;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::REQUEST_LOG;
}
