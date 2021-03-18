<?php

namespace RZP\Models\Partner\Activation;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Partner\Activation;

class Core extends Base\Core
{
    const PARTNER_ACTIVATION_CREATE_MUTEX_PREFIX = 'api_partner_activation_create_';

    /**
     * Creates a partner activation entity for a partner merchant
     * case 1: partner merchant is activated and $considerActivatedMerchant = true
     *      - will be used currently when partner merchant becomes partner
     *      - create partner activation entity with merchant activation details
     * case 2: partner merchant is not activated and $considerActivatedMerchant = true
     *      - To restrict creation of partner activation entity for current partners who are not activated
     *      - Do not create partner activation entity
     * case 3: partner merchant is not activated and $considerActivatedMerchant = false
     *      - This will be used in future once partner kyc goes live and for back fill as well
     *      - create partner activation entity without any merchant activation(if not activated) details and fill partner activation as per partner KYC
     *      - create partner activation entity with merchant activation details if partner merchant is activated
     *
     * @param Merchant\Entity $merchant
     * @param bool            $considerActivatedMerchant
     *
     * @return Entity
     */
    public function createOrFetchPartnerActivationForMerchant(Merchant\Entity $merchant, bool $considerActivatedMerchant = true)
    {
        $partnerActivation = $merchant->partnerActivation;

        $merchantDetails = $merchant->merchantDetail;

        if ($merchant->isPartner() === true and empty($partnerActivation) === true and empty($merchantDetails) === false)
        {
            $partnerActivation = $this->createPartnerActivationForMerchant($merchant, $merchantDetails, $considerActivatedMerchant);

            $merchant->setRelation(Merchant\Entity::PARTNER_ACTIVATION, $partnerActivation);
        }

        return $partnerActivation;
    }

    /**
     * Creates a partner associated to merchant. We use this entity for processing partner activation
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     * @param bool            $considerActivatedMerchant
     *
     * @return Entity
     */
    protected function createPartnerActivationForMerchant(Merchant\Entity $merchant, Detail\Entity $merchantDetails, bool $considerActivatedMerchant)
    {
        $mutexResource = self::PARTNER_ACTIVATION_CREATE_MUTEX_PREFIX . $merchant->getId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function() use ($merchant, $merchantDetails, $considerActivatedMerchant) {

            return $this->createPartnerActivation($merchant, $merchantDetails, $considerActivatedMerchant);
        });
    }

    /**
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     * @param bool            $considerActivatedMerchant
     *
     * @return Activation\Entity
     */
    private function createPartnerActivation(Merchant\Entity $merchant, Detail\Entity $merchantDetails, bool $considerActivatedMerchant)
    {

        if($merchantDetails->getActivationStatus() !== Constants::ACTIVATED and $considerActivatedMerchant === true)
        {
            return null;
        }
        // this is required if another thread gets the lock immediately
        // after the previous thread releases the lock. So we refresh the relation and if found, we return

        $merchant->load(Merchant\Entity::PARTNER_ACTIVATION);

        $partnerActivation = $merchant->partnerActivation;

        if (empty($partnerActivation) === false)
        {
            return $partnerActivation;
        }

        $partnerActivation = new Activation\Entity;

        $partnerActivation->merchant()->associate($merchant);

        $input = $this->populateCommonActivationFields($merchant, $merchantDetails);

        $partnerActivation->build($input);

        $this->trace->info(TraceCode::PARTNER_ACTIVATION_CREATION_DETAILS, $partnerActivation->toArrayPublic());

        $this->repo->partner_activation->saveOrFail($partnerActivation);

        $this->trace->info(TraceCode::PARTNER_ACTIVATION_CREATION_SUCCESS, [
            'merchant_id' => $merchant->getId()
        ]);

        if($partnerActivation->getActivationStatus() === Constants::ACTIVATED)
        {
            $this->trace->info(TraceCode::PARTNER_AUTO_ACTIVATION_FROM_MERCHANT_SUCCESS, [
                'merchant_id' => $merchant->getId()
            ]);
        }

        return $partnerActivation;
    }

    /**
     * In case the merchant wants to convert to a partner after merchant is activated, mark partner activation as
     * activated
     * Reason: partner activation is a subset of merchant activation
     * 1. we will not consider workflow management for partner activation in this case.
     *
     * @param Merchant\Entity        $merchant
     * @param Detail\Entity          $merchantDetails
     *
     * @return array
     */
    public function populateCommonActivationFields(Merchant\Entity $merchant, Detail\Entity $merchantDetails): array
    {
        $input = [];

        if($merchantDetails->getActivationStatus() === Constants::ACTIVATED)
        {
            $this->populateCommonFields($input, $merchantDetails, Constants::COMMON_ACTIVATION_FIELDS_MERCHANT_DETAILS);

            $this->populateCommonFields($input, $merchant, Constants::COMMON_ACTIVATION_FIELDS_MERCHANT);

            $now = Carbon::now()->getTimestamp();

            $input[Entity::ACTIVATED_AT] = $now;
        }

        return $input;
    }

    private function populateCommonFields(array &$input, $sourceArr, $commonFields)
    {
        foreach ($commonFields as $key => $val)
        {
            if (empty($sourceArr[$val]) === false)
            {
                $input[$key] = $sourceArr[$val];
            }
        }
    }

    /**
     * This function auto activates a partner merchant when merchant is getting activated
     * Case 1: Partner activation entity has not been created (old partners or new partners who got created when merchant is not activated)
     *      - Create partner activation and auto activate partner activation.
     *      - This would become an invalid case after back fill job is completed
     *  TODO: Once partner KYC goes live and partner activation creation is independent of merchant activation,
     *        need to consider updating partner activation entity accordingly
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     */
    public function autoActivatePartnerIfApplicable(Merchant\Entity $merchant, Detail\Entity $merchantDetails)
    {
        try
        {
            if ($merchantDetails->getActivationStatus() === Constants::ACTIVATED and $merchant->isPartner())
            {
                $this->createOrFetchPartnerActivationForMerchant($merchant);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::PARTNER_AUTO_ACTIVATION_FROM_MERCHANT_FAILED, [
                'merchant_id' => $merchant->getId()
            ]);
        }
    }
}
