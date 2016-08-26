<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;
use RZP\Models\Card\Network;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'emi_plan';

    public function getAllEmiPlans()
    {
        return $this->newQuery()->get();
    }

    public function fetchRelevantEmiPlan($iin, $duration)
    {
        $bank = $iin->getIssuer();
        $network = $iin->getNetworkCode();

        $query = $this->newQuery()
                      ->where(Entity::DURATION, '=', $duration);

        if ($bank)
        {
            $query->where(Entity::BANK, '=', $bank);
        }

        if ($network === Network::AMEX)
        {
            $query->where(Entity::NETWORK, '=', $network);
        }

        return $query->firstOrFail();
    }
}