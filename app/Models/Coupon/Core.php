<?php

namespace RZP\Models\Coupon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Models\Merchant\Promotion as MerchantPromotion;

class Core extends Base\Core
{
    const SUCCESS_MESSAGE = 'Coupon Applied Successfully';

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

        $coupon->source()->associate($entity);

        $coupon->merchant()->associate($merchant);

        $this->repo->saveOrFail($coupon);

        return $coupon;
    }

    public function apply(Merchant\Entity $merchant, Entity $coupon): array
    {
        $this->validateMerchantPromotion($merchant, $coupon);

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
            $merchantPromotionCore = (new MerchantPromotion\Core);

            $merchantPromotion = $merchantPromotionCore->create($merchant, $promotion);

            //
            // Initial apply of Credit is done instantly
            // Subsequent run and expiry will be handled by cron
            //
            $merchantPromotionCore->applyCredits($merchant, $promotion);

            $coupon->incrementUsedCount();

            $merchantPromotion->decrementRemainingIterations();

            $this->repo->saveOrFail($coupon);

            $this->repo->saveOrFail($merchantPromotion);
        });
    }
}
