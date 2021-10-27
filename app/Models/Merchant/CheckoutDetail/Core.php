<?php

namespace RZP\Models\Merchant\CheckoutDetail;

use Mail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;


class Core extends Base\Core
{
    const CHECKOUT_DETAIL_CREATE_MUTEX_PREFIX = 'api_checkout_detail_create_';

    public function createOrEditCheckoutDetail(Detail\Entity $merchantDetails, $input)
    {
        return $this->repo->transactionOnLiveAndTest(function () use ($merchantDetails, $input) {

            $merchantId = $merchantDetails->getMerchantId();

            $checkoutDetail = $this->repo->merchant_checkout_detail->getByMerchantId($merchantId);

            if ($checkoutDetail === null)
            {
                $input[Entity::MERCHANT_ID] = $merchantId;

                $checkoutDetail = $this->createCheckoutDetail($merchantDetails, $input);
            }

            else
            {
                $checkoutDetail->edit($input, 'edit');

                $this->repo->merchant_checkout_detail->saveOrFail($checkoutDetail);
            }

            return $checkoutDetail;
        });
    }

    private function createCheckoutDetail($merchantDetails, $input)
    {
        $mutexResource = self::CHECKOUT_DETAIL_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function () use ($merchantDetails, $input) {

            $checkoutDetail = new Entity;

            $checkoutDetail->generateId();

            $this->trace->info(TraceCode::MERCHANT_CREATE_CHECKOUT_DETAIL, $input);

            $checkoutDetail->build($input);

            $this->repo->merchant_checkout_detail->saveOrFail($checkoutDetail);

            return $checkoutDetail;
        });
    }

}
