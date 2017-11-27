<?php

namespace RZP\Models\Merchant\Detail;

use DB;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Feature\Constants as FeatureConstants;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive
    {
        saveOrFail as saveOrFailTestAndLive;
    }

    protected $entity = 'merchant_detail';

    /**
     * @override
     *
     * Once merchant details is saved, we need to trigger es sync for corresponding
     * merchant. This handling is required as merchant detail relation is part
     * of merchant index content.
     *
     * @param Detail\Entity $merchantDetail
     * @param array         $options
     */
    public function saveOrFail($merchantDetail, array $options = [])
    {
        $this->saveOrFailTestAndLive($merchantDetail, $options);

        $merchant = $merchantDetail->merchant;

        $this->repo->merchant->syncToEsLiveAndTest($merchant, Merchant\EsRepository::UPDATE);
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }

    public function getMerchantDetailsToBeMigrated($count = 1000, $skip = 0)
    {
        return $this->newQuery()
                    ->select(
                        Entity::MERCHANT_ID,
                        Entity::BUSINESS_PROOF_URL,
                        Entity::BUSINESS_OPERATION_PROOF_URL,
                        Entity::BUSINESS_PAN_URL,
                        Entity::ADDRESS_PROOF_URL,
                        Entity::PROMOTER_PROOF_URL,
                        Entity::PROMOTER_PAN_URL,
                        Entity::PROMOTER_ADDRESS_URL)
                    ->whereRaw(
                        'length(business_proof_url) > 19 or
                        length(business_operation_proof_url) > 19 or
                        length(business_pan_url) > 19 or
                        length(address_proof_url) > 19 or
                        length(promoter_proof_url) > 19 or
                        length(promoter_pan_url) > 19 or
                        length(promoter_address_url) > 19')
                    ->orderBy(Entity::MERCHANT_ID)
                    ->skip($skip)
                    ->take($count)
                    ->get();
    }

    /**
     * This function will be deprecated. Use the getFeatureOnboardingRequests function instead.
     * Currently, maintained for Backward Compatibility.
     *
     * @param string $status
     *
     * @return Base\PublicCollection
     */
    public function getFeatureOnboardingRequestsByStatus(string $status): Base\PublicCollection
    {
        return $this->newQueryWithConnection(Mode::LIVE)
                    ->select(
                        Entity::MERCHANT_ID,
                        Entity::CONTACT_NAME,
                        Entity::MARKETPLACE_ACTIVATION_STATUS,
                        Entity::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS,
                        Entity::SUBSCRIPTIONS_ACTIVATION_STATUS)
                    ->where(Entity::MARKETPLACE_ACTIVATION_STATUS, $status)
                    ->orWhere(Entity::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS, $status)
                    ->orWhere(Entity::SUBSCRIPTIONS_ACTIVATION_STATUS, $status)
                    ->get();
    }

    public function getFeatureOnboardingRequests(array $filters): Base\PublicCollection
    {
        if (isset($filters['status']) === true)
        {
            $status = $filters['status'];
        }

        if (isset($filters['product']) === true)
        {
            $productFilter = $filters['product'];
        }

        $productFeatures = FeatureConstants::PRODUCT_FEATURES;

        foreach ($productFeatures as $productFeature)
        {
            //
            // Add productFeature results,
            // - If the product filter is not present, or,
            // - If the product filter is set to productFeature
            //
            if ((isset($productFilter) === false) or ($productFilter === $productFeature))
            {
                // virtual_accounts_activation_status
                $productFeatureActivationStatus = $productFeature . '_activation_status';

                // virtualAccountsRecords
                $productRecordsName = camel_case($productFeature . '_records');

                // Defines $marketplaceRecords, $virtualAccountsRecords, $subscriptionsRecords
                ${$productRecordsName} = $this->newQueryWithConnection(Mode::LIVE)
                                              ->select(
                                                    Entity::MERCHANT_ID,
                                                    DB::raw("'" . $productFeature . "' as product"),
                                                    DB::raw($productFeatureActivationStatus . " as 'status'"))
                                              ->whereNotNull($productFeatureActivationStatus);

                // Filter with status
                if (isset($status) === true)
                {
                    ${$productRecordsName}->where($productFeatureActivationStatus, $status);
                }
            }
        }

        if (isset($productFilter) === true)
        {
            $productRecords = camel_case($productFilter . '_records');

            // $marketplaceRecords, $virtualAccountsRecords, $subscriptionsRecords
            $records = $$productRecords;
        }
        else
        {
            // If the product filter is not present, all the 3 variables will be dynamically defined above
            $records = $marketplaceRecords->union($virtualAccountsRecords)
                                          ->union($subscriptionsRecords);
        }

        if (isset($filters['offset']) === true)
        {
            $records = $records->skip($filters['offset']);
        }

        if (isset($filters['limit']) === true)
        {
            $records = $records->limit($filters['limit']);
        }

        $records = $records->get();

        return $records;
    }

    public function updateFeatureActivationStatus(
        Merchant\Entity $merchant,
        string $featureName,
        string $status)
    {
        $merchantDetail = $merchant->merchantDetail;

        $setFeatureActivationStatus = camel_case('set_' . $featureName . '_activation_status');

        $merchantDetail->$setFeatureActivationStatus($status);

        $this->saveOrFailTestAndLive($merchantDetail);
    }

    public function getFeatureActivationStatus(
        Merchant\Entity $merchant,
        string $featureName)
    {
        $merchantDetail = $merchant->merchantDetail;

        $getFeatureActivationStatus = camel_case('get_' . $featureName . '_activation_status');

        $status = $merchantDetail->$getFeatureActivationStatus();

        return $status;
    }
}
