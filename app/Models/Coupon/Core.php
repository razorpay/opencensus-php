<?php

namespace RZP\Models\Coupon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Schedule;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Promotion;
use RZP\Constants\Entity as PublicEntity;
use RZP\Models\Merchant\Promotions as MerchantPromotion;

class Core extends Base\Core
{
    const SUCESS_MESSAGE = 'Coupon Applied Successfully';

    public function create(array $input)
    {
        $publicEntityId = $input[Entity::ENTITY_ID];

        $entityType = $input[Entity::ENTITY_TYPE];

        $entityClass = PublicEntity::getEntityClass($entityType);

        $entityClass::verifyIdAndSilentlyStripSign($input[Entity::ENTITY_ID]);

        $coupon = (new Entity)->build($input);

        $entity = $this->repo->$entityType->findByPublicId($publicEntityId);

        $coupon->source()->associate($entity);

        $this->repo->saveOrFail($coupon);

        return $coupon;
    }

    public function apply(Merchant\Entity $merchant, Entity $coupon)
    {
        $this->validateAndApplyMerchantPromotion($merchant, $coupon);

        return [
            'message' => self::SUCESS_MESSAGE
        ];
    }

    protected function validateAndApplyMerchantPromotion(Merchant\Entity $merchant, Entity $coupon)
    {
        $promotion = $coupon->source()->firstOrFail();

        $merchantPromotion = $this->repo->merchant_promotion->findByMerchantAndPromotionId(
                                $merchant->getId(),
                                $promotion->getId());

        if ($merchantPromotion !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_ALREADY_USED);
        }

        $coupon->getValidator()->validateApplyCoupon($merchant);

        $merchantPromotionCore = (new MerchantPromotion\Core);

        // This need to be in transaction, as credits are applied here,
        // And Schedule for next run is also created via Merchant Promotion
        // Coupon Usage is also updated
        // If either of these fail data need to be rollbacked
        $this->repo->transaction(
            function() use (
                $merchant,
                $promotion,
                $coupon,
                $merchantPromotionCore)
            {
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
