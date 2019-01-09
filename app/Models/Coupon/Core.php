<?php

namespace RZP\Models\Coupon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Promotion as MerchantPromotion;
use RZP\Models\Merchant\Repository as MerchantRepository;

class Core extends Base\Core
{
    const SUCCESS_MESSAGE = 'Coupon Applied Successfully';

    const SUCCESS_MESSAGE_COUPON_VALID = 'Coupon is valid';

    public function create(array $input): Entity
    {
        $coupon = (new Entity)->build($input);

        $entityType = $input[Entity::ENTITY_TYPE];

        $entity = $this->repo->$entityType->findByPublicId($input[Entity::ENTITY_ID]);

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
                ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE);
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

    public function apply(Merchant\Entity $merchant, Entity $coupon,bool $isCheck = false): array
    {
        $this->validateMerchantPromotion($merchant, $coupon);

        if($isCheck === true)
        {
            return [
                'message'  =>  self::SUCCESS_MESSAGE_COUPON_VALID
            ];
        }

        $this->applyMerchantPromotion($merchant, $coupon);

        return [
            'message' => self::SUCCESS_MESSAGE
        ];
    }

    /**
     * Check if the coupon is valid for given merchant
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
            $pricingPlanId = $promotion->getPricingPlanId();

            $merchant->setPricingPlan($pricingPlanId);

            (new MerchantRepository)->saveOrFail($merchant);

            $merchantPromotionCore = (new MerchantPromotion\Core);

            $merchantPromotion = $merchantPromotionCore->create($merchant, $promotion);


            //
            // Merchant need not be activated for redeeming the coupon. Merchants signing up through
            // the promotional signup link must be eligible for the promotion
            //

            $merchantPromotionCore->activate($merchantPromotion);

            $coupon->incrementUsedCount();

            $this->repo->saveOrFail($coupon);
        });
    }
}
