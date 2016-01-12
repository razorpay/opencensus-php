<?php

namespace Models\Emi;

use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Emi';
}