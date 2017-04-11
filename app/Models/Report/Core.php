<?php

namespace RZP\Models\Report;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param   $input
     *          Expected params in $input are : start_time, end_time, entity
     * @return  Entity
     */
    public function create(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::REPORT_CREATE_REQUEST,
            $input
        );

        $report = (new Entity)->build($input);

        $report->merchant()->associate($merchant);

        $this->repo->saveOrFail($report);

        return $report;
    }

    /**
     * Checks if a report entity exists for give parameters
     * @param $from integer
     * @param $to   integer
     * @param $entity string
     * @param $input array containing day, month, year
     * @return $report Report\Entity
     */
    public function getReportEntity($from, $to, $entity, array $input)
    {
        $merchant = $this->merchant;

        $report = $this->repo->report->fetchReportEntity(
                                        $from, $to, $entity, $merchant->getId());

        if ($report === null)
        {
            $params = [
                Entity::DAY             => $input['day'],
                Entity::MONTH           => $input['month'],
                Entity::YEAR            => $input['year'],
                Entity::START_TIME      => $from,
                Entity::END_TIME        => $to,
                Entity::TYPE            => $entity,
                Entity::GENERATED_BY    => $this->getInternalUsernameOrEmail(),
            ];

            $report = $this->create($params, $merchant);
        }

        return $report;
    }
}
