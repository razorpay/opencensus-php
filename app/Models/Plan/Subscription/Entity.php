<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Plan;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Schedule\Anchor;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const PLAN_ID           = 'plan_id';
    const CUSTOMER_ID       = 'customer_id';
    const CURRENT_START     = 'current_start';
    const CURRENT_END       = 'current_end';
    const STATUS            = 'status';
    const ENDED_AT          = 'ended_at';
    const ACTIVATED_AT      = 'activated_at';
    const QUANTITY          = 'quantity';
    const TOKEN_ID          = 'token_id';
    const NOTES             = 'notes';
    const CHARGE_AT         = 'charge_at';
    const START_AT          = 'start_at';
    const END_AT            = 'end_at';
    const TOTAL_COUNT       = 'total_count';
    const PAID_COUNT        = 'paid_count';
    const AUTH_ATTEMPTS     = 'auth_attempts';
    const ERROR_STATUS      = 'error_status';
    const SCHEDULE_ID       = 'schedule_id';
    const CUSTOMER_NOTIFY   = 'customer_notify';
    const TYPE              = 'type';

    const FAILED_AT         = 'failed_at';
    const AUTHENTICATED_AT  = 'authenticated_at';
    const CANCELLED_AT      = 'cancelled_at';

    // Input Keys

    /**
     * Add-on needs to be at a subscription level because
     * the add-on amount can change based on the subscription period.
     * For example: if the subscription is for 3 months, add-on amount can
     * be 1000rs and if subscription is for 1yr, add-on amount can be 500rs.
     */
    const ADDONS = 'addons';

    /**
     * This key is used to search in subscriptions fetch multiple
     */
    const CUSTOMER_EMAIL = 'customer_email';

    protected static $sign = 'sub';

    protected $entity = 'subscription';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::NOTES             => [],
        self::QUANTITY          => 1,
        self::ENDED_AT          => null,
        self::STATUS            => Status::CREATED,
        self::PAID_COUNT        => 0,
        self::AUTH_ATTEMPTS     => 0,
        self::TYPE              => 0,
        self::ERROR_STATUS      => null,
        self::ACTIVATED_AT      => null,
        self::FAILED_AT         => null,
        self::CURRENT_START     => null,
        self::CURRENT_END       => null,
        self::TOKEN_ID          => null,
        self::START_AT          => null,
        self::END_AT            => null,
    ];

    protected static $generators = [
        self::CHARGE_AT,
        self::TYPE,
    ];

    protected $fillable = [
        self::QUANTITY,
        self::NOTES,
        self::START_AT,
        self::TOTAL_COUNT,
        self::END_AT,
        self::CUSTOMER_NOTIFY,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PLAN_ID,
        self::CUSTOMER_ID,
        //
        // We need to expose only the unconsumed ones via the subscription
        // We cannot expose every single add on ever created for the
        // subscription. Until we find a good solution to expose this,
        // keep this commented.
        //
        // self::ADDONS,
        self::STATUS,
        self::TYPE,
        self::CURRENT_START,
        self::CURRENT_END,
        self::ENDED_AT,
        self::QUANTITY,
        // self::TOKEN_ID,
        self::NOTES,
        self::CHARGE_AT,
        self::START_AT,
        self::END_AT,
        self::AUTH_ATTEMPTS,
        self::TOTAL_COUNT,
        self::PAID_COUNT,
        self::CUSTOMER_NOTIFY,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::START_AT          => 'int',
        self::END_AT            => 'int',
        self::QUANTITY          => 'int',
        self::CURRENT_START     => 'int',
        self::CURRENT_END       => 'int',
        self::TOTAL_COUNT       => 'int',
        self::PAID_COUNT        => 'int',
        self::AUTH_ATTEMPTS     => 'int',
        self::CUSTOMER_NOTIFY   => 'bool',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        // self::TOKEN_ID,
        self::PLAN_ID,
        // Later, we will come up with a proper structure to show
        // fields based on proper auth structure.
        // TODO: Remove this when the above is implemented
        self::TYPE,
    ];

    protected $appends = [
        // This has to be via appends and not relations
        // because it's a hasMany relation.
        self::ADDONS,
    ];

    protected $dates = [
        self::CHARGE_AT,
        self::CURRENT_END,
        self::CURRENT_START,
        self::ENDED_AT,
        self::ACTIVATED_AT,
        self::START_AT,
        self::END_AT,
    ];

    const DEFAULT_AUTH_AMOUNT = 500;

    const MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION = 10;

    // --------------------- GETTERS ---------------------

    public function getChargeableAmount(): int
    {
        $quantity = $this->getQuantity();

        $planAmount = $this->plan->item->getAmount();

        $chargeableAmount = $quantity * $planAmount;

        return $chargeableAmount;
    }

    public function getQuantity()
    {
        return $this->getAttribute(self::QUANTITY);
    }

    public function getChargeAt()
    {
        return $this->getAttribute(self::CHARGE_AT);
    }

    public function getStartAt()
    {
        return $this->getAttribute(self::START_AT);
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

    public function getErrorStatus()
    {
        return $this->getAttribute(self::ERROR_STATUS);
    }

    public function getTotalCount()
    {
        return $this->getAttribute(self::TOTAL_COUNT);
    }

    public function getPlanChargeInvoicesCount()
    {
        $invoiceCount = $this->invoices()->count();

        // If the subscription started with an addon, and not with a plan
        // amount then there is an extra invoice, which is to be excluded.
        if (($this->hadUpfrontAmount() === true) and
            ($this->wasImmediate() === false))
        {
            $invoiceCount = $invoiceCount - 1;
        }

        return $invoiceCount;
    }

    public function getPaidCount()
    {
        return $this->getAttribute(self::PAID_COUNT);
    }

    public function getCurrentStart()
    {
        return $this->getAttribute(self::CURRENT_START);
    }

    public function getCurrentEnd()
    {
        return $this->getAttribute(self::CURRENT_END);
    }

    public function getCancelledAt()
    {
        return $this->getAttribute(self::CANCELLED_AT);
    }

    public function getAuthAttempts()
    {
        return $this->getAttribute(self::AUTH_ATTEMPTS);
    }

    public function getTokenId()
    {
        return $this->getAttribute(self::TOKEN_ID);
    }

    public function hasToken()
    {
        return $this->isAttributeNotNull(self::TOKEN_ID);
    }

    public function hasSchedule()
    {
        return $this->isAttributeNotNull(self::SCHEDULE_ID);
    }

    public function hasCustomer()
    {
        return $this->isAttributeNotNull(self::CUSTOMER_ID);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function hasBeenAuthenticated()
    {
        return $this->isAttributeNotNull(self::AUTHENTICATED_AT);
    }

    public function isCreated()
    {
        return ($this->getAttribute(self::STATUS) === Status::CREATED);
    }

    public function isAuthenticated()
    {
        return ($this->getAttribute(self::STATUS) === Status::AUTHENTICATED);
    }

    public function isActive()
    {
        return ($this->getAttribute(self::STATUS) === Status::ACTIVE);
    }

    public function isPending()
    {
        return ($this->getAttribute(self::STATUS) === Status::PENDING);
    }

    public function isHalted()
    {
        return ($this->getAttribute(self::STATUS) === Status::HALTED);
    }

    public function isExpired()
    {
        return ($this->getAttribute(self::STATUS) === Status::EXPIRED);
    }

    public function isCompleted()
    {
        return ($this->getAttribute(self::STATUS) === Status::COMPLETED);
    }

    public function setType(string $type, bool $value)
    {
        $currentHex = $this->getType();

        $newHex = Type::getHexWithTypeMarked($currentHex, $type, $value);

        $this->setTypeHex($newHex);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    protected function isTypeApplicable(string $type)
    {
        $hex = $this->getType();

        return Type::isApplicable($hex, $type);
    }

    public function wasImmediate()
    {
        return ($this->isTypeApplicable(Type::IMMEDIATE) === true);
    }

    public function hadUpfrontAmount()
    {
        return ($this->isTypeApplicable(Type::UPFRONT) === true);
    }

    public function isFutureNotUpfront()
    {
        return (($this->wasImmediate() === false) and
                ($this->hadUpfrontAmount() === false));
    }

    public function isAuthTxnCharge()
    {
        //
        // This signifies that the auth transaction also
        // includes the first charge of the subscription.
        //
        return ($this->wasImmediate() === true);
    }

    public function isChangeCardStatus()
    {
        return (in_array($this->getStatus(), Status::$changeCardStatuses, true) === true);
    }

    public function followLocalFlow()
    {
        $localCustomer = $this->customer;

        // If local customer is null, then it needs to be created and mapped
        // to global customer for subscription first 2FA txn.
        if ($localCustomer === null)
        {
            return false;
        }

        //
        // If global customer is null, it means, that the subscription
        // follows the local flow only.
        // In case it's not null, it means that this is NOT the first 2FA
        // txn and is a second charge or change card flow
        // and follows global flow. The mapping happens in first txn.
        //
        $hasGlobalCustomer = $localCustomer->hasGlobalCustomer();

        return ($hasGlobalCustomer === false);
    }

    // --------------------- END GETTERS ---------------------

    // --------------------- ACCESSORS ---------------------

    public function getAddonsAttribute()
    {
        //
        // NOTE: This is not a good solution because this will list
        // down all the addons ever created of the subscription and
        // not just the unconsumed ones.
        //
        $addons = $this->addons()->getResults()->toArrayPublicEmbedded();

        return $addons;
    }

    // --------------------- END ACCESSORS ---------------------

    // --------------------- SETTERS ---------------------

    public function setStartAt($startAt)
    {
        $this->setAttribute(self::START_AT, $startAt);
    }

    public function setEndAt($endAt)
    {
        $this->setAttribute(self::END_AT, $endAt);
    }

    public function setChargeAt($chargeAt)
    {
        $this->setAttribute(self::CHARGE_AT, $chargeAt);
    }

    public function setEndedAt($endAt)
    {
        $this->setAttribute(self::ENDED_AT, $endAt);
    }

    public function setStatus($status)
    {
        Status::validateStatus($status);

        $this->setAttribute(self::STATUS, $status);

        if (in_array($status, Status::$timestampedStatuses, true) === true)
        {
            $timestampKey = $status . '_at';

            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute($timestampKey, $currentTime);
        }
    }

    public function setCurrentStart($currentStart)
    {
        $this->setAttribute(self::CURRENT_START, $currentStart);
    }

    public function setCurrentEnd($currentEnd)
    {
        $this->setAttribute(self::CURRENT_END, $currentEnd);
    }

    public function setActivatedAt($activatedAt)
    {
        $this->setAttribute(self::ACTIVATED_AT, $activatedAt);
    }

    public function setTotalCount($totalCount)
    {
        $this->setAttribute(self::TOTAL_COUNT, $totalCount);
    }

    public function incrementPaidCount()
    {
        $this->increment(self::PAID_COUNT);
    }

    public function incrementAuthAttempts()
    {
        $this->increment(self::AUTH_ATTEMPTS);
    }

    public function setErrorStatus($errorStatus)
    {
        $this->setAttribute(self::ERROR_STATUS, $errorStatus);
    }

    public function setFailedAt($failedAt)
    {
        $this->setAttribute(self::FAILED_AT, $failedAt);
    }

    public function resetAuthAttempts()
    {
        $this->setAttribute(self::AUTH_ATTEMPTS, 0);
    }

    public function setTypeHex(string $hex)
    {
        $this->setAttribute(self::TYPE, $hex);
    }

    /**
     * This is here just for test cases.
     *
     * @param $tokenId
     */
    public function setTokenId($tokenId)
    {
        $this->setAttribute(self::TOKEN_ID, $tokenId);
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

    public function payments()
    {
        return $this->hasMany('RZP\Models\Payment\Entity');
    }

    public function invoices()
    {
        return $this->hasMany('RZP\Models\Invoice\Entity');
    }

    public function schedule()
    {
        return $this->belongsTo('RZP\Models\Schedule\Entity');
    }

    public function task()
    {
        return $this->morphOne('RZP\Models\Schedule\Task\Entity', 'entity');
    }

    public function addons()
    {
        return $this->hasMany('RZP\Models\Plan\Subscription\Addon\Entity');
    }

    // --------------------- END RELATIONS ---------------------

    // --------------------- PUBLIC SETTERS ---------------------


    public function setPublicPlanIdAttribute(array & $array)
    {
        $planId = $this->getAttribute(self::PLAN_ID);

        $array[self::PLAN_ID] = Plan\Entity::getSignedIdOrNull($planId);
    }

    public function setPublicTypeAttribute(array & $array)
    {
        $app = App::getFacadeRoot();

        $basicAuth = $app['basicauth'];

        if ($basicAuth->isProxyOrPrivilegeAuth() === false)
        {
            unset($array[self::TYPE]);
        }
    }

    public function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    // public function setPublicTokenIdAttribute(array & $array)
    // {
    //     $tokenId = $this->getAttribute(self::TOKEN_ID);
    //
    //     $array[self::TOKEN_ID] = Customer\Token\Entity::getSignedIdOrNull($tokenId);
    // }

    // --------------------- END PUBLIC SETTERS ---------------------

    // --------------------- GENERATORS ---------------------

    public function generateChargeAt($input)
    {
        if (empty($input[Entity::START_AT]) === true)
        {
            $chargeAt = null;
        }
        else
        {
            $chargeAt = (int) $input[Entity::START_AT];
        }

        $this->setAttribute(self::CHARGE_AT, $chargeAt);
    }

    public function generateType($input)
    {
        if (empty($input[Entity::START_AT]) === true)
        {
            $this->setType(Type::IMMEDIATE, true);
        }

        if (empty($input[Entity::ADDONS]) === false)
        {
            $this->setType(Type::UPFRONT, true);
        }
    }

    // --------------------- END GENERATORS ---------------------

    public function associateEntities(
        Plan\Entity $plan,
        Customer\Entity $customer = null)
    {
        //
        // Cannot get it via customer since customer can be null too.
        //
        $merchant = $plan->merchant;

        $this->merchant()->associate($merchant);
        $this->plan()->associate($plan);

        //
        // Don't want to override the relation to null by mistake;
        // hence the check.
        //
        if ($customer !== null)
        {
            $this->customer()->associate($customer);
        }
    }

    public function getAnchorForSchedule()
    {
        $anchor = null;

        $period = $this->plan->getPeriod();

        if ($this->getStartAt() !== null)
        {
            $startAt = Carbon::createFromTimestamp($this->getStartAt(), Timezone::IST);

            $anchor = Anchor::getAnchor($period, $startAt);

            //
            // Commenting this out for now, since we are not
            // clear on what should be the behaviour.
            // If the subscription is starting on Feb 28th,
            // we'll end up charging on March 31st, April 30th
            // and so on. This may not be the expected behaviour.
            // Also, there will be subscriptions which should
            // always be charged on 28th of every month.
            // If we implement the below block, there will be no
            // way to do something like start_at = 28th Feb,
            // charge on 28th of every month.
            //
            // if (($period === Plan\Cycle::MONTHLY) and
            //     ($startAt->lastOfMonth() === true))
            // {
            //     $anchor = -1;
            // }
        }

        return $anchor;
    }

    public function isMoreThanOneYear()
    {
        $plan = $this->plan;

        $totalCount = $this->getTotalCount();

        $totalCountForOneYear = Plan\Cycle::getTotalCountForOneYear($plan);

        return ($totalCount > $totalCountForOneYear);
    }
}
