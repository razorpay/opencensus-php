<?php

namespace RZP\Models\Merchant\M2MReferral;


use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\M2MReferral\Entity as M2MReferralEntity;
use RZP\Models\Merchant\Constants as MerchantConstants;

class Core extends Base\Core
{
    const M2M_REFERRAL_CREATE_MUTEX_PREFIX = 'api_m2m_referral_create_';

    /**
     * @param Entity $referralEntity
     * @param array  $input
     *
     * @return mixed
     * @throws \Throwable
     */
    public function editM2MReferral(M2MReferralEntity $referralEntity, array $input)
    {
        $this->trace->info(TraceCode::EDIT_M2M_REFERRALS,
                           [
                               MerchantConstants::MERCHANT_ID => $referralEntity->getMerchantId(),
                               MerchantConstants::INPUT       => $input
                           ]);

        return $this->repo->transactionOnLiveAndTest(function() use ($referralEntity, $input) {

            $mutexResource = self::M2M_REFERRAL_CREATE_MUTEX_PREFIX . $referralEntity->getMerchantId();

            return $this->app[MerchantConstants::API_MUTEX]->acquireAndRelease
            ($mutexResource,

                function() use ($referralEntity, $input) {

                    $this->validateEditRequest($referralEntity, $input);

                    if (empty($input[M2MReferralEntity::METADATA]) === false)
                    {
                        $input[M2MReferralEntity::METADATA] = $this->mergeJson($referralEntity->getMetadata(), $input[M2MReferralEntity::METADATA]);
                    }

                    $referralEntity->edit($input, MerchantConstants::EDIT);

                    $this->repo->m2m_referral->saveOrFail($referralEntity);

                    return $referralEntity;
                },
             MerchantConstants::MERCHANT_MUTEX_LOCK_TIMEOUT,
             ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
             MerchantConstants::MERCHANT_MUTEX_RETRY_COUNT);

        });
    }

    protected function validateEditRequest(Entity $referralEntity, $input)
    {
        if (array_key_exists(Entity::STATUS, $input))
        {
            if (Status::isValidStatusTransition($referralEntity->getRefereeStatus(), $input[Entity::STATUS]) === false)
            {
                throw new BadRequestValidationFailureException(
                    'Not a valid status ' . $input,
                    Entity::STATUS,
                    [
                        Entity::STATUS => $input[Entity::STATUS]
                    ]);
            }
        }
    }

    /**
     * @param MerchantEntity $merchant
     * @param                $input
     *
     * @return mixed
     * @throws \Throwable
     */
    public function createM2MReferral(MerchantEntity $merchant, $input)
    {
        $this->trace->info(TraceCode::CREATE_M2M_REFERRALS,
                           [
                               MerchantConstants::MERCHANT_ID => $merchant->getMerchantId(),
                               MerchantConstants::INPUT       => $input
                           ]);

        return $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input) {

            $mutexResource = self::M2M_REFERRAL_CREATE_MUTEX_PREFIX . $merchant->getMerchantId();

            return $this->app[MerchantConstants::API_MUTEX]->acquireAndRelease
            ($mutexResource,

                function() use ($merchant, $input) {

                    $m2mReferral = new M2MReferralEntity;

                    $m2mReferral->generateId();

                    $input[MerchantConstants::MERCHANT_ID] = $merchant->getMerchantId();

                    $m2mReferral->build($input);

                    $this->repo->m2m_referral->saveOrFail($m2mReferral);

                    return $m2mReferral;
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

    public function isFriendBuyReferral($params)
    {
        return empty($params[Constants::UTM_SOURCE]) == false and $params[Constants::UTM_SOURCE] === Constants::FRIEND_BUY;
    }


    /**
     * @param MerchantEntity $merchant
     *
     * @param                $input
     *
     * @return Entity|null
     * @throws \Throwable
     */

    public function storeFriendBuyReferral(MerchantEntity $merchant, array $input): ?M2MReferralEntity
    {
        try
        {

            $input[FriendBuy\Constants::EMAIL] = $merchant->getEmail();

            $this->trace->info(TraceCode::UTM_PARAMS, [
                'merchant'   => $merchant->getId(),
                'utm_params' => $input,
                'method'     => 'storeIfFriendBuyReferral'
            ]);

            $input = [
                M2MReferralEntity::STATUS   => Status::SIGN_UP,
                M2MReferralEntity::METADATA => $input
            ];

            return $this->createM2MReferral($merchant, $input);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::STORE_FRIEND_BUY_REFERRAL_DETAILS_FAILED,
                                         [
                                             DEConstants::MERCHANT_ID => $merchant->getId()]);
        }

        return null;
    }
}
