<?php

namespace Models\Customer\App;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer\App;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'CustomerApps';
}
