<?php

namespace RZP\Models\FundLoadingDowntime;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestValidationFailureException;

class Repository extends \RZP\Models\Base\Repository
{
    protected $entity = Entity::FUND_LOADING_DOWNTIMES;

    public function saveOrFailEntity(Entity $downtime)
    {
        if (empty($downtime->getEndTime()) === false and
            (int) $downtime->getStartTime() > (int) $downtime->getEndTime())
        {
            throw new BadRequestValidationFailureException('End time should be greater than start time');
        }

        $this->repo->fund_loading_downtimes->saveOrFail($downtime);
    }

    public function fetchByCurrentTime($params): PublicCollection
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();
        $this->processFetchParams($params);

        $query = $this->newQuery();

        $query = $this->buildFetchQuery($query, $params);

        return $query->where(Entity::START_TIME, '<=', $currentTime)
                     ->where(function($query) use ($currentTime) {
                         return $query->whereNull(Entity::END_TIME)
                                      ->orWhere(Entity::END_TIME, '>', $currentTime);
                     })
                     ->get();
    }

    public function fetchDowntimeBetweenTimestamp($params)
    {
        $this->processFetchParams($params);

        $startTime = $params['start_time'];
        unset($params['start_time']);

        $endTime = array_key_exists('end_time', $params) ? $params['end_time'] : null;
        unset($params['end_time']);

        $query = $this->newQuery();

        $query = $this->buildFetchQuery($query, $params);

        $query->where(Entity::START_TIME, '>=', $startTime);

        return (empty($endTime) === false) ?
            $query->where(Entity::END_TIME, '<=', $endTime)->get() :
            $query->get();
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::UPDATED_AT, 'desc')
              ->orderBy(Entity::START_TIME, 'desc');
    }

    public function getSimilarDowntime($params, $downtime = null)
    {
        if (empty($downtime) === false)
        {
            $params[Entity::START_TIME] = $params[Entity::START_TIME] ?? $downtime[Entity::START_TIME];
            $params[Entity::END_TIME]   = $params[Entity::END_TIME] ?? $downtime[Entity::END_TIME];
            $params[Entity::MODE]       = $params[Entity::MODE] ?? $downtime[Entity::MODE];
            $params[Entity::CHANNEL]    = $params[Entity::CHANNEL] ?? $downtime[Entity::CHANNEL];
            $params[Entity::SOURCE]     = $params[Entity::SOURCE] ?? $downtime[Entity::SOURCE];
        }

        return $this->newQuery()
                    ->where(Entity::START_TIME, '=', $params[Entity::START_TIME])
                    ->where(Entity::END_TIME, '=', $params[Entity::END_TIME] ?? null)
                    ->where(Entity::CHANNEL, '=', $params[Entity::CHANNEL])
                    ->where(Entity::MODE, '=', $params[Entity::MODE])
                    ->where(Entity::SOURCE, '=', $params[Entity::SOURCE])
                    ->first();
    }

}
