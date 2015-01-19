<?php

namespace Models\Adjustment;

use EE\Exception;
use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Adjustment';
}