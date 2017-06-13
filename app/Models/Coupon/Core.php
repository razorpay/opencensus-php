<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Models\Schedule;
use RZP\Models\Promotion;
use RZP\Constants\Entity as PublicEntity;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Promotions as MerchantPromotion;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $publicEntityId = $input[Entity::ENTITY_ID];

        (PublicEntity::getEntityClass($input[Entity::ENTITY_TYPE]))::
            verifyIdAndSilentlyStripSign($input[Entity::ENTITY_ID]);

        $coupon = (new Entity)->build($input);

        $entityType = $input[Entity::ENTITY_TYPE];

        $entity = $this->repo->$entityType->findByPublicId($publicEntityId);

        $coupon->source()->associate($entity);

        $coupon = $entity->coupons()->save($coupon);

        return $coupon;
    }

    public function apply(Merchant\Entity $merchant, Entity $coupon)
    {
        $this->validateAndApplyMerchantPromotion($merchant, $coupon);

        return ['message' => 'Coupon Applied Successfully'];
    }

    protected function validateAndApplyMerchantPromotion(Merchant\Entity $merchant, Entity $coupon)
    {
        $promotion = $coupon->source()->firstOrFail();

        $merchantPromotion = $this->repo->merchant_promotion->findByMerchantAndPromotionId(
                                $merchant->getId(), $promotion->getId());

        if ($merchantPromotion !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_ALREADY_USED);
        }

        $coupon->getValidator()->couponApplyValidator($merchant);

        $this->repo->transaction(function() use ($merchant, $promotion, $coupon)
        {
            $merchantPromotionCore = (new MerchantPromotion\Core);

            $merchantPromotion = $merchantPromotionCore->create($merchant, $promotion);

            // Initial apply of Credit is done instantly
            // Subsequent run and expiry will be handled by cron
            $merchantPromotionCore->applyCredits($merchant, $promotion);

            $coupon->setUsedCount($coupon->getUsedCount() + 1);

            $merchantPromotion->updateRemainingRuns();

            $this->repo->saveOrFail($coupon);

            $this->repo->saveOrFail($merchantPromotion);
        });
    }
}
