<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Models\Schedule\Anchor;
use RZP\Exception\BadRequestException;
use RZP\Models\{Base, Schedule, Merchant};
use RZP\Models\Merchant\Promotion as MerchantPromotion;

class Core extends Base\Core
{
    public function create(array $input, Event\Entity $event = null): Entity
    {
        $partner = null;

        if (empty($input[Entity::PARTNER_ID]) === false)
        {
            /** @var Merchant\Entity $merchant */
            $partner = $this->repo->merchant->findOrFailPublic($input[Entity::PARTNER_ID]);

            (new Merchant\Validator)->validateIsNonPurePlatformPartner($partner);

            unset($input[Entity::PARTNER_ID]);
        }

        return $this->repo->transaction(function() use ($input, $partner, $event)
        {
            $promotion = (new Entity)->build($input);

            if ($promotion->doCreditsExpire() === true)
            {
                $schedule = $this->createSchedule($input);

                $promotion->schedule()->associate($schedule);
            }

            if (empty($partner) === false)
            {
                $promotion->partner()->associate($partner);
            }

            if (empty($event) === false)
            {
                $existingPromotion = $this->repo->promotion->checkIfActivatedPromotionForEventExists(
                                                                    $promotion,
                                                                    $event);

                if ($existingPromotion !== null)
                {
                    throw new Exception\BadRequestException(
                                    ErrorCode::BAD_REQUEST_ACTIVE_PROMOTION_FOR_EVENT_ALREADY_EXISTS);
                }

                $promotion->setStatus(Entity::ACTIVATED);

                $promotion->event()->associate($event);
            }

            $this->repo->saveOrFail($promotion);

            return $promotion;
        });
    }

    public function update(Entity $promotion, array $input): Entity
    {
        if ($this->isUsed($promotion) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Editing a used promotion is not allowed');
        }

        if ($promotion->getProduct() === Entity::BANKING)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Editing a banking promotion is not allowed');
        }

        return $this->repo->transaction(
            function() use ($promotion, $input)
        {
            $promotion->edit($input);

            if ($promotion->doCreditsExpire() === true)
            {
                $schedule = $this->createSchedule($input);

                $promotion->schedule()->associate($schedule);
            }

            $this->repo->saveOrFail($promotion);

            return $promotion;
        });
    }

    public function deactivatePromotion(Entity $promotion, array $input): Entity
    {
        if ($this->canPromotionBeDeactivated($promotion) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The promotion cannot be deactivated');
        }

        if ($this->isDeactivated($promotion) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Promotion already deactivated');
        }

        (new Validator)->validateInput(Validator::DEACTIVATE, $input);

        $promotion->edit($input);

        $promotion->setStatus(Entity::DEACTIVATED);

        $this->repo->saveOrFail($promotion);

        return $promotion;
    }

    public function processTasks(Base\PublicCollection $tasks, int $timestamp): array
    {
        return (new MerchantPromotion\Core)->processTasks($tasks, $timestamp);
    }

    public function applyEventPromotionToMerchant(string $eventName, string $product, Merchant\Entity $merchant)
    {
        $event = $this->repo->promotion_event->getEventByName($eventName);

        if ($event === null)
        {
            return;
        }

        $existingPromotions = $this->repo
                                    ->promotion
                                    ->getActivePromotionsRunningCurrentlyForEvent(
                                        $event,
                                        $product);

        if ($existingPromotions->count() === 0)
        {
            return;
        }

        if ($existingPromotions->count() > 1)
        {
            throw new Exception\LogicException(
                'found more than 1 active promotions for an event',
                [
                    'count'    => $existingPromotions->count(),
                    'event_id' => $event->getId(),
                ]);
        }

        $promotion = $existingPromotions[0];

        $existingMerchantPromotion = $this->repo->merchant_promotion->checkIfMerchantPromotionAlreadyExists($merchant, $promotion);

        if ($existingMerchantPromotion !== null)
        {
            return;
        }

        $merchantPromotionCore = (new MerchantPromotion\Core);

        $merchantPromotion = $merchantPromotionCore->create($merchant, $promotion);

        // we will assign rewards once the merchant signs up but
        // the consumption will be restricted to when he is going
        // to make payouts
        $merchantPromotionCore->activate($merchantPromotion);

        return $merchantPromotion;
    }

    public function isUsed(Entity $promotion): bool
    {
        $usedCount = $this->repo
                          ->merchant_promotion
                          ->getCountByPromotionId($promotion->getId());

        if ($usedCount === 0)
        {
            return false;
        }

        return true;
    }

    public function applyPromotion(Merchant\Entity $merchant, string $product = null, string $eventName = null)
    {
        if ($product !== Product::BANKING)
        {
            // we are not supporting normal promotions through this flow.
            // This to avoid the code to run into unknown issues.
            // If PG plans to use this flow for promotion, after modifying
            // the flow accordingly they can disable this check
            return;
        }

        if ($this->mode === Mode::TEST)
        {
            // banking promotions will run only in live mode.
            return;
        }

        $this->trace->info(TraceCode::PROMOTION_APPLY_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'product_id'  => $product,
                'event'       => $eventName,
            ]);

        $merchantPromotion = $this->applyEventPromotionToMerchant(
                                            $eventName,
                                            $product,
                                            $merchant);

        return $merchantPromotion;
    }

    public function isDeactivated(Entity $promotion): bool
    {
        $status = $promotion->getStatus();

        if ($status === Entity::DEACTIVATED)
        {
            return true;
        }

        return false;
    }

    protected function canPromotionBeDeactivated(Entity $promotion): bool
    {
        $product = $promotion->getProduct() ;

        if ($product === Entity::BANKING)
        {
            return true;
        }

        return false;
    }

    protected function createSchedule(array $input): Schedule\Entity
    {
        $period = $input[Entity::CREDITS_EXPIRY_PERIOD];

        $interval = $input[Entity::CREDITS_EXPIRY_INTERVAL];

        $anchor = $this->getAnchorForPromotion($period);

        $schedule = $this->repo->schedule->getScheduleByPeriodIntervalAnchorAndType(
            $period, $interval, $anchor, Schedule\Type::PROMOTION);

        if ($schedule === null)
        {
            $scheduleName =  $interval . '/' . $period;

            $scheduleInput = [
                Schedule\Entity::NAME     => $scheduleName,
                Schedule\Entity::INTERVAL => $interval,
                Schedule\Entity::PERIOD   => $period,
                Schedule\Entity::ANCHOR   => $anchor,
                Schedule\Entity::TYPE     => Schedule\Type::PROMOTION
            ];

            $schedule = (new Schedule\Core)->createSchedule($scheduleInput);
        }

        return $schedule;
    }

    protected function getAnchorForPromotion(string $period)
    {
       $anchor = null;

       //For hourly AND daily, anchor is not significant

        $unAnchoredPeriods = [
            Schedule\Period::HOURLY,
            Schedule\Period::DAILY
        ];

        if (in_array($period, $unAnchoredPeriods, true) === true)
        {
            return null;
        }

       $currentTime = Carbon::now(Timezone::IST);

       $anchor = Anchor::getAnchor($period, $currentTime);

       return $anchor;
    }
}
