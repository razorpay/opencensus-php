<?php

namespace Models\Settlement\Details;

use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'settlement_details';

    public function createMultipleEntities($entities)
    {
        foreach ($entities as $entity) 
        {
            $repo = $this->repo;

            $repo::saveOrFail($entity);
        }
    }
}