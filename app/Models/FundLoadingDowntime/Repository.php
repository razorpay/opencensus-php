<?php

namespace RZP\Models\FundLoadingDowntime;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
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
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_MULTIPLE_FETCH_REQUEST,
                           [
                               'params' => $params
                           ]
        );

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $this->processFetchParams($params);

        $query = $this->newQuery();

        $query = $this->buildFetchQuery($query, $params);

        // SQL Query (assuming params = [ 'channel' => 'icicibank', 'source' => 'Partner Bank']) :
        // select * from `fund_loading_downtimes`
        // where `fund_loading_downtimes`.`channel` = ? and `fund_loading_downtimes`.`source` = ?
        // and `start_time` <= ? and (`end_time` is null or `end_time` > ?)
        // order by `updated_at` desc, `start_time` desc limit 1000
        return $query->where(Entity::START_TIME, '<=', $currentTime)
                     ->where(function($query) use ($currentTime) {
                         return $query->whereNull(Entity::END_TIME)
                                      ->orWhere(Entity::END_TIME, '>', $currentTime);
                     })
                     ->get();
    }

    public function fetchDowntimeBetweenTimestamp($params)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_MULTIPLE_FETCH_REQUEST,
                           [
                               'params' => $params
                           ]
        );

        $this->processFetchParams($params);

        $startTime = array_key_exists(Entity::START_TIME, $params) ? $params[Entity::START_TIME] : null;
        unset($params[Entity::START_TIME]);

        $endTime = array_key_exists(Entity::END_TIME, $params) ? $params[Entity::END_TIME] : null;
        unset($params[Entity::END_TIME]);

        $query = $this->newQuery();

        $query = $this->buildFetchQuery($query, $params);

        if(empty($startTime) === false)
        {
            $query->where(Entity::START_TIME, '>=', $startTime);
        }

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

    /** This is for fetching downtimes from admin dashboard.
     *
     * @param array       $params
     * @param string|null $mid
     * @param string|null $connection
     *
     * @return PublicCollection
     * @throws BadRequestValidationFailureException
     * @throws \RZP\Exception\InvalidArgumentException
     */
    public function fetch(array $params, string $mid = null, string $connection = null): PublicCollection
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_MULTIPLE_FETCH_REQUEST,
                           [
                               'params' => $params
                           ]
        );

        // if 'active' = true in $params, we ignore start_time and end_time ( if present in params )
        // and fetch ongoing downtimes
        if ((empty($params['active']) === false) and boolval($params['active']) === true)
        {
            unset($params['active']);
            unset($params['start_time']);
            unset($params['end_time']);

            return $this->fetchByCurrentTime($params);
        }
        // else if either start_time or end_time is present in query params, we call fetchDowntimeBetweenTimestamp
        if ((empty($params[Entity::START_TIME]) === false) or (empty($params[Entity::END_TIME]) === false))
        {
            unset($params['active']);

            return $this->fetchDowntimeBetweenTimestamp($params);
        }

        // finally if we don't have any of start_time, end_time and active in search query,
        // we call parent::fetch with the other params
        unset($params['active']);

        return parent::fetch($params);
    }
}
