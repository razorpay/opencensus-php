<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;   
    use Base\RepositoryUpdateTestAndLive;
    
    protected $entity = 'emi';

    public function getAllEmiPlans()
    {
        $repo = $this->repo;

        return $repo::get();
    }

    public function fetchByBankAndDuration($bank, $duration)
    {
        $repo = $this->repo;

        return $repo::where(Entity::BANK, '=', $bank)
                    ->where(Entity::DURATION, '=', $duration)
                    ->firstOrFail();    
    }
}