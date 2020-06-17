<?php

namespace RZP\Models\Payment\Downtime;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment\Downtime\Constants;
use RZP\Models\Payment\Method;
use RZP\Models\Base\PublicCollection;
use RZP\Constants\Entity as EntityConstants;

class Repository extends Base\Repository
{
    protected $entity = EntityConstants::PAYMENT_DOWNTIME;

    public function fetchOngoingDowntimes(): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(function ($query)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>', Carbon::now()->getTimestamp());
        });

        $query->where(Entity::BEGIN, '<=', Carbon::now()->getTimestamp());

        return $query->get();
    }

    public function fetchOngoingDowntimesByMethod(string $method): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(Entity::METHOD, $method);

        $query->where(function ($query)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>', Carbon::now()->getTimestamp());
        });

        $query->where(Entity::BEGIN, '<=', Carbon::now()->getTimestamp());

        return $query->get();
    }

    public function getDuplicate(array $input)
    {
        $query = $this->newQuery()
                      ->where(Entity::METHOD, $input[Entity::METHOD]);

        $query->where(function ($query) use ($input)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>=', $input[Entity::BEGIN]);
        });

        if (isset($input[Entity::END]) === true)
        {
            $query->where(Entity::BEGIN, '<=', $input[Entity::END]);
        }

        $this->addMethodSpecificQuery($query, $input);

        return $query->first();
    }

    protected function addMethodSpecificQuery($query, $input)
    {
        $method = $input[Entity::METHOD];

        $attributes = Constants::getMethodQueryInstrument($method);

        if (count($attributes) === 1)
        {
            $attribute = $attributes[0];

            $query->where($attribute, $input[$attribute]);
        }
        else
        {
            foreach ($attributes as $attribute)
            {
                if(isset($input[$attribute]) && $input[$attribute] != Entity::NA)
                {
                    $query->where($attribute, $input[$attribute]);
                    break;
                }
            }
        }
    }

    public function fetchFutureScheduledDowntimesToActivate(int $now): PublicCollection
    {
        $query = $this->newQuery()
                      ->where(Entity::BEGIN, '<=', $now)
                      ->where(Entity::STATUS, '=', Status::SCHEDULED);

        $query->where(function ($query)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>', Carbon::now()->getTimestamp());
        });

        return $query->get();
    }

    public function fetchPastScheduledDowntimesToResolve(int $now): PublicCollection
    {
        $query = $this->newQuery()
                      ->where(function ($query) use ($now)
                      {
                        $query->whereNotNull(Entity::END)
                              ->where(Entity::END, '<=', $now);
                      });

        $query->whereIn(Entity::STATUS, [Status::SCHEDULED, Status::STARTED]);

        return $query->get();
    }
}
