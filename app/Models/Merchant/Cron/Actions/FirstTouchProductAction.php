<?php


namespace RZP\Models\Merchant\Cron\Actions;


use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;

class FirstTouchProductAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collector = $data["first_touch_product"];

        $lakeData =  $collector->getData();

        foreach ($lakeData as $data)
        {
            $merchantId = $data['merchant_id'];

            $segmentProperties = [];

            $segmentProperties['merchant_id'] = $merchantId;

            $segmentProperties['first_touch_product']  = $data['product'];

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties);
        }

        $this->app['segment-analytics']->buildRequestAndSend(true);

        return new ActionDto(Constants::SUCCESS);
    }
}
