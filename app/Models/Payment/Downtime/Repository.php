<?php

namespace RZP\Models\Payment\Downtime;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Base\PublicCollection;

class Repository extends Base\Repository
{
    protected $entity = 'payment_downtime';

    public function fetchCurrentAndFutureDowntimes(): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(function ($query)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>=', Carbon::now()->getTimestamp());
        });

        return $query->get();
    }

    public function getDuplicate(array $input)
    {
        $query = $this->newQuery()
                      ->where(Entity::METHOD, $input[Entity::METHOD])
                      ->where(Entity::BEGIN, $input[Entity::BEGIN]);

        if (isset($input[Entity::END]) === true)
        {
            $query->where(Entity::END, $input[Entity::END]);
        }
        else
        {
            $query->whereNull(Entity::END);
        }

        return $query->first();
    }
}
