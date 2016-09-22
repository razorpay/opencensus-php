<?php

namespace RZP\Models\Payout;

use RZP\Models\Base;
use RZP\Models\Payout;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Payout';

    public function fetchCreatedPayouts($timestamp, $method)
    {
        return $this->newQuery()
                    ->where(Payout\Entity::CREATED_AT, '<', $timestamp)
                    ->where(Payout\Entity::STATUS, '=', Payout\Status::CREATED)
                    ->where(Payout\Entity::METHOD, '=', $method)
                    ->orderBy(Payout\Entity::ID)
                    ->get();
    }
}