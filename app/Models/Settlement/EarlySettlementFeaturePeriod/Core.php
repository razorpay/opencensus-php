<?php

namespace RZP\Models\Settlement\EarlySettlementFeaturePeriod;

use DateTime;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Pricing\Feature as PricingFeature;

class Core extends Base\Core
{
    public function createFeaturePeriod($input, $merchant)
    {
        $feature = $this->getFeature($input[Entity::FULL_ACCESS]);

        $disableDate = $this->parseDate($input[Entity::DISABLE_DATE]);

        $pricing = (new Ondemand\Core)->getOndemandPricingByFeature($merchant,
                                            PricingFeature::SETTLEMENT_ONDEMAND);


        if($pricing ===  null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                null,
                'settlement_ondemand pricing absent for merchant'
            );
        }

       [$percentRate, $fixedRate] =  $pricing->getRates();

        $scheduledTasks = (new ScheduleTask\Core)->getMerchantSettlementScheduleTasks($merchant, false);

        $data = [
            Entity::MERCHANT_ID              => $input[Entity::MERCHANT_ID],
            Entity::ENABLE_DATE              => Carbon::now()->getTimestamp(),
            Entity::DISABLE_DATE             => $disableDate,
            Entity::FEATURE                  => $feature,
            Entity::INITIAL_ONDEMAND_PRICING => $percentRate,
            Entity::INITIAL_SCHEDULE_ID      => $scheduledTasks[0][ScheduleTask\Entity::SCHEDULE_ID]
        ];

        $featurePeriod = (new Entity)->build($data);

        $featurePeriod->generateId();

        $this->repo->saveOrFail($featurePeriod);

        return $featurePeriod;
    }

    public function updateFeaturePeriod($featurePeriod, $input)
    {
        if(isset($input[Entity::DISABLE_DATE]) === true)
        {
            $featurePeriod->setDisableDate($this->parseDate($input[Entity::DISABLE_DATE]));
        }

        $this->repo->saveOrFail($featurePeriod);

        return $featurePeriod;
    }

    public function getFeaturePeriodByMerchantId($merchantId)
    {
        return (new Repository)->findFeaturePeriodByMerchantId($merchantId);
    }

    private function getFeature($fullAccess)
    {
        if ($fullAccess === 'yes')
        {
            return Constants::ES_AUTOMATIC;
        }
        else if ($fullAccess === 'no')
        {
            return Constants::ES_AUTOMATIC_RESTRICTED;
        }
    }

    private function parseDate($date)
    {
        $expectedFormat = 'd/m/Y';

        $d = DateTime::createFromFormat($expectedFormat,
                                        $date,
                                        new \DateTimeZone('Asia/Kolkata'))->setTime(1,0);

        return $d->getTimestamp();
    }
}
