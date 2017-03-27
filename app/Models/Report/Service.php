<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $merchant = $this->merchant;

        $report = (new Core)->create($input, $merchant);

        return $report;
    }

    public function fetchMerchantReport(array $params)
    {
        $merchantId = $this->merchant->getId();

        $report = $this->repo->report
                            ->fetchReportEntity($params[Entity::START_TIME],
                                                $params[Entity::END_TIME],
                                                $params[Entity::ENTITY],
                                                $merchantId);

        return $report;
    }
}
