<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Base;

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
}
