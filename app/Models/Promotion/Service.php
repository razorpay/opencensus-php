<?php

namespace RZP\Models\Promotion;

use App;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Service extends Base\Service
{
    public function createPromotionForEvent(array $input)
    {
        $this->trace->info(TraceCode::PROMOTION_FOR_EVENT_CREATE_REQUEST, $input);

        (new Validator)->validateInput(Validator::EVENT_PROMOTION, $input);

        $this->validateModeIsApplicableForProduct($this->mode, $input[Entity::PRODUCT]);

        $input[Entity::CREATOR_NAME] = $this->auth->getAdmin()->getName();
        $input[Entity::CREATOR_EMAIL] = $this->auth->getAdmin()->getEmail();

        $eventId = $input[Entity::EVENT_ID];

        unset($input[Entity::EVENT_ID]);

        $event = $this->repo->promotion_event->findByPublicId($eventId);

        $promotion = $this->core()->create($input, $event);

        $this->trace->info(TraceCode::PROMOTION_FOR_EVENT_CREATE_RESPONSE,
            [
                'promotion_id' => $promotion->getId()
            ]);

        return $promotion->toArrayAdmin();
    }

    public function create(array $input): array
    {
        $this->trace->info(TraceCode::PROMOTION_CREATE_REQUEST, $input);

        $input[Entity::CREATOR_NAME] = $this->auth->getAdmin()->getName();
        $input[Entity::CREATOR_EMAIL] = $this->auth->getAdmin()->getEmail();

        $promotion = $this->core()->create($input);

        $this->trace->info(TraceCode::PROMOTION_CREATE_RESPONSE,
            [
                'promotion_id' => $promotion->getId()
            ]);

        return $promotion->toArrayAdmin();
    }

    public function update(string $id, array $input): array
    {
        $this->trace->info(TraceCode::PROMOTION_UPDATE_REQUEST, $input);

        $promotion = $this->repo->promotion->findByPublicId($id);

        $promotion = $this->core()->update($promotion, $input);

        return $promotion->toArrayAdmin();
    }

    public function deactivatePromotion(string $id)
    {
        $this->trace->info(TraceCode::PROMOTION_DEACTIVATE_REQUEST,
            [
                'promotion_id' => $id
            ]);

        $promotion = $this->repo->promotion->findOrFailPublic($id);

        $this->validateModeIsApplicableForProduct($this->mode, $promotion->getProduct());

        $adminName = $this->auth->getAdmin()->getName();

        $input[Entity::DEACTIVATED_BY] = $adminName;

        $promotion = $this->core()->deactivatePromotion($promotion, $input);

        $this->trace->info(TraceCode::PROMOTION_DEACTIVATE_RESPONSE,
            [
                'promotion_id'      => $promotion->getId(),
                'deactivated_by'    => $input[Entity::DEACTIVATED_BY],
            ]);

        return $promotion->toArrayAdmin();

    }

    protected function validateModeIsApplicableForProduct(string $mode, string $product = null)
    {
        if (($mode === Mode::TEST) and
            ($product === Entity::BANKING))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PROMOTIONS_FOR_X_SUPPORTED_IN_ONLY_LIVE_MODE);
        }
    }
}
