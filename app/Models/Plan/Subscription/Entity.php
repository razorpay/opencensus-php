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

    const PLAN_ID           = 'plan_id';
    const CUSTOMER_ID       = 'customer_id';
    //const CURRENT_START     = 'current_start';
    //const CURRENT_END       = 'current_end';
    const STATUS            = 'status';
    const ENDED_AT          = 'ended_at';
    const QUANTITY          = 'quantity';
    const TOKEN_ID          = 'token_id';
    const NOTES             = 'notes';
    const CHARGE_AT         = 'charge_at';
    const START_AT          = 'start_at';
    const END_AT            = 'end_at';

    protected static $sign = 'sub';

    protected $entity = 'subscription';

    protected $table = Table::SUBSCRIPTION;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::NOTES     => [],
        self::QUANTITY  => 1,
        self::ENDED_AT  => null,
        self::START_AT  => Status::CREATED,
    ];

    protected static $generators = [
        self::CHARGE_AT,
    ];

    protected $fillable = [
        self::QUANTITY,
        self::NOTES,
        self::START_AT,
        self::END_AT,
    ];

    protected $public = [
        self::PLAN_ID,
        self::CUSTOMER_ID,
        self::STATUS,
        //self::CURRENT_START,
        //self::CURRENT_END,
        self::ENDED_AT,
        self::QUANTITY,
        self::TOKEN_ID,
        self::NOTES,
        self::CHARGE_AT,
        self::START_AT,
        self::END_AT,
    ];

    protected $casts = [
        self::START_AT          => 'int',
        self::END_AT            => 'int',
        self::CHARGE_AT         => 'int',
        self::ENDED_AT          => 'int',
        self::QUANTITY          => 'int',
        //self::CURRENT_START     => 'int',
        //self::CURRENT_END       => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::TOKEN_ID,
        self::PLAN_ID,
    ];

    // --------------------- GETTERS ---------------------

    public function getChargeableAmount()
    {
        $quantity = $this->getAttribute(self::QUANTITY);

        $planAmount = $this->plan->getAmount();

        $chargeableAmount = $quantity * $planAmount;

        return $chargeableAmount;
    }

    public function getChargeAt()
    {
        return $this->getAttribute(self::CHARGE_AT);
    }

    public function getEndAt()
    {
        return $this->getAttribute(self::END_AT);
    }

    public function hasEnded()
    {
        return ($this->getAttribute(self::ENDED_AT) !== null);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    // --------------------- END GETTERS ---------------------

    // --------------------- SETTERS ---------------------

    public function setChargeAt($chargeAt)
    {
        $this->setAttribute(self::CHARGE_AT, $chargeAt);
    }

    public function setEndedAt($endAt)
    {
        $this->setAttribute(self::ENDED_AT, $endAt);
    }

    // --------------------- END SETTERS ---------------------

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

    // --------------------- GENERATORS ---------------------

    public function generateChargeAt($input)
    {
        $startAt = $input[Entity::START_AT];

        $this->setAttribute(self::CHARGE_AT, $startAt);
    }

    // --------------------- END GENERATORS ---------------------
}