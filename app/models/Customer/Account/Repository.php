<?php

namespace Models\Customer;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'customer';
}
