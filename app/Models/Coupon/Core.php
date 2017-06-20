<?php

namespace RZP\Models\Coupon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Schedule;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Promotion;
use RZP\Models\Merchant\Promotion as MerchantPromotion;

class Core extends Base\Core
{
    const SUCCESS_MESSAGE = 'Coupon Applied Successfully';

    public function create(array $input)
    {
        $coupon = (new Entity)->build($input);

        $entityType = $input[Entity::ENTITY_TYPE];

        $entity = $this->repo->$entityType->findByPublicId($input[Entity::ENTITY_ID]);

        $merchant = $this->repo->merchant->findByPublicId($input[Entity::MERCHANT_ID]);

        $coupon->source()->associate($entity);

        $coupon->merchant()->associate($merchant);

        $this->repo->saveOrFail($coupon);

        return $coupon;
    }

    public function apply(Merchant\Entity $merchant, Entity $coupon)
    {
        $this->validateMerchantPromotion($merchant, $coupon);

        $this->applyMerchantPromotion($merchant, $coupon);

        return [
            'message' => self::SUCCESS_MESSAGE
        ];
    }

    protected function validateMerchantPromotion(Merchant\Entity $merchant, Entity $coupon)
    {
        $promotion = $coupon->source;

        $merchantPromotion = $this->repo->merchant_promotion
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

    protected function applyMerchantPromotion(Merchant\Entity $merchant, Entity $coupon)
    {
        $promotion = $coupon->source;

        // This need to be in transaction, as credits are applied here,
        // And Schedule for next run is also created via Merchant Promotion
        // Coupon Usage is also updated
        // If either of these fail data need to be rollbacked
        $this->repo->transaction(
            function() use (
                $merchant,
                $promotion,
                $coupon)
            {
                $merchantPromotionCore = (new MerchantPromotion\Core);

                $merchantPromotion = $merchantPromotionCore->create($merchant, $promotion);

                // Initial apply of Credit is done instantly
                // Subsequent run and expiry will be handled by cron
                $merchantPromotionCore->applyCredits($merchant, $promotion);

                $coupon->incrementUsedCount();

                $merchantPromotion->updateRemainingRuns();

                $this->repo->saveOrFail($coupon);

                $this->repo->saveOrFail($merchantPromotion);
            });
    }
}
