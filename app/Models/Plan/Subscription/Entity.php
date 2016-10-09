<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Plan;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const PLAN_ID               = 'plan_id';
    const CUSTOMER_ID           = 'customer_id';
    const CURRENT_PERIOD_START  = 'current_period_start';
    const CURRENT_PERIOD_END    = 'current_period_end';
    const ENDED_AT              = 'ended_at';
    const QUANTITY              = 'quantity';
    const TOKEN_ID              = 'token_id';
    const NOTES                 = 'notes';

    protected static $sign = 'sub';

    protected $entity = 'subscription';

    protected $table = Table::SUBSCRIPTION;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::NOTES     => [],
        self::QUANTITY  => 1,
        self::ENDED_AT  => null,
    ];

    protected $fillable = [
        self::QUANTITY,
        self::NOTES,
    ];

    protected $public = [
        self::PLAN_ID,
        self::CUSTOMER_ID,
        self::CURRENT_PERIOD_START,
        self::CURRENT_PERIOD_END,
        self::ENDED_AT,
        self::QUANTITY,
        self::TOKEN_ID,
        self::NOTES,
    ];

    protected $casts = [
        self::ENDED_AT              => 'int',
        self::QUANTITY              => 'int',
        self::CURRENT_PERIOD_START  => 'int',
        self::CURRENT_PERIOD_END    => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::TOKEN_ID,
        self::PLAN_ID,
    ];

    // --------------------- RELATIONS ---------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function plan()
    {
        return $this->belongsTo('RZP\Models\Plan\Entity');
    }

    public function token()
    {
        return $this->belongsTo('RZP\Models\Customer\Token\Entity');
    }

    // --------------------- END RELATIONS ---------------------

    // --------------------- PUBLIC SETTERS ---------------------


    public function setPublicPlanIdAttribute(Array & $array)
    {
        if (isset($array[self::PLAN_ID]))
        {
            $planId = $this->getAttribute(self::PLAN_ID);

            $array[self::PLAN_ID] = Plan\Entity::getSignedId($planId);
        }
    }

    public function setPublicCustomerIdAttribute(Array & $array)
    {
        if (isset($array[self::CUSTOMER_ID]))
        {
            $customerId = $this->getAttribute(self::CUSTOMER_ID);

            $array[self::CUSTOMER_ID] = Customer\Entity::getSignedId($customerId);
        }
    }

    public function setPublicTokenIdAttribute(Array & $array)
    {
        if (isset($array[self::TOKEN_ID]))
        {
            $tokenId = $this->getAttribute(self::TOKEN_ID);

            $array[self::TOKEN_ID] = Customer\Token\Entity::getSignedId($tokenId);
        }
    }

    // --------------------- END PUBLIC SETTERS ---------------------
}