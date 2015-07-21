<?php

namespace Models\Settlement;

use Models\Base;
use Models\Settlement;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Settlement';
}
