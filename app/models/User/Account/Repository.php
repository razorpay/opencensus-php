<?php

namespace Models\User;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\User;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'user';
}
