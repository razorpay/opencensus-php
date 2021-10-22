<?php

namespace RZP\Models\Merchant\AvgOrderValue;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;


class Core extends Base\Core
{
    const AVG_ORDER_VALUE_CREATE_MUTEX_PREFIX = 'api_avg_order_value_create_';

    public function createOrEditAvgOrderValue(Detail\Entity $merchantDetails, $input)
    {
        return $this->repo->transactionOnLiveAndTest(function () use ($merchantDetails, $input) {

            $avgOrderValue = $merchantDetails->avgOrderValue;

            if ($avgOrderValue === null)
            {
                $this->trace->info(
                    TraceCode::AVG_ORDER_VALUE_DOES_NOT_EXIST,
                    [
                        'merchant_id' => $merchantDetails->getMerchantId(),
                    ]
                );

                $input[Entity::MERCHANT_ID] = $merchantDetails->merchant->getId();

                $avgOrderValue = $this->createAvgOrderValue($merchantDetails, $input);

                $merchantDetails->setRelation(Detail\Entity::MERCHANT_AVG_ORDER_VALUE, $avgOrderValue);
            }

            else
            {
                $avgOrderValue->edit($input);

                $this->repo->merchant_avg_order_value->saveOrFail($avgOrderValue);
            }

            return $avgOrderValue;
        });
    }

    private function createAvgOrderValue($merchantDetails, $input)
    {
        $mutexResource = self::AVG_ORDER_VALUE_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function () use ($merchantDetails, $input) {

            $avgOrderValue = new Entity;

            $avgOrderValue->generateId();

            $this->trace->info(TraceCode::MERCHANT_CREATE_AVG_ORDER_VALUE_DETAILS, $input);

            $avgOrderValue->build($input);

            $this->repo->merchant_avg_order_value->saveOrFail($avgOrderValue);

            return $avgOrderValue;
        });
    }

}
