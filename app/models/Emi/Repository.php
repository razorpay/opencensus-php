<?php

namespace Models\Emi;

use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'emi';

    public function getAllEmiOptions()
    {
        $repo = $this->repo;

        return $repo::get();
    }
}