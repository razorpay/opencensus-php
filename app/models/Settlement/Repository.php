<?php

namespace Models\Settlement;

use Models\Base;
use Models\Transaction;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Settlement';
}