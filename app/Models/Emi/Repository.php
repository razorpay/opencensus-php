<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Network;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'emi_plan';

    protected $appFetchParamRules = array(
        Entity::BANK            => 'sometimes|string|size:4',
        Entity::NETWORK         => 'sometimes|string|max:12',
    );

    public function fetchEmiPlans()
    {
        return $this->newQuery()
                    ->get();
    }

    public function fetchRelevantEmiPlan(IIN\Entity $iin, int $duration)
    {
        $bank = $iin->getIssuer();
        $network = $iin->getNetworkCode();

        $query = $this->newQuery()
                      ->where(Entity::DURATION, '=', $duration);
        if ($bank)
        {
            $query->where(Entity::BANK, '=', $bank);
        }
        else if ($network === Network::AMEX)
        {
            $query->where(Entity::NETWORK, '=', $network);
        }

        return $query->firstOrFail();
    }
 }
