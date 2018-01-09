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
     * @deprecated by getFeatureOnboardingRequests()
     *
     * @param string $status
     *
     * @return array
     */
    public function getFeatureOnboardingRequestsByStatus(string $status): array
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
                    ->get()
                    ->toArray();
    }

    /**
     * Returns the list of feature onboarding requests based on the params passed
     *
     * @param array $params
     *
     * @return array
     */
    public function getFeatureOnboardingRequests(array $params): array
    {
        //
        // [Sample]
        //
        // Union query that will run when these params are passed -
        // status=rejected, count=2, skip=0
        //
        //    (SELECT `merchant_id`,
        //            'marketplace'                 AS product,
        //            marketplace_activation_status AS 'status'
        //     FROM   `merchant_details`
        //     WHERE `marketplace_activation_status` = 'rejected')
        //    UNION
        //    (SELECT `merchant_id`,
        //            'subscriptions'                 AS product,
        //            subscriptions_activation_status AS 'status'
        //     FROM   `merchant_details`
        //     WHERE `subscriptions_activation_status` = 'rejected')
        //    UNION
        //    (SELECT `merchant_id`,
        //            'virtual_accounts'                 AS product,
        //            virtual_accounts_activation_status AS 'status'
        //     FROM   `merchant_details`
        //     WHERE `virtual_accounts_activation_status` = 'rejected')
        //    LIMIT 2
        //    OFFSET 0
        //

        //
        // Unset input keys that are not required ahead and are only used below
        // in the query building. They interfere with buildQueryWithParams() call.
        //
        $statusFilter  = array_pull($params, FeatureConstants::STATUS);
        $productFilter = array_pull($params, FeatureConstants::PRODUCT);

        $products = FeatureConstants::PRODUCT_FEATURES;

        $unionQueryElements = [];

        foreach ($products as $product)
        {
            //
            // Add productFeature results,
            // - If the product filter is not present, or,
            // - If the product filter is set to productFeature
            //
            if (($productFilter === null) or ($productFilter === $product))
            {
                // For eg: virtual_accounts_activation_status
                $productActivationStatus = $product . '_activation_status';

                //
                // Dynamically generate the queries that have to be run for each product
                // All such queries will then be UNIONed to run just one single query.
                //
                $unionQueryElement = $this->newQueryWithConnection(Mode::LIVE)
                                          ->select(
                                                Entity::MERCHANT_ID,
                                                DB::raw("'" . $product . "' as product"),
                                                DB::raw($productActivationStatus . " as 'status'"));

                // Filter with status
                if ($statusFilter === null)
                {
                    //
                    // If the merchant hasn't submitted on-boarding responses
                    // *_activation_status attribute is null.
                    //
                    $unionQueryElement->whereNotNull($productActivationStatus);
                }
                else
                {
                    $unionQueryElement->where($productActivationStatus, $statusFilter);
                }

                $unionQueryElements[] = $unionQueryElement;
            }
        }

        $records = [];

        if (count($unionQueryElements) > 0)
        {
            $query = null;

            // Generate a union query
            foreach ($unionQueryElements as $unionQueryElement)
            {
                $query = $query ? $query->union($unionQueryElement) : $unionQueryElement;
            }

            // Handles query params like skip, count, from and to
            $this->buildQueryWithParams($query, $params);

            $records = $query->get()->toArray();
        }

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
