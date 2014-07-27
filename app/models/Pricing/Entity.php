<?php

namespace Models\Pricing;

use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID                    = 'id';
    const PLAN_ID               = 'plan_id';
    const PLAN                  = 'plan';
    const GATEWAY               = 'gateway';
    const PAYMENT_MODE          = 'payment_mode';
    const PAYMENT_MODE_TYPE     = 'payment_mode_type';
    const PAYMENT_NETWORK       = 'payment_network';
    const PAYMENT_ISSUER        = 'payment_issuer';
    const PERCENT_RATE          = 'percent_rate';
    const FIXED_RATE            = 'fixed_rate';
    const EXPIRED_AT            = 'expired_at';

    public function newPlan()
    {
        $this->generateUniqueIdIfNotSet();

        $this->setAttribute(self::PLAN_ID, static::generateUniqueId());
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new Plan($models);
    }

}
