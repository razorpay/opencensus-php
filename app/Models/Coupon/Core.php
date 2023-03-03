<?php

namespace RZP\Models\Coupon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Promotion;
use RZP\Models\User;
use RZP\Constants\Product;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Models\Merchant\Promotion as MerchantPromotion;
use RZP\Models\Coupon\Entity as Coupon;

class Core extends Base\Core
{
    const SUCCESS_MESSAGE = 'Coupon Applied Successfully';

    public function create(array $input): Entity
    {
        $coupon = (new Entity)->build($input);

        $entityType = $input[Entity::ENTITY_TYPE];

        $entity = $this->repo->$entityType->findByPublicId($input[Entity::ENTITY_ID]);

        if (empty($input[Coupon::ALERTS]) === false)
        {
            $input[Coupon::ALERTS] = $this->mergeJson(Coupon::getDefaultAlerts(), $input[Coupon::ALERTS]);
        }

        if (empty($input[Entity::MERCHANT_ID]) === true)
        {
            $merchant = $this->repo->merchant->getSharedAccount();
        }
        else
        {
            $merchant = $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);
        }

        $couponCode = $input[Entity::CODE];

        $couponExists = $this->repo->coupon->fetchByCodeWithRelations($couponCode, $merchant->id);

        if ($couponExists !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_ALREADY_EXISTS);
        }

        $coupon->source()->associate($entity);

        $coupon->merchant()->associate($merchant);

        $this->repo->saveOrFail($coupon);

        return $coupon;
    }

    /**
     *
     * Updating start_at and end_at
     * @param Entity $coupon
     * @param array $input
     * @return Entity
     */
    public function update(Entity $coupon, array $input): Entity
    {
        $coupon->edit($input);

        $this->repo->saveOrFail($coupon);

        return $coupon;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array $input
     * @return mixed|Entity
     * @throws Exception\BadRequestException
     */
    public function validateAndGetDetails(Merchant\Entity $merchant, array $input, bool $isSystemCall): Entity
    {
        $coupon = $this->getCouponByCode($merchant, $input);

        $couponCode = $input[Entity::CODE];
        $config = Constants::COUPON_CONFIG['default'];

        if (isset(Constants::COUPON_CONFIG[$couponCode]) === true)
        {
            $config = Constants::COUPON_CONFIG[$couponCode];
        }
        //    Grow-1299 make internal coupons non redeemable from controllers
        if ($coupon->isInternal()!=null and $coupon->isInternal()===true and !$isSystemCall)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE,
                [],
                $input);
        }

        $this->validateMerchantPromotion($merchant, $coupon);

        return $coupon;
    }

    /**
     *  Validating and Checking whether coupon and merchant is valid
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return mixed
     * @throws Exception\BadRequestException
     */
    public function getCouponByCode(Merchant\Entity $merchant, array $input): Entity
    {
        $coupon = $this->repo->coupon->fetchByCodeWithRelations($input[Entity::CODE], $merchant->getId());

        if ($coupon === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE,
                null,
                $input);
        }

        return $coupon;
    }

    public function apply(Merchant\Entity $merchant, array $input, bool $isSystemCall = false): array
    {

        $couponCode = $input[Entity::CODE] ?? '';

        $input[Entity::COUPON_CODE] = $couponCode;

        if (isset(Constants::COUPON_CONFIG[$couponCode]) === true)
        {
            $config = Constants::COUPON_CONFIG[$couponCode];

            $exptName = $config[Constants::EXPERIMENT_NAME] ?? null;

            if (empty($exptName) === false and
                (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(), $exptName) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE,
                    null,
                    $input);
            }
        }
        else
        {
            $config = Constants::COUPON_CONFIG['default'];
        }

        try
        {

            $coupon = $this->validateAndGetDetails($merchant, $input,$isSystemCall);
            $this->applyMerchantPromotion($merchant, $coupon);
        }
        catch (\Throwable $exception)
        {
            $this->app['diag']->trackOnboardingEvent($config[Constants::FAILED_EVENT_CODE], $merchant,
                $exception, $input);
            throw $exception;
        }

        $this->app['diag']->trackOnboardingEvent($config[Constants::SUCCESS_EVENT_CODE], $merchant, null, $input);

        $hubspotInput = [Entity::COUPON_CODE => $couponCode];

        if ($couponCode !== Constants::MTU_COUPON)
        {
            $this->app->hubspot->trackPreSignupEvent($hubspotInput, $merchant);
        }

        $this->app->salesforce->sendCouponInfo($merchant, $hubspotInput);

        $merchantBalance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(),
            Merchant\Balance\Type::PRIMARY);

        if (empty($merchantBalance) === false)
        {
            $segmentProperties = [
                Merchant\Service::SEGMENT_FREE_CREDITS_AVAILABLE => $merchantBalance->getAmountCredits()
            ];

            $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties);
        }

        return [
            'message' => self::SUCCESS_MESSAGE
        ];
    }

    public function applyCouponCode(Merchant\Entity $merchant, Entity $coupon, bool $isSystemCall = false)
    {
        $promotion = $coupon->source;

        $success = true;

        try
        {
            $couponCode = $coupon-> getCode();

            //    Grow-1299 make internal coupons non redeemable from controllers
            if ($coupon->isInternal()!=null and $coupon->isInternal()===true and !$isSystemCall)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE,
                    null,
                    $coupon);
            }
            $this->repo->transaction(function() use ($merchant, $promotion, $coupon)
            {
                $this->mapPromotionPartnerIfApplicable($merchant, $promotion);

                $this->createAndActivateMerchantPromotion($merchant, $promotion);

                $coupon->incrementUsedCount();

                $this->repo->saveOrFail($coupon);

                $this->trace->info(TraceCode::MERCHANT_PROMOTION_CREATED);
            });
        }
        catch (\Throwable $exception)
        {
            $success = false;

            $this->trace->info(
                TraceCode::AMOUNT_CREDITS_COUPON_APPLY_EXCEPTION,
                [
                    'message'             => 'exception',
                    'error'               => $exception->getMessage(),
                ]);
        }

        if($success === true)
        {
            $hubspotInput = [Entity::COUPON_CODE => $coupon->getCode()];

            $this->app->salesforce->sendCouponInfo($merchant, $hubspotInput);

            $primaryBalance = $merchant->primaryBalance;

            if (empty($primaryBalance) === false)
            {
                $segmentProperties = [
                    Merchant\Service::SEGMENT_FREE_CREDITS_AVAILABLE => $primaryBalance->reload()->getAmountCredits()
                ];

                $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties);
            }
        }
    }

    /**
     * Check if the coupon has been used by the merchant
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $coupon
     *
     * @throws Exception\BadRequestException
     */
    protected function validateMerchantPromotion(Merchant\Entity $merchant, Entity $coupon)
    {
        $coupon->getValidator()->validateEntityType();

        $promotion = $coupon->source;

        //Check coupon belongs to right product
        $promotionProduct = optional($promotion)->getProduct() ?? Product::PRIMARY;

        $requestProduct = $this->app->basicauth->getRequestOriginProduct();

        if ($promotionProduct !== $requestProduct)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE);
        }

        $merchantPromotion = $this->repo
            ->merchant_promotion
            ->findByMerchantAndPromotionId(
                $merchant->getId(),
                $promotion->getId()
            );

        if ($merchantPromotion !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_ALREADY_USED);
        }

        $coupon->getValidator()->validateApplyCoupon($merchant);
    }

    /**
     * Apply a promotion (identified by the coupon) to a merchant.
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $coupon
     */
    protected function applyMerchantPromotion(Merchant\Entity $merchant, Entity $coupon)
    {
        $promotion = $coupon->source;

        //
        // This need to be in transaction, as credits are applied here,
        // And Schedule for next run is also created via Merchant Promotion
        // Coupon Usage is also updated
        // If either of these fail data need to be rolled back
        //
        $this->repo->transaction(function() use ($merchant, $promotion, $coupon)
        {
            if ($this->shouldApplyPromotionPricing($coupon) === true)
            {
                $this->applyPromotionPricing($merchant, $promotion);
            }
            else
            {
                $this->trace->info(TraceCode::MERCHANT_PROMOTION_PRICING_CHANGE_SKIPPED, [
                    'merchant_id'   => $merchant->getId(),
                    'coupon_code'   => $coupon->getCode()
                ]);
            }

            $this->mapPromotionPartnerIfApplicable($merchant, $promotion);

            $this->createAndActivateMerchantPromotion($merchant, $promotion);

            $coupon->incrementUsedCount();

            $this->repo->saveOrFail($coupon);

            $this->trace->info(TraceCode::MERCHANT_PROMOTION_CREATED);
        });
    }

    public function isCouponApplied(Merchant\Entity $merchant, $couponCode): bool
    {
        try
        {
            $coupon = $this->getCouponByCode($merchant, [Entity::CODE => $couponCode]);
        }
        catch (\Exception $ex)
        {
            return false;
        }

        $promotion = $coupon->source;

        $merchantPromotion = $this->repo
            ->merchant_promotion
            ->findByMerchantAndPromotionId(
                $merchant->getId(),
                $promotion->getId()
            );

        return $merchantPromotion !== null;
    }

    public function isAnyCouponApplied(Merchant\Entity $merchant): bool
    {
        $merchantPromotion = $this->repo->merchant_promotion->getByMerchantId($merchant->getId());

        return $merchantPromotion->isEmpty() === false;
    }

    protected function shouldApplyPromotionPricing(Entity $coupon)
    {
        $couponCode = $coupon->getCode();

        $config = Constants::COUPON_CONFIG[$couponCode] ?? Constants::COUPON_CONFIG['default'];

        return $config[Constants::APPLY_PROMOTION_PRICING];
    }

    protected function applyPromotionPricing(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        $pricingPlanId = $promotion->getPricingPlanId();

        $oldPricingPlanId = $merchant->getPricingPlanId();

        $merchant->setPricingPlan($pricingPlanId);

        $this->repo->saveOrFail($merchant);

        $this->trace->info(TraceCode::MERCHANT_PROMOTION_PRICING_CHANGED,
            [
                'merchant_old_pricing_plan' => $oldPricingPlanId,
                'merchant_new_pricing_plan' => $merchant->getPricingPlanId(),
                Entity::MERCHANT_ID         => $merchant->getId(),
                'promotion_id'              => $promotion->getId(),
            ]);
    }

    public function createAndActivateMerchantPromotion(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        $merchantPromotionCore = (new MerchantPromotion\Core);

        $merchantPromotion = $merchantPromotionCore->create($merchant, $promotion);

        //
        //  Promotion/Coupon is applied to merchant only if merchant is activated.
        //  As credits and balance are credited for merchant in live mode once activated.
        //
        if ($merchant->isActivated() === true)
        {
            $merchantPromotionCore->activate($merchantPromotion);
        }
    }

    protected function mapPromotionPartnerIfApplicable(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        /** @var Merchant\Entity $partner */
        $partner = $promotion->partner;

        if (empty($partner) === false)
        {
            $merchantCore = new Merchant\Core;
            $role = null;
            $product = $promotion->getProduct() ?? Product::PRIMARY;

            if ($product === Product::BANKING)
            {
                $role = User\Role::VIEW_ONLY;
            }

            $merchantCore->createPartnerSubmerchantAccessMap($partner, $merchant, null, $role);

            $data = [
                'status'        => 'success',
                'merchant_id'   => $merchant->getId(),
                'partner_id'    => $partner->getId(),
                'source'        => PartnerConstants::COUPON,
                'product_group' => $product
            ];

            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP,
                $partner, null,
                $data);

            if ($partner->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM) === true)
            {
                $this->app->hubspot->skipMerchantOnboardingComm($merchant->getEmail());
            }

            $this->app->hubspot->trackSubmerchantSignUp($partner->getEmail());

            $dimension = [
                'partner_type' => $partner->getPartnerType(),
                'source'       => PartnerConstants::COUPON
            ];

            $this->trace->count(PartnerMetric::SUBMERCHANT_CREATE_TOTAL, $dimension);
            $merchantCore->pushSettleToPartnerSubmerchantMetrics($partner->getId(), $merchant->getId());
            $merchantCore->sendPartnerLeadInfoToSalesforce($merchant->getId(), $partner->getId(), $product);
        }
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

    /**
     * @param array $dateRanges
     * @return Entity
     */
    public function getExpiringCoupons(array $dateRanges)
    {
        return $this->repo->coupon->fetchCouponsByExpiry($dateRanges);
    }
}
