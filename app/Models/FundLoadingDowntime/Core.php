<?php

namespace RZP\Models\FundLoadingDowntime;

use RZP\Trace\TraceCode;

class Core extends \RZP\Models\Base\Core
{
    public function create($input)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_CREATE_REQUEST, ['input' => $input]);
        $duplicateDowntime = $this->repo->fund_loading_downtimes->getSimilarDowntime($input);

        if(empty($duplicateDowntime) === false)
        {
            $this->trace->info(
                TraceCode::DUPLICATE_FUND_LOADING_DOWNTIME_WHILE_CREATE,
                [
                    Entity::ID => $duplicateDowntime->getPublicId(),
                ]
            );

            return $duplicateDowntime;
        }
        $downtime = (new Entity)->build($input);

        $this->repo->fund_loading_downtimes->saveOrFailEntity($downtime);

        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_CREATED, ['id' => $downtime->getId()]);

        return $downtime;
    }

    public function update($id, $input)
    {
        $downtimeId = Entity::verifyIdAndSilentlyStripSign($id);

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_UPDATE_REQUEST,
            [
                'input' => $input,
                'id'    => $downtimeId
            ]);

        $downtime = $this->repo->fund_loading_downtimes->findOrFailPublic($downtimeId);

        $duplicateDowntime = $this->repo->fund_loading_downtimes->getSimilarDowntime($input, $downtime);

        if(empty($duplicateDowntime) === false)
        {
            $this->trace->info(
                TraceCode::DUPLICATE_FUND_LOADING_DOWNTIME_WHILE_UPDATE,
                [
                    Entity::ID      => $duplicateDowntime->getPublicId(),
                    'update_params' => $input,
                ]
            );

            return $duplicateDowntime;
        }

        $downtime->edit($input,'update');

        $this->repo->fund_loading_downtimes->saveOrFailEntity($downtime);

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_UPDATED,
            [
                Entity::ID => $downtime->getPublicId(),
            ]
        );

        return $downtime;
    }

    public function listAllDowntimes($input)
    {
        return $this->repo->fund_loading_downtimes->fetch($input);
    }

    public function fetch($id)
    {
        $downtimeId = Entity::verifyIdAndSilentlyStripSign($id);

        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_FETCH_BY_ID, ['id' => $downtimeId]);

        return $this->repo->fund_loading_downtimes->findOrFailPublic($downtimeId);
    }

    public function listActiveDowntimes($input)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_FETCH_ACTIVE_REQUEST, ['parameters' => $input]);

        if (array_key_exists(Entity::START_TIME,$input) or
            array_key_exists(Entity::END_TIME,$input))
        {
            $downtime = $this->repo->fund_loading_downtimes->fetchDowntimeBetweenTimestamp($input);
        }
        else
        {
            $downtime = $this->repo->fund_loading_downtimes->fetchByCurrentTime($input);
        }

        return $downtime;
    }

    public function delete(Entity $downtime)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_DELETE_REQUEST, ['id' => $downtime->getId()]);

        $this->repo->fund_loading_downtimes->deleteOrFail($downtime);
    }
}
