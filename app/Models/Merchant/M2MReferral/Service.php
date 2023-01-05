<?php

namespace RZP\Models\Merchant\M2MReferral;

use Throwable;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Merchant\Account;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Feature\Core as FeatureCore;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Feature\Constants as FeatureConstants;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Store\Store;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Http\Requests\RewardValidationRequest;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\M2MReferral\FriendBuy\Constants as FB;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;
use RZP\Models\Merchant\M2MReferral\FriendBuy\FriendBuyService;
use RZP\Models\Coupon\Entity as CouponEntity;
use RZP\Models\Coupon\Core as CouponCore;
use RZP\Models\Coupon\Constants as CouponConstants;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $trace;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->trace = $this->app[MerchantConstants::TRACE];

        $this->mutex = $this->app[MerchantConstants::API_MUTEX];

        $this->entityRepo = $this->repo->m2m_referral;
    }


    /**
     * create entry in m2m_referral table with default status as Signup
     * send signup event to friendbuy
     * update the details in merchant config store that merchant has signed up and not done MTU
     *
     * @param String $userId
     *
     * @param array  $input
     *
     * @return void
     */
    public function sendSignUpEventIfApplicable(string $userId, array $input): bool
    {
        try
        {
            if ($this->core->isFriendBuyReferral($input) === true)
            {
                $user = $this->repo->user->findOrFailPublic($userId);

                $merchant = $user->getMerchantEntity();

                $input[Constants::FIRST_NAME] = $merchant->getName();

                $m2mReferral = $this->core->storeFriendBuyReferral($merchant, $input);

                if (empty($m2mReferral) === false)
                {
                    $this->postSignUpEvent($m2mReferral);
                }

                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::SEND_SIGNUP_EVENT_FAILED,
                                         [
                                             "userId" => $userId]);

        }

        return false;
    }

    protected function postSignUpEvent($m2mReferral)
    {
        if (empty($m2mReferral->getValueFromMetaData(FB::EMAIL)) === false)
        {
            $fbresponse = (new FriendBuyService())->postSignupEvent(new FriendBuy\SignUpEventRequest($m2mReferral));

            if (empty($fbresponse) === false and $fbresponse->isSuccess())
            {
                $input = [
                    Entity::STATUS   => STATUS::SIGNUP_EVENT_SENT,
                    Entity::METADATA => [
                        Constants::SIGN_UP_EVENT_ID => $fbresponse->getEventId()
                    ]
                ];

                $m2mReferral = $this->core->editM2MReferral($m2mReferral, $input);
            }
        }
        $data = [
            StoreConstants::NAMESPACE            => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::IS_SIGNED_UP_REFEREE => true
        ];

        (new StoreCore())->updateMerchantStore($m2mReferral->getMerchantId(), $data, StoreConstants::INTERNAL);

    }

    /**
     * update status to MTU in m2m_referral table
     * send mtu event to friendbuy
     * update the details in merchant config store that merchant has done MTU
     *
     * @param MerchantEntity $merchant
     *
     * @return void
     */
    public function sendPurchaseEventIfApplicable(MerchantEntity $merchant, $input)
    {
        try
        {
            $m2mReferral = $this->entityRepo->getReferralDetailsFromMerchantId($merchant->getId());

            if (empty($m2mReferral) === false)
            {
                $input[FriendBuy\Constants::EMAIL] = $merchant->getEmail();
                $input[Constants::FIRST_NAME]      = $merchant->getName();

                $input = [
                    Entity::STATUS   => STATUS::MTU,
                    Entity::METADATA => $input
                ];

                $m2mReferral = $this->core->editM2MReferral($m2mReferral, $input);

                $this->sendPurchaseEvent($m2mReferral);

                return $m2mReferral->getValueFromMetaData(M2MConstants::REFERRAL_CODE);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::SEND_MTU_EVENT_FAILED,
                                         [
                                             DEConstants::MERCHANT_ID => $merchant->getId()]);

        }

        return null;
    }

    public function getReferralCodeIfApplicable(MerchantEntity $merchant)
    {
        try
        {
            $m2mReferral = $this->entityRepo->getReferralDetailsFromMerchantId($merchant->getId());

            if (empty($m2mReferral) === false)
            {
                return $m2mReferral->getValueFromMetaData(M2MConstants::REFERRAL_CODE);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::GET_M2M_REFERRALS,
                                         [
                                             DEConstants::MERCHANT_ID => $merchant->getId()]);

        }

        return null;
    }
    public function isReferralMerchant(MerchantEntity $merchant)
    {
        try
        {
            $m2mReferral = $this->entityRepo->getReferralDetailsFromMerchantId($merchant->getId());

            if (empty($m2mReferral) === false)
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::GET_M2M_REFERRALS,
                                         [
                                             DEConstants::MERCHANT_ID => $merchant->getId()]);

        }

        return false;
    }
    protected function sendPurchaseEvent($m2mReferral)
    {

        $fbresponse = (new FriendBuyService())->postMtuEvent(new FriendBuy\MtuEventRequest($m2mReferral));

        if (empty($fbresponse) === false and $fbresponse->isSuccess())
        {
            $input = [
                Entity::STATUS   => STATUS::MTU_EVENT_SENT,
                Entity::METADATA => [
                    Constants::MTU_EVENT_ID => $fbresponse->getEventId()
                ]
            ];

            $m2mReferral = $this->core->editM2MReferral($m2mReferral, $input);

            $data = [
                StoreConstants::NAMESPACE            => StoreConfigKey::ONBOARDING_NAMESPACE,
                StoreConfigKey::IS_SIGNED_UP_REFEREE => false
            ];

            (new StoreCore())->updateMerchantStore($m2mReferral->getMerchantId(), $data, StoreConstants::INTERNAL);
        }
    }

    /**
     * update referrer_id in m2m_referral table and status
     *
     * @param RewardValidationRequest $request
     *
     * @return void
     * @throws Throwable
     */
    public function performRewardValidation(RewardValidationRequest $request)
    {
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->core()->setModeAndDefaultConnection(Mode::LIVE);

        $referrerData = null;
        //validations
        $merchant = $this->repo->merchant->findOrFailPublic($request->getMerchantId());

        $referrer = $this->repo->merchant->findOrFailPublic($request->getReferrerId());

        $m2mReferral = $this->entityRepo->getReferralDetailsFromMerchantId($request->getMerchantId());

        try
        {
            if (empty($m2mReferral) === true or
                ($m2mReferral->getRefereeStatus() <> Status::MTU_EVENT_SENT and $m2mReferral->getRefereeStatus() <> Status::MTU))
            {
                throw new GatewayErrorException(ErrorCode::GATEWAY_ERROR_REQUEST_ERROR);
            }

            if (empty($m2mReferral->getReferrerId()))
            {
                $input = [
                    Entity::REFERRER_ID => $request->getReferrerId()
                ];

                $m2mReferral = $this->core->editM2MReferral($m2mReferral, $input);
            }

            $referralCount = $this->entityRepo->getReferralCount($referrer->getId());

            $couponInput = [
                CouponEntity::CODE => CouponConstants::M2M_FRIEND,
            ];

            $coupon = (new CouponCore())->getCouponByCode($merchant, $couponInput);
            $promotion = $coupon->source;

            $eventAttributes = [
                Constants::REFERRAL_MID     => $merchant->getId(),
                Constants::REFERRAL_COUNT   => $referralCount,
                Constants::CREDITS_RECEIVED => $promotion->getCreditAmount(),
            ];

            $this->app['diag']->trackOnboardingEvent(EventCode::MERCHANT_REFERRAL, $referrer, null, $eventAttributes);

            $this->app['segment-analytics']->pushTrackEvent(
                $referrer, $eventAttributes, SegmentEvent::ADVOCATE_REFERRAL);

            $data         = [
                StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
            ];
            $referrerData = (new StoreCore())->fetchMerchantStore($referrer->getId(), $data, StoreConstants::INTERNAL);

            $this->repo->transactionOnLiveAndTest(function() use ($m2mReferral) {

                $this->rewardReferrer($m2mReferral);

                $this->rewardReferee($m2mReferral);
            });

            $this->app['segment-analytics']->buildRequestAndSend();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::FRIEND_BUY_REWARD_VALIDATION_FAILED,
                                         [
                                             'request' => $request]);

            if (empty($referrerData) === false)
            {
                $data = [
                    StoreConstants::NAMESPACE                    => StoreConfigKey::ONBOARDING_NAMESPACE,
                    StoreConfigKey::REFERRED_COUNT               => $referrerData[StoreConfigKey::REFERRED_COUNT]??0,
                    StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT => $referrerData[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]??0,
                    StoreConfigKey::REFEREE_NAME                 => $referrerData[StoreConfigKey::REFEREE_NAME]??[],
                    StoreConfigKey::REFEREE_ID                   => $referrerData[StoreConfigKey::REFEREE_ID]??[],
                    StoreConfigKey::REFERRAL_AMOUNT              => $referrerData[StoreConfigKey::REFERRAL_AMOUNT]??0,
                    StoreConfigKey::REFERRAL_AMOUNT_CURRENCY     => 'INR'
                ];

                (new StoreCore())->updateMerchantStore($referrer->getId(), $data, StoreConstants::INTERNAL);
            }
            throw $e;
        }
    }

    /**
     * update status in m2m_referral table
     * apply coupon to referee
     * update store config of referee
     *
     * @param Entity $m2mReferral
     *
     * @return void
     * @throws Throwable
     */
    private function rewardReferee(Entity $m2mReferral)
    {
        try
        {
            $merchant = $this->repo->merchant->findOrFail($m2mReferral->getRefereeId());

            $couponInput = [
                CouponEntity::CODE                  => CouponConstants::M2M_FRIEND,
                FriendBuy\Constants::RECIPIENT_TYPE => Constants::REFEREE];

            (new CouponCore)->apply($merchant, $couponInput,true);

            $input = [
                Entity::STATUS => STATUS::REWARDED
            ];

            $m2mReferral = $this->core->editM2MReferral($m2mReferral, $input);

            $coupon = (new CouponCore())->getCouponByCode($merchant, $couponInput);

            $promotion = $coupon->source;

            $referralAmount = $promotion->getCreditAmount();

            $data = [
                StoreConstants::NAMESPACE                    => StoreConfigKey::ONBOARDING_NAMESPACE,
                StoreConfigKey::REFEREE_SUCCESS_POPUP_COUNT => env(Constants::M2M_REFERRAL_SUCCESS_POPUP_COUNT),
                StoreConfigKey::IS_SIGNED_UP_REFEREE         => false,
                StoreConfigKey::REFERRAL_AMOUNT              => $referralAmount,
                StoreConfigKey::REFERRAL_AMOUNT_CURRENCY     => 'INR'
            ];

            (new StoreCore())->updateMerchantStore($merchant->getId(), $data, StoreConstants::INTERNAL);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::FRIEND_BUY_REFEREE_REWARD_FAILED,
                                         [
                                             'referral' => $m2mReferral]);
            throw $e;

        }
    }

    /**
     * update referrer_status in m2m_referral table
     * apply coupon to referrer
     * update store config of referrer
     *
     * @param Entity $m2mReferral
     *
     * @return void
     * @throws Throwable
     */
    private function rewardReferrer(Entity $m2mReferral)
    {
        try
        {
            $referrer = $this->repo->merchant->findOrFail($m2mReferral->getReferrerId());

            $referee = $this->repo->merchant->findOrFail($m2mReferral->getRefereeId());

            //no coupon credits after max referrals for referrer
            $referralCount = $this->entityRepo->getReferralCount($referrer->getId());

            if ($referralCount >= env(FeatureConstants::M2M_REFERRAL_MAX_REFERRED_COUNT_ALLOWED))
            {
                return;
            }

            $couponCode = Constants::REFERRAL_COUNT_COUPON_MAPPING[$referralCount];

            $couponInput = [
                CouponEntity::CODE                  => $couponCode,
                FriendBuy\Constants::RECIPIENT_TYPE => Constants::REFERRER];

            (new CouponCore)->apply($referrer, $couponInput,true);

            //save status
            $input = [
                Entity::REFERRER_STATUS => STATUS::REWARDED
            ];

            $m2mReferral = $this->core->editM2MReferral($m2mReferral, $input);

            //save referral details in merchant store config
            $data = [
                StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
            ];

            $coupon = (new CouponCore())->getCouponByCode($referrer, $couponInput);

            $promotion = $coupon->source;

            $data = (new StoreCore())->fetchMerchantStore($referrer->getId(), $data, StoreConstants::INTERNAL);

            //populate referee names list that made MTU events and advocate has not seen them yet
            $refereeName    = [$referee->getName()];
            $refereeId      = [$referee->getId()];
            $referralAmount = $promotion->getCreditAmount();

            if (empty($data) === false)
            {
                $existingRefereeName = $data[StoreConfigKey::REFEREE_NAME] ?? [];
                $existingRefereeId   = $data[StoreConfigKey::REFEREE_ID] ?? [];
                $existingAmount      = $data[StoreConfigKey::REFERRAL_AMOUNT] ?? 0;
                $popupCount          = $data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT] ?? 0;

                if ($popupCount == env(Constants::M2M_REFERRAL_SUCCESS_POPUP_COUNT))
                {
                    $refereeName    = array_merge($existingRefereeName, $refereeName);
                    $refereeId      = array_merge($existingRefereeId, $refereeId);
                    $referralAmount = $existingAmount + $referralAmount;
                }
            }

            $referralCount++;

            $data = [
                StoreConstants::NAMESPACE                    => StoreConfigKey::ONBOARDING_NAMESPACE,
                StoreConfigKey::REFERRED_COUNT               => $referralCount,
                StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT => env(Constants::M2M_REFERRAL_SUCCESS_POPUP_COUNT),
                StoreConfigKey::REFEREE_NAME                 => $refereeName,
                StoreConfigKey::REFEREE_ID                   => $refereeId,
                StoreConfigKey::REFERRAL_AMOUNT              => $referralAmount,
                StoreConfigKey::REFERRAL_AMOUNT_CURRENCY     => 'INR'
            ];

            (new StoreCore())->updateMerchantStore($referrer->getId(), $data, StoreConstants::INTERNAL);

            $eventAttributes = [
                Constants::REFERRAL_MID     => $referee->getId(),
                Constants::CREDITS_RECEIVED => $promotion->getCreditAmount(),
                Constants::REFERRAL_COUNT   => $referralCount
            ];

            $this->app['diag']->trackOnboardingEvent(EventCode::MERCHANT_REFERRAL_CREDITS, $referrer, null, $eventAttributes);

            $this->app['segment-analytics']->pushTrackEvent(
                $referrer, $eventAttributes, SegmentEvent::ADVOCATE_REFERRAL_CREDITS);

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::FRIEND_BUY_REFERRER_REWARD_FAILED,
                                         [
                                             'referral' => $m2mReferral]);
            throw $e;

        }
    }

    public function extractFriendBuyParams(&$data)
    {
        $m2mReferralInput = [];
        foreach (Constants::FRIEND_BUY_PARAMETERS as $param)
        {
            $m2mReferralInput[$param] = $data[$param] ?? '';
            unset($data[$param]);
        }
        $this->trace->info(TraceCode::FRIEND_BUY_SIGNUP, [
            'params' => $m2mReferralInput,
        ]);

        return $m2mReferralInput;
    }

    /**
     * update link in merchant store
     *
     * @throws Throwable
     */
    public function fetchPublicReferralDetails(): array
    {
        return [];

        /*
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->core()->setModeAndDefaultConnection(Mode::LIVE);

        $coupon = $this->repo->coupon->fetchByCodeWithRelations(CouponConstants::M2M_FRIEND, Account::SHARED_ACCOUNT);

        $promotion = $coupon->source;

        $referralAmount                                                            = $promotion->getCreditAmount();
        $response[Constants::REFEREE_DETAILS][Constants::REFERRAL_AMOUNT]          = $referralAmount;
        $response[Constants::REFEREE_DETAILS][Constants::REFERRAL_AMOUNT_CURRENCY] = Constants::INR;

        return $response;*/
    }

    /**
     * update link in merchant store
     *
     * @throws Throwable
     */
    public function fetchReferralDetails($merchant=null): array
    {
        $response[Constants::CAN_REFER] = false;

        //terminating m2m referral program
        return $response;

        /*
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->core()->setModeAndDefaultConnection(Mode::LIVE);

        $referralLink = null;

        $merchant = $merchant??$this->merchant;

        //when m2m_referral is enabled for the merchant get details for advocate/referrer
        $featureCore           = (new FeatureCore);
        $featureStatusResponse = $featureCore->getStatus(FeatureConstants::MERCHANT, $merchant->getMerchantId(), FeatureConstants::M2M_REFERRAL);

        $this->trace->info(TraceCode::M2M_REFERRAL_DETAILS, [
            'featureStatusResponse' => $featureStatusResponse,
        ]);

        if (empty($featureStatusResponse) === false and
            empty($featureStatusResponse[FeatureConstants::STATUS]) === false and
            $featureStatusResponse[FeatureConstants::STATUS] === true)
        {
            $data = [
                StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
            ];

            $data = (new StoreCore())->fetchMerchantStore($merchant->getId(), $data, StoreConstants::INTERNAL);

            $referralLink = $data[ConfigKey::REFERRAL_LINK];

            if (empty($referralLink))
            {
                $fbresponse = (new FriendBuyService())->generateReferralLink(new FriendBuy\ReferralLinkRequest($merchant->merchantDetail));

                if (empty($fbresponse) === false and $fbresponse->isSuccess())
                {

                    $data = [
                        StoreConstants::NAMESPACE     => StoreConfigKey::ONBOARDING_NAMESPACE,
                        StoreConfigKey::REFERRAL_LINK => $fbresponse->getReferralLink(),
                        StoreConfigKey::REFERRAL_CODE => $fbresponse->getReferralCode()
                    ];

                    (new StoreCore())->updateMerchantStore($merchant->getId(), $data, StoreConstants::INTERNAL);

                    $referralLink = $fbresponse->getReferralLink();
                }
            }

            $couponInput = [
                CouponEntity::CODE => CouponConstants::M2M_ADVOCATE1
            ];

            $coupon = (new CouponCore())->getCouponByCode($merchant, $couponInput);

            $promotion = $coupon->source;

            $referralAmount = $promotion->getCreditAmount();

            $response[Constants::CAN_REFER]                = true;
            $response[Constants::REFERRAL_LINK]            = $referralLink;
            $response[Constants::MAX_ALLOWED_REFERRALS]    = env(FeatureConstants::M2M_REFERRAL_MAX_REFERRED_COUNT_ALLOWED);
            $response[Constants::REFERRAL_AMOUNT]          = $referralAmount;
            $response[Constants::REFERRAL_AMOUNT_CURRENCY] = Constants::INR;
        }

        $m2mReferral = $this->entityRepo->getReferralDetailsFromMerchantId($merchant->getId());

        if (empty($m2mReferral) === false)
        {
            $couponInput = [
                CouponEntity::CODE => CouponConstants::M2M_FRIEND
            ];

            $coupon = (new CouponCore())->getCouponByCode($merchant, $couponInput);

            $this->trace->info(TraceCode::M2M_REFERRAL_DETAILS, [
                'coupon'      => $coupon,
                'm2mReferral' => $m2mReferral
            ]);

            $promotion = $coupon->source;

            $response[Constants::REFEREE_DETAILS][Constants::REFERRAL_AMOUNT] = $promotion->getCreditAmount();;
            $response[Constants::REFEREE_DETAILS][Constants::REFERRAL_AMOUNT_CURRENCY] = Constants::INR;
            $response[Constants::REFEREE_DETAILS][Entity::STATUS]                      = (($m2mReferral->getRefereeStatus() === Status::SIGNUP_EVENT_SENT) ? Status::SIGN_UP : $m2mReferral->getRefereeStatus());

        }

        return $response;*/
    }
}
