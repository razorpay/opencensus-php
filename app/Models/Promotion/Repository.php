<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'promotion';

    /**
     * @param Entity $promotion
     * @param Event\Entity $event
     * @return mixed
     *
     * This method checks if for an event, there is any promotion that already exists and is in
     * activated state in the time period someone wants to create promotion.
     * Note all promotions will have start date always, either given by the user or the current
     * timestamp. End date if not given by the user will remain null(ie running till infinity)
     * When promotion has an end date, we need to check if any promotion exists which runs till
     * infinity and begins somewhere between this promotion's start and end date. We also
     * need to check if any promotion exists whose end or start date is between the start and end
     * of promotion being created
     * If the promotion does not have end date, we need to first check if there is already a promotion
     * running till infinity, in that case we can't create this one
     * Else if there is any promotion which ends after this promotion starts to see if it lies between
     * start and end of the promotion being created
     */
    public function checkIfActivatedPromotionForEventExists(Entity $promotion , Event\Entity $event)
    {
        $query = $this->newQuery()
                      ->where(Entity::EVENT_ID, $event->getId())
                      ->where(Entity::STATUS, Entity::ACTIVATED)
                      ->where(function($query) use ($promotion) {
                          if ($promotion->getEndAt() !== null)
                          {
                                $query->whereNull(Entity::END_AT)
                                      ->where(Entity::START_AT , '<', $promotion->getEndAt())
                                      ->orWhere(function($query) use ($promotion)
                                      {
                                          // either promotion lies before start or after end at
                                          $query->whereNotNull(Entity::END_AT)
                                                ->whereBetween(Entity::END_AT, [$promotion->getStartAt(), $promotion->getEndAt()])
                                                ->orwhereBetween(Entity::START_AT, [$promotion->getStartAt(), $promotion->getEndAt()]);
                                      })
                                      ->orWhere(function($query) use ($promotion) {
                                          // promotion lies b/w any existing promotion dates
                                          $query->whereNotNull(Entity::END_AT)
                                              ->where(Entity::END_AT ,'>', $promotion->getEndAt())
                                              ->where(Entity::START_AT ,'<',$promotion->getStartAt());
                                      });
                          }
                          else
                          {
                              $query->whereNull(Entity::END_AT)
                                    ->orWhere(function($query) use ($promotion)
                                  {
                                      $query->whereNotNull(Entity::END_AT)
                                            ->where(Entity::END_AT, '>=', $promotion->getStartAt());
                                  });
                          }
                      });

        return $query->first();
    }

    public function getActivePromotionsRunningCurrentlyForEvent(Event\Entity $event, string $product)
    {
        $timestamp = Carbon::now()->getTimestamp();

        $query = $this->newQuery()
                    ->where(Entity::EVENT_ID, $event->getId())
                    ->where(Entity::PRODUCT, $product)
                    ->where(Entity::START_AT, '<=', $timestamp)
                    ->where(Entity::STATUS, Entity::ACTIVATED)
                    ->where(function($query) use ($timestamp){
                        $query->whereNull(Entity::END_AT)
                              ->orWhere(Entity::END_AT,'>=',$timestamp);
                    });

        return $query->get();
    }
}
