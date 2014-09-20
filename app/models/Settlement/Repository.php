<?php

namespace Models\Settlement;

use Models\Base;
use Models\Ledger;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Settlement';
}