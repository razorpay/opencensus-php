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

                    if (isset($input[Constants::TXN_URL]) === true)
                    {
                        $appUrls = $this->updatedAppUrlsWithTxnPlaystoreUrls($input[Constants::TXN_URL], $businessDetail);

                        if ($appUrls !== null)
                        {
                            $input[BusinessDetailEntity::APP_URLS] = $appUrls;
                        }

                        unset($input[Constants::TXN_URL]);
                    }

                    if (empty($input[BusinessDetailEntity::PLUGIN_DETAILS]) === false)
                    {
                        $existingPluginDetails = $businessDetail->getPluginDetails() ?? [];

                        $input[BusinessDetailEntity::PLUGIN_DETAILS] = array_merge(
                            $input[BusinessDetailEntity::PLUGIN_DETAILS],
                            $existingPluginDetails
                        );
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

                    if (isset($input[Constants::TXN_URL]) === true)
                    {
                        $input[BusinessDetailEntity::APP_URLS] = [
                            Constants::TXN_PLAYSTORE_URLS   => [$input[Constants::TXN_URL]],
                        ];

                        unset($input[Constants::TXN_URL]);
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

    // moves the current url to most recently used if the current url exists in the list
    protected function moveToMRU(& $txnUrls, $currentUrl)
    {
        $currentUrlPos = array_search($currentUrl, $txnUrls);

        if ($currentUrlPos !== false)
        {
            array_splice($txnUrls, $currentUrlPos, 1);
        }

        array_push($txnUrls, $currentUrl);
    }

    protected function updatedAppUrlsWithTxnPlaystoreUrls($currentUrl, $businessDetail)
    {
        $appUrls = $businessDetail->getAppUrls();

        if (isset($appUrls) === false
            or isset($appUrls[Constants::PLAYSTORE_URL]) === true)
        {
            return null;
        }

        $txnUrls = [];

        if (isset($appUrls[Constants::TXN_PLAYSTORE_URLS]) === true)
        {
            $txnUrls = $appUrls[Constants::TXN_PLAYSTORE_URLS];
        }

        $this->moveToMRU($txnUrls, $currentUrl);

        if (sizeof($txnUrls) > Constants::TXN_PLAYSTORE_URL_COUNT_LIMIT)
        {
            // remove the least recent url
            array_shift($txnUrls);
        }

        $appUrls[Constants::TXN_PLAYSTORE_URLS] = $txnUrls;

        return $appUrls;
    }
}
