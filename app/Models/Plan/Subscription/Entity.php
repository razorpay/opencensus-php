<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\Plan;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Schedule\Task;
use RZP\Models\Customer\Token;
use RZP\Models\Schedule\Anchor;
use RZP\Models\Schedule\Period;
use RZP\Exception\LogicException;
use RZP\Models\Base\Traits\ExternalOwner;
use RZP\Models\Base\Traits\NotesTrait;

/**
 * @property Invoice\Entity     $invoice
 * @property Customer\Entity    $customer
 * @property Plan\Entity        $plan
 * @property Item\Entity        $item
 * @property Merchant\Entity    $merchant
 * @property Token\Entity       $token
 * @property Task\Entity        $task
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait, ExternalOwner;

    const SOURCE                 = 'source';
    const PLAN_ID                = 'plan_id';
    const CUSTOMER_ID            = 'customer_id';
    const GLOBAL_CUSTOMER        = 'global_customer';
    const CURRENT_PAYMENT_ID     = 'current_payment_id';
    const CURRENT_INVOICE_ID     = 'current_invoice_id';
    const CURRENT_INVOICE_AMOUNT = 'current_invoice_amount';
    const ISSUED_INVOICES_COUNT  = 'issued_invoices_count';
    const CURRENT_START          = 'current_start';
    const CURRENT_END            = 'current_end';
    const STATUS                 = 'status';
    const ENDED_AT               = 'ended_at';
    const ACTIVATED_AT           = 'activated_at';
    const QUANTITY               = 'quantity';
    const TOKEN_ID               = 'token_id';
    const NOTES                  = 'notes';
    const CHARGE_AT              = 'charge_at';
    const START_AT               = 'start_at';
    const END_AT                 = 'end_at';
    const TOTAL_COUNT            = 'total_count';
    const PAID_COUNT             = 'paid_count';
    const AUTH_ATTEMPTS          = 'auth_attempts';
    const ERROR_STATUS           = 'error_status';
    const SCHEDULE_ID            = 'schedule_id';
    const CUSTOMER_NOTIFY        = 'customer_notify';
    const TYPE                   = 'type';
    const CANCEL_AT              = 'cancel_at';
    const CUSTOMER_NAME          = 'customer_name';
    const CUSTOMER_CONTACT       = 'customer_contact';

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
     * Input to signify cancel at cycle end and not immediately
     */
    const CANCEL_AT_CYCLE_END  = 'cancel_at_cycle_end';

    /**
     * This key is used to search in subscriptions fetch multiple
     */
    const CUSTOMER_EMAIL = 'customer_email';

    /**
     * Used in mails for card update links
     */
    const HOSTED_URL = 'hosted_url';

    /**
     * Stores the current recurring type for the subscription.
     * Temporarily required for the subserv flow.
     */
    const RECURRING_TYPE = 'recurring_type';

    /**
     * We throw exceptions in the following cases
     * - In preferences, if the subscription has been authenticated and card_change = false in the input.
     * - In preferences, if card_change = true in the input and it's not card change status.
     * - While adding order_id in the payment input, if card_change = false and subscription not in created state
     *   and order_id is not already present in the input.
     * - While adding order_id, if card_change = true and subscription status is not card change status.
     * - In authorize flow, if card_change = true and app_token is not sent.
     * - In authorize flow, if card_change = true and it's not card change status.
     * - When customer_id is added to input and card_change is true and app_token is not sent.
     */
    const SUBSCRIPTION_CARD_CHANGE = 'subscription_card_change';

    protected static $sign = 'sub';

    protected $entity = 'subscription';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::GLOBAL_CUSTOMER       => 1,
        self::CUSTOMER_EMAIL        => null,
        self::TYPE                  => 0,
        self::NOTES                 => [],
        self::QUANTITY              => 1,
        self::ENDED_AT              => null,
        self::STATUS                => Status::CREATED,
        self::PAID_COUNT            => 0,
        self::AUTH_ATTEMPTS         => 0,
        self::ERROR_STATUS          => null,
        self::ACTIVATED_AT          => null,
        self::FAILED_AT             => null,
        self::CURRENT_START         => null,
        self::CURRENT_END           => null,
        self::TOKEN_ID              => null,
        self::START_AT              => null,
        self::END_AT                => null,
        self::CUSTOMER_NOTIFY       => true,
        self::ISSUED_INVOICES_COUNT => 0,
    ];

    protected static $generators = [
        self::CHARGE_AT,
        self::TYPE,
    ];

    protected static $modifiers = [
        self::START_AT,
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
        // self::CANCEL_AT_CYCLE_END,
    ];

    protected $hosted = [
        self::ID,
        self::STATUS,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::START_AT        => 'int',
        self::END_AT          => 'int',
        self::QUANTITY        => 'int',
        self::CURRENT_START   => 'int',
        self::CURRENT_END     => 'int',
        self::TOTAL_COUNT     => 'int',
        self::PAID_COUNT      => 'int',
        self::AUTH_ATTEMPTS   => 'int',
        self::CUSTOMER_NOTIFY => 'bool',
        self::GLOBAL_CUSTOMER => 'bool',
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
        self::CANCEL_AT_CYCLE_END,
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

    public function getChargeAtAttribute()
    {
        $chargeAt = null;

        //
        // If start_at is null, it means that subscription is still in
        // created state and is of type `immediate`. If the type is immediate,
        // charge_at should be null, since we don't know when to charge.
        // But, task sets a default value to `next_run_at` via modifier.
        //
        if (($this->isCancelled() === false) and
            ($this->isCompleted() === false) and
            ($this->getStartAt() !== null))
        {
            $chargeAt = $this->task->getNextRunAt();
        }

        return $chargeAt;
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
            $invoiceCount--;
        }

        if ($invoiceCount > $this->getTotalCount())
        {
            throw new LogicException(
                'Invoice count more than subscription total count defined',
                ErrorCode::SERVER_ERROR_SUBSCRIPTION_INVOICE_COUNT_MISMATCH_TOTAL_COUNT,
                [
                    'invoice_count'     => $invoiceCount,
                    'subscription_id'   => $this->getId(),
                ]);
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

    public function getCancelAt()
    {
        return $this->getAttribute(self::CANCEL_AT);
    }

    public function getEndedAt()
    {
        return $this->getAttribute(self::ENDED_AT);
    }

    public function getCustomerNotify()
    {
        return $this->getAttribute(self::CUSTOMER_NOTIFY);
    }

    public function getAuthAttempts()
    {
        return $this->getAttribute(self::AUTH_ATTEMPTS);
    }

    public function getTokenId()
    {
        return $this->getAttribute(self::TOKEN_ID);
    }

    public function getCancelAtCycleEnd()
    {
        return $this->getAttribute(self::CANCEL_AT_CYCLE_END);
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

    public function getCurrentPaymentId()
    {
        return $this->getAttribute(self::CURRENT_PAYMENT_ID);
    }

    public function getCurrentInvoiceId()
    {
        return $this->getAttribute(self::CURRENT_INVOICE_ID);
    }

    public function getCurrentInvoiceAmount()
    {
        return $this->getAttribute(self::CURRENT_INVOICE_AMOUNT);
    }

    public function getRecurringType()
    {
        assert($this->isExternal() === true);

        return $this->getAttribute(self::RECURRING_TYPE);
    }

    public function hasBeenAuthenticated()
    {
        return $this->isAttributeNotNull(self::AUTHENTICATED_AT);
    }

    public function hasCurrentInvoice()
    {
        assert($this->isExternal() === true);

        return $this->isAttributeNotNull(self::CURRENT_INVOICE_ID);
    }

    public function isGlobalCustomer()
    {
        return $this->getAttribute(self::GLOBAL_CUSTOMER) === true;
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

    public function isCancelled()
    {
        return ($this->getAttribute(self::STATUS) === Status::CANCELLED);
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

    public function isCardChangeStatus()
    {
        return (in_array($this->getStatus(), Status::$cardChangeStatuses, true) === true);
    }

    public function isManualTestChargeableStatus()
    {
        return (in_array($this->getStatus(), Status::$manualTestChargeableStatuses, true) === true);
    }

    public function isInvoiceManualChargeableStatus()
    {
        return (in_array($this->getStatus(), Status::$invoiceManualChargeableStatuses, true) === true);
    }

    public function isTerminalStatus()
    {
        return (in_array($this->getStatus(), Status::$terminalStatuses, true) === true);
    }

    /**
     * Duplication of the conditions present in getSubscriptionToCharge
     * Used to re-check the conditions before charging a subscription,
     * to avoid race conditions arising from parallel processes
     * @return boolean [description]
     */
    public function isChargeable(): bool
    {
        $currentTime = Carbon::now()->getTimestamp();

        if ($this->getChargeAt() > $currentTime)
        {
            return false;
        }

        if (in_array($this->getStatus(), Status::$cronChargeableStatuses, true) === false)
        {
            return false;
        }

        if (($this->getEndedAt() !== null) or
            ($this->getCancelAt() !== null))
        {
            return false;
        }

        if (($this->getAuthAttempts() !== 0) and
            (($this->getAuthAttempts() !== Charge::MAX_AUTH_ATTEMPTS) or
             ($this->getStatus() !== Status::HALTED)))
        {
            return false;
        }

        if (($this->getCurrentEnd() !== null) and
            ($this->getCurrentEnd() > $currentTime))
        {
            return false;
        }

        return true;
    }

    /**
     * Subscription time fields are only to be updated under certain conditions
     * - Latest invoice of a subscription is being charged
     * -         AND
     * - Subscription is not in terminal/halted state
     *
     * @param  Invoice\Entity $invoice Invoice being charged
     * @return boolean                 Flag to indicate that subscriptions fields are to be updated
     */
    public function shouldUpdateWithInvoiceCharge(Invoice\Entity $invoice)
    {
        $shouldUpdate = $this->isLatestInvoiceForSubscription($invoice);

        //
        // TODO: If the cycle is 1st jan to 1st feb and then 1st feb to 1st march. It moved to halted on 4th feb.
        // Current cycle is still 1st feb to 1st march. latest invoice will also be 1st feb to 1st march.
        // but in case of halted, no invoice is latest. all are old.
        //
        // Subscriptions in terminal state are not to be updated.
        //
        if (($this->isTerminalStatus() === true) or
            ($this->getStatus() === Status::HALTED))
        {
            $shouldUpdate = false;
        }

        return $shouldUpdate;
    }

    /**
     * Checks if invoice is the latest one generated for the subscription.
     * This would be the case if the invoice billing period matches that
     * of the subscription, which is always current.
     *
     * @param  Invoice\Entity $invoice
     * @return boolean
     */
    public function isLatestInvoiceForSubscription(Invoice\Entity $invoice)
    {
        $isLatest = false;

        if (($this->getCurrentStart() === $invoice->getBillingStart()) and
            ($this->getCurrentEnd() === $invoice->getBillingEnd()))
        {
            $isLatest = true;
        }

        return $isLatest;
    }

    public function isGlobal()
    {
        //
        // In case subscription is local, merchant
        // always has to send customer id while creating
        // subscription so customer will never be null in
        // case of local flow
        //
        // In case of global flow initially the customer
        // will be null, but after a payment happens we
        // create a local customer which has global customer
        // associated with it.
        //

        if (($this->isExternal() === true) and
            ($this->token !== null) and
            $this->token->merchant->isShared() === false)
        {
            return false;
        }

        if (($this->isExternal() === true) and
            ($this->isGlobalCustomer() === true))
        {
            return true;
        }

        if ($this->hasCustomer() === false)
        {
            return true;
        }

        return $this->customer->hasGlobalCustomer();
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

    /**
     * Returns the path component of Dashboard view url.
     *
     * For subscription (New): #/app/subscriptions/{public-id}
     *
     * @return string
     */
    public function getDashboardPath(): string
    {
        $path = '#/app/subscriptions/' . $this->getPublicId();

        return $path;
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

    public function getCancelAtCycleEndAttribute()
    {
        return $this->isAttributeNotNull(self::CANCEL_AT);
    }

    // --------------------- END ACCESSORS ---------------------

    // --------------------- SETTERS ---------------------

    public function setGlobalCustomer(bool $isGlobalCustomer)
    {
        $this->setAttribute(self::GLOBAL_CUSTOMER, $isGlobalCustomer);
    }

    public function setCustomerEmail(string $customerEmail = null)
    {
        $this->setAttribute(self::CUSTOMER_EMAIL, $customerEmail);
    }

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

    public function setCancelledAt($cancelledAt)
    {
        $this->setAttribute(self::CANCELLED_AT, $cancelledAt);
    }

    public function setCancelAt($cancelAt)
    {
        $this->setAttribute(self::CANCEL_AT, $cancelAt);
    }

    public function setStatus($status)
    {
        Status::validateStatus($status);

        $this->setAttribute(self::STATUS, $status);

        if (in_array($status, Status::$timestampedStatuses, true) === true)
        {
            $timestampKey = $status . '_at';

            $currentTime = Carbon::now()->getTimestamp();

            $currentTimestamp = $this->getAttribute($timestampKey);

            //
            // In some cases like cancelled_at, the request to
            // cancel the subscription is received at time T1, to
            // cancel the subscription at time T2.
            // We set the cancelled_at to T1, but the state is not changed
            // to `cancelled`. We do not change the timestamp after we
            // actually change the state to `cancelled` at time T2.
            //
            if (empty($currentTimestamp) === true)
            {
                $this->setAttribute($timestampKey, $currentTime);
            }
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

    public function setType(string $type, bool $value)
    {
        $currentHex = $this->getType();

        $newHex = Type::getHexWithTypeMarked($currentHex, $type, $value);

        $this->setTypeHex($newHex);
    }

    public function setCurrentPeriod(array $billingPeriod)
    {
        $this->setCurrentStart($billingPeriod['start']);
        $this->setCurrentEnd($billingPeriod['end']);
    }

    public function resetErrorFields()
    {
        $this->setFailedAt(null);
        $this->setErrorStatus(null);
        $this->resetAuthAttempts();
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

    // ----------------------- MODIFIERS -----------------------

    public function modifyStartAt(& $input)
    {
        if (empty($input[Entity::START_AT]) === true)
        {
            unset($input[self::START_AT]);
        }
    }

    // --------------------- END MODIFIERS ---------------------

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

        if (($this->getStartAt() !== null) and
            (Period::isPeriodAnchored($period) === true))
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
