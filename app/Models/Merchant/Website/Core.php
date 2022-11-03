<?php

namespace RZP\Models\Merchant\Website;

use Mail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstants;

class Core extends Base\Core
{
    const MERCHANT_WEBSITE_CREATE_MUTEX_PREFIX = 'api_merchant_website_create_';

    public function createOrEditWebsiteDetails(Detail\Entity $merchantDetails, $input)
    {

        return $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $input) {

            $websiteDetail = $merchantDetails->websiteDetail;

            if (empty($websiteDetail) === true)
            {
                $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchantDetails->getMerchantId());
            }

            if ($websiteDetail === null)
            {
                $this->trace->info(
                    TraceCode::MERCHANT_WEBSITE_DOES_NOT_EXIST,
                    [
                        'merchant_id' => $merchantDetails->getMerchantId()
                    ]
                );

                $input[Entity::MERCHANT_ID] = $merchantDetails->getMerchantId();

                $websiteDetail = $this->createWebsiteDetails($merchantDetails, $input);
            }

            else
            {
                if (isset($input[Entity::MERCHANT_WEBSITE_DETAILS]) === true)
                {
                    (new StoreCore())->deleteMerchantStore($websiteDetail->getMerchantId(),
                                                           ConfigKey::ONBOARDING_NAMESPACE,
                                                           [ConfigKey::POLICY_DATA],
                                                           StoreConstants::INTERNAL);
                }

                $input = $this->array_deep_merge_recursive($websiteDetail->toArray(), $input);

                unset($input[Entity::UPDATED_AT]);
                unset($input[Entity::AUDIT_ID]);
                unset($input[Entity::CREATED_AT]);
                unset($input[Entity::ID]);

                $this->trace->info(
                    TraceCode::MERCHANT_WEBSITE_CREATE_DETAILS,
                    [
                        'merchant_id' => $merchantDetails->getMerchantId(),
                        'input'       => $input
                    ]
                );

                $websiteDetail->edit($input, 'edit');

                $this->repo->merchant_website->saveOrFail($websiteDetail);
            }

            return $websiteDetail;
        });
    }

    private function createWebsiteDetails($merchantDetails, $input)
    {
        $mutexResource = self::MERCHANT_WEBSITE_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function() use ($merchantDetails, $input) {

            $websiteDetail = new Entity;

            $websiteDetail->generateId();

            $this->trace->info(TraceCode::MERCHANT_WEBSITE_CREATE_DETAILS, $input);

            $websiteDetail->build($input);

            $this->repo->merchant_website->saveOrFail($websiteDetail);

            return $websiteDetail;
        });
    }


    public function getWebsiteDetails($websiteDetail)
    {
        if ($websiteDetail !== null)
        {
            $merchant = $websiteDetail->merchantDetail->merchant;

            $websiteDetail = $websiteDetail->toArrayPublic();

            $websiteDetail['link'] = $this->getMerchantTncLink($merchant, $websiteDetail[Entity::ID]);

            unset($websiteDetail[Entity::ID]);
        }

        return $websiteDetail;
    }

    public function getMerchantTncLink(Merchant\Entity $merchant, string $tncId)
    {
        $org = $merchant->getOrgId() ?: $this->app['basicauth']->getOrgId();

        $host = null;

        if ($org === ORG_ENTITY::AXIS_ORG_ID)
        {
            $host = env('MERCHANT_TNC_SUBDOMAIN_AXIS');
        }

        if (empty($host) === true)
        {
            $host = env('MERCHANT_TNC_SUBDOMAIN');
        }

        return $host . '/' . $tncId;
    }

    public function array_deep_merge_recursive($paArray1, $paArray2)
    {
        if (!is_array($paArray1) or !is_array($paArray2))
        {
            return $paArray2;
        }
        foreach ($paArray2 as $sKey2 => $sValue2)
        {
            $paArray1[$sKey2] = $this->array_deep_merge_recursive(@$paArray1[$sKey2], $sValue2);
        }

        return $paArray1;
    }

    public function getUrlType($url)
    {
        //check For valid playStore Url
        if (str_starts_with($url, 'https://play.google.com/store/apps/details') === true)
        {
            return BusinessDetailConstants::PLAYSTORE_URL;
        }
        else
        {
            if (str_starts_with($url, 'https://apps.apple.com') === true)
            {
                return BusinessDetailConstants::APPSTORE_URL;
            }
            else
            {
                return Constants::WEBSITE;
            }
        }
    }
}
