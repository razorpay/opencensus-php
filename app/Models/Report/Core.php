<?php

namespace RZP\Models\Report;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * builds the report entity, given params
     *
     * @param   $input    array
     *          Expected params in $input are :
     *          start_time, end_time, entity, day, month, year
     * @param   $merchant Merchant\Entity
     *
     * @return  $report Entity
     */
    public function buildEntity(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::REPORT_CREATE_REQUEST,
            $input
        );

        $params = [
            Entity::DAY             => $input['day'] ?? null,
            Entity::MONTH           => $input['month'],
            Entity::YEAR            => $input['year'],
            Entity::START_TIME      => $input['from'],
            Entity::END_TIME        => $input['to'],
            Entity::TYPE            => $input['entity'],
            Entity::GENERATED_BY    => $this->getInternalUsernameOrEmail(),
        ];

        $report = (new Entity)->build($params);

        $report->merchant()->associate($merchant);

        return $report;
    }
}
