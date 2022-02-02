<?php


namespace RZP\Models\Merchant\Cron\Actions;


use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;

class PushMetricsAction extends BaseAction
{

    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        foreach ($data as $key => $result)
        {
            $metricName = "";
            $gaugeValue = "";

            if(is_array($result) === true)
            {
                $metricName = $key . "_count";
                $gaugeValue = count($result);
            }
            elseif (is_int($result) === true)
            {
                $metricName = $key;
                $gaugeValue = $result;
            }

            if(empty($metricName) === false and empty($gaugeValue) === false)
            {
                $this->app['trace']->gauge($metricName, $gaugeValue, []);
            }
        }

        return new ActionDto(Constants::SUCCESS);
    }
}
