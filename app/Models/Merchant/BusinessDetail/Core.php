<?php

namespace RZP\Models\Merchant\BusinessDetail;


use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
use RZP\Models\Merchant\Constants as MerchantConstants;

class Core extends Base\Core
{
    const BUSINESS_DETAIL_CREATE_MUTEX_PREFIX = 'api_business_detail_create_';

    public function editBusinessDetail(Detail\Entity $merchantDetails, array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_EDIT_BUSINESS_DETAILS,
                           [
                               MerchantConstants::MERCHANT_ID => $merchantDetails->getMerchantId(),
                               MerchantConstants::INPUT       => $input
                           ]);

        return $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $input) {

            $mutexResource = self::BUSINESS_DETAIL_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

            return $this->app[MerchantConstants::API_MUTEX]->acquireAndRelease
            ($mutexResource,

                function() use ($merchantDetails, $input) {
                    $businessDetail = $merchantDetails->businessDetail;

                    if (empty($input[BusinessDetailEntity::WEBSITE_DETAILS]) === false)
                    {
                        $input[BusinessDetailEntity::WEBSITE_DETAILS] = $this->mergeJson($businessDetail->getWebsiteDetails(), $input[BusinessDetailEntity::WEBSITE_DETAILS]);
                    }

                    if (empty($input[BusinessDetailEntity::APP_URLS]) === false)
                    {
                        $input[BusinessDetailEntity::APP_URLS] = $this->mergeJson($businessDetail->getAppUrls(), $input[BusinessDetailEntity::APP_URLS]);
                    }

                    $businessDetail->edit($input, MerchantConstants::EDIT);

                    $this->repo->merchant_business_detail->saveOrFail($businessDetail);

                    $merchantDetails->setRelation(Detail\Entity::MERCHANT_BUSINESS_DETAIL, $businessDetail);

                    return $businessDetail;
                },
             MerchantConstants::MERCHANT_MUTEX_LOCK_TIMEOUT,
             ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
             MerchantConstants::MERCHANT_MUTEX_RETRY_COUNT);

        });
    }

    /**
     * @param $merchantDetails
     * @param $input
     *
     * @return mixed
     * @throws \RZP\Exception\LogicException|\Throwable
     */
    public function createBusinessDetail($merchantDetails, $input)
    {
        $this->trace->info(TraceCode::MERCHANT_CREATE_BUSINESS_DETAILS,
                           [
                               MerchantConstants::MERCHANT_ID => $merchantDetails->getMerchantId(),
                               MerchantConstants::INPUT       => $input
                           ]);

        return $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $input) {

            $mutexResource = self::BUSINESS_DETAIL_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

            return $this->app[MerchantConstants::API_MUTEX]->acquireAndRelease
            ($mutexResource,

                function() use ($merchantDetails, $input) {

                    $businessDetail = new BusinessDetailEntity;

                    $businessDetail->generateId();

                    $input[MerchantConstants::MERCHANT_ID] = $merchantDetails->getMerchantId();

                    $this->trace->info(TraceCode::MERCHANT_CREATE_BUSINESS_DETAILS,
                                       [
                                           MerchantConstants::INPUT => $input
                                       ]);

                    if (empty($input[BusinessDetailEntity::WEBSITE_DETAILS]) === false)
                    {
                        $input[BusinessDetailEntity::WEBSITE_DETAILS] = $this->mergeJson(BusinessDetailEntity::getDefaultWebsiteDetails(), $input[BusinessDetailEntity::WEBSITE_DETAILS]);
                    }

                    if (empty($input[BusinessDetailEntity::APP_URLS]) === false)
                    {
                        $input[BusinessDetailEntity::APP_URLS] = $this->mergeJson(BusinessDetailEntity::getDefaultAppUrls(), $input[BusinessDetailEntity::APP_URLS]);
                    }

                    $businessDetail->build($input);

                    $this->repo->merchant_business_detail->saveOrFail($businessDetail);

                    $merchantDetails->setRelation(Detail\Entity::MERCHANT_BUSINESS_DETAIL, $businessDetail);

                    return $businessDetail;
                },
             MerchantConstants::MERCHANT_MUTEX_LOCK_TIMEOUT,
             ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
             MerchantConstants::MERCHANT_MUTEX_RETRY_COUNT);
        });
    }

    protected function mergeJson($existingDetails, $newDetails)
    {
        if (empty($newDetails) === false)
        {
            foreach ($newDetails as $key => $value)
            {
                $existingDetails[$key] = $value;
            }
        }

        return $existingDetails;
    }
}
