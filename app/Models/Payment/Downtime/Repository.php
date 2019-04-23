<?php

namespace RZP\Models\Payment\Downtime;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Constants\Entity as EntityConstants;

class Repository extends Base\Repository
{
    protected $entity = EntityConstants::PAYMENT_DOWNTIME;

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

    public function fetchOngoingDowntimesByMethod(string $method): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(Entity::METHOD, $method)
              ->whereNull(Entity::END)
              ->where(Entity::BEGIN, '<=', Carbon::now()->getTimestamp());

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

    public function fetchFutureScheduledDowntimesToActivate(int $now)
    {
        $query = $this->newQuery()
                      ->where(Entity::BEGIN, '<=', $now)
                      ->where(Entity::STATUS, '=', Status::SCHEDULED);

        return $query->get();
    }

    public function fetchPastScheduledDowntimesToResolve(int $now)
    {
        $query = $this->newQuery()
                      ->where(Entity::STATUS, '=', Status::STARTED)
                      ->where(function ($query) use ($now)
                      {
                        $query->whereNotNull(Entity::END)
                              ->where(Entity::END, '<=', $now);
                      });

        return $query->get();
    }
}
