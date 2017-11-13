<?php

namespace RZP\Models\Merchant\Detail;

use DB;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Feature;

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

    public function getFeatureOnboardingRequestsByStatus(array $filters): Base\PublicCollection
    {
        if (isset($filters['status']) === true)
        {
            $status = $filters['status'];
        }

        if (isset($filters['offset']) === true)
        {
            $offset = $filters['offset'];
        }
        if (isset($filters['limit']) === true)
        {
            $limit = $filters['limit'];
        }

        if ((isset($filters['product']) === false) or ($filters['product'] === Feature\Constants::MARKETPLACE))
        {
            $marketplaceRecords = $this->newQueryWithConnection(Mode::LIVE)
                ->select(
                    Entity::MERCHANT_ID,
                    Entity::CONTACT_NAME,
                    DB::raw("'marketplace' as product"),
                    DB::raw(Entity::MARKETPLACE_ACTIVATION_STATUS . " as 'status'"))
                ->whereNotNull(Entity::MARKETPLACE_ACTIVATION_STATUS);

            if (isset($status) === true)
            {
                $marketplaceRecords->where(Entity::MARKETPLACE_ACTIVATION_STATUS, $status);
            }

            if (isset($filters['product']) === true)
            {
                $records = $marketplaceRecords;
            }
        }

        if ((isset($filters['product']) === false) or ($filters['product'] === Feature\Constants::VIRTUAL_ACCOUNTS))
        {
            $virtualAccountRecords = $this->newQueryWithConnection(Mode::LIVE)
                ->select(
                    Entity::MERCHANT_ID,
                    Entity::CONTACT_NAME,
                    DB::raw("'virtual_accounts' as product"),
                    DB::raw(Entity::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS . " as 'status'"))
                ->whereNotNull(Entity::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS);

            if (isset($status) === true)
            {
                $virtualAccountRecords->where(Entity::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS, $status);
            }

            if (isset($filters['product']) === true)
            {
                $records = $virtualAccountRecords;
            }
        }

        if ((isset($filters['product']) === false) or ($filters['product'] === Feature\Constants::SUBSCRIPTIONS))
        {
            $subscriptionRecords = $this->newQueryWithConnection(Mode::LIVE)
                ->select(
                    Entity::MERCHANT_ID,
                    Entity::CONTACT_NAME,
                    DB::raw("'subscriptions' as product"),
                    DB::raw(Entity::SUBSCRIPTIONS_ACTIVATION_STATUS . " as 'status'"))
                ->whereNotNull(Entity::SUBSCRIPTIONS_ACTIVATION_STATUS);

            if (isset($status) === true)
            {
                $subscriptionRecords->where(Entity::SUBSCRIPTIONS_ACTIVATION_STATUS, $status);
            }

            if (isset($filters['product']) === true)
            {
                $records = $subscriptionRecords;
            }
        }

        if (isset($filters['product']) === false)
        {
            $records = $marketplaceRecords->union($subscriptionRecords)
                                          ->union($virtualAccountRecords);
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
