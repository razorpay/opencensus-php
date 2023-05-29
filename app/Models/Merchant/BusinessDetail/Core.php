<?php

namespace RZP\Models\Merchant\BusinessDetail;

use App;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Http\Controllers\MerchantOnboardingProxyController;

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

                    if (empty($input[Entity::WEBSITE_DETAILS]) === false)
                    {
                        $input[Entity::WEBSITE_DETAILS] = $this->mergeJson($businessDetail->getWebsiteDetails(), $input[Entity::WEBSITE_DETAILS]);
                    }

                    if (empty($input[Entity::APP_URLS]) === false)
                    {
                        $input[Entity::APP_URLS] = $this->mergeJson($businessDetail->getAppUrls(), $input[Entity::APP_URLS]);
                    }

                    if (empty($input[Entity::METADATA]) === false)
                    {
                        $input[Entity::METADATA] = $this->mergeJson($businessDetail->getMetadata(), $input[Entity::METADATA]);
                    }

                    if (isset($input[Constants::TXN_URL]) === true)
                    {
                        $appUrls = $this->updatedAppUrlsWithTxnPlaystoreUrls($input[Constants::TXN_URL], $businessDetail);

                        if ($appUrls !== null)
                        {
                            $input[Entity::APP_URLS] = $appUrls;
                        }

                        unset($input[Constants::TXN_URL]);
                    }

                    if (empty($input[Entity::PLUGIN_DETAILS]) === false)
                    {
                        $input[Entity::PLUGIN_DETAILS] = $this->setPluginDetails($businessDetail, $input);
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

                    $businessDetail = new Entity;

                    $businessDetail->generateId();

                    $input[MerchantConstants::MERCHANT_ID] = $merchantDetails->getMerchantId();

                    $this->trace->info(TraceCode::MERCHANT_CREATE_BUSINESS_DETAILS,
                                       [
                                           MerchantConstants::INPUT => $input
                                       ]);

                    if (empty($input[Entity::WEBSITE_DETAILS]) === false)
                    {
                        $input[Entity::WEBSITE_DETAILS] = $this->mergeJson(Entity::getDefaultWebsiteDetails(), $input[Entity::WEBSITE_DETAILS]);
                    }

                    if (empty($input[Entity::APP_URLS]) === false)
                    {
                        $input[Entity::APP_URLS] = $this->mergeJson(Entity::getDefaultAppUrls(), $input[Entity::APP_URLS]);
                    }

                    if (isset($input[Constants::TXN_URL]) === true)
                    {
                        $input[Entity::APP_URLS] = [
                            Constants::TXN_PLAYSTORE_URLS   => [$input[Constants::TXN_URL]],
                        ];

                        unset($input[Constants::TXN_URL]);
                    }

                    if (empty($input[Entity::PLUGIN_DETAILS]) === false)
                    {
                        $input[Entity::PLUGIN_DETAILS] = [$input[Entity::PLUGIN_DETAILS]];
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

    public function updateLeadScoreComponents(Detail\Entity $merchantDetails, $newLeadScore)
    {
        return $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $newLeadScore) {
            $mutexResource = self::BUSINESS_DETAIL_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();
            return $this->app[MerchantConstants::API_MUTEX]->acquireAndRelease
            ($mutexResource,
                function() use ($merchantDetails, $newLeadScore) {
                    $businessDetail = $merchantDetails->businessDetail;
                    if (empty($businessDetail) == false)
                    {
                        $oldLeadScore = $businessDetail->getLeadScoreComponents();
                        $leadScoreComponents = $this->mergeJson($oldLeadScore, $newLeadScore);
                        $businessDetail->setLeadScoreComponents($leadScoreComponents);
                        $this->repo->merchant_business_detail->saveOrFail($businessDetail);
                    }
                    else
                    {
                        $this->createBusinessDetail($merchantDetails, [Entity::LEAD_SCORE_COMPONENTS => $newLeadScore]);
                    }
                },
                MerchantConstants::MERCHANT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
                MerchantConstants::MERCHANT_MUTEX_RETRY_COUNT);
        });
    }

    public function setPluginDetails($businessDetail, $input)
    {
        //This will be an array returning value like this ->
        // [website : https://flipkart.com, merchant_selected_plugin : 'shopify', suggested_plugin :' WooCommerce']
        $existingPluginDetails = $businessDetail->getPluginDetails() ?? [];

        $existingWebsite = false;

        $pluginDetails = $input['plugin_details'];

        try
        {
            foreach ($existingPluginDetails as &$existingPluginDetail)
            {
                if ($existingPluginDetail[Constants::WEBSITE] === $pluginDetails[Constants::WEBSITE])
                {
                    $existingPluginDetail = array_merge($existingPluginDetail, $pluginDetails);
                    $existingWebsite                   = true;
                    break;
                }
            }

            if ($existingWebsite === false)
            {
                array_push($existingPluginDetails, $pluginDetails);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException($ex);
        }

        return $existingPluginDetails;
    }


    public function savePGOSDataToAPI(array $data)
    {
        $splitzResult = (new Detail\Core)->getSplitzResponse($data[Entity::MERCHANT_ID], 'pgos_migration_dual_writing_exp_id');

        if ($splitzResult === 'variables')
        {
            $merchant = $this->repo->merchant->find($data[Entity::MERCHANT_ID]);

            // dual write only for below merchants
            // merchants for whom pgos is serving onboarding requests
            // merchants who are not completely activated
            if ($merchant->getService() === MerchantConstants::PGOS and
                $merchant->merchantDetail->getActivationStatus()!=Detail\Status::ACTIVATED)
            {
                $businessDetail = (new Repository())->getBusinessDetailsForMerchantId($data["merchant_id"]);

                if (empty($businessDetail) === false)
                {
                    unset($data["merchant_id"]);

                    $businessDetail->edit($data);

                    $this->repo->saveOrFail($businessDetail);
                }
                else
                {
                    $businessDetail = new Entity;

                    $businessDetail->generateId();

                    $businessDetail->build($data);

                    $this->repo->merchant_business_detail->saveOrFail($businessDetail);

                }
            }
        }
    }
}
