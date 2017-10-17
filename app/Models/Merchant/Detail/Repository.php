<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Feature\Constants;

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

    public function updateFeatureActivationStatus(
        Merchant\Entity $merchant,
        string $featureName,
        string $status): bool
    {
        $attributeName = $featureName . '_activation_status';

        $attributeName = constant(Entity::class . '::' . strtoupper($attributeName));

        if (in_array($status, Constants::$onboardingStatuses, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ONBOARDING_STATUS_NOT_RECOGNIZED,
                $attributeName,
                [$featureName, $status]);
        }

        if (($status === Entity::APPROVED) and
            ($merchant->isFeatureEnabled($featureName) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_NOT_ASSIGNED,
                $attributeName,
                [$featureName, $status]);
        }

        return $this->newQueryWithConnection(Mode::LIVE)
                    ->where(Entity::MERCHANT_ID, $merchant->getId())
                    ->update([$attributeName => $status]);
    }
}
