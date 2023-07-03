<?php

namespace RZP\Models\Dispute;

use App;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Models\Transaction;

class Entity extends Base\PublicEntity
{
    use Base\Traits\RevisionableTrait {
        preSave as traitPreSave;
    }

    const MERCHANT_ID             = 'merchant_id';
    const PARENT_ID               = 'parent_id';
    const PAYMENT_ID              = 'payment_id';
    const TRANSACTION_ID          = 'transaction_id';
    const AMOUNT                  = 'amount';
    const AMOUNT_DEDUCTED         = 'amount_deducted';
    const DEDUCTION_SOURCE_TYPE   = 'deduction_source_type';
    const DEDUCTION_SOURCE_ID     = 'deduction_source_id';
    const DEDUCTION_REVERSAL_AT   = 'deduction_reversal_at';
    const AMOUNT_REVERSED         = 'amount_reversed';
    const CURRENCY                = 'currency';
    const DEDUCT_AT_ONSET         = 'deduct_at_onset';
    const GATEWAY_DISPUTE_ID      = 'gateway_dispute_id';
    const REASON_ID               = 'reason_id';
    const REASON_CODE             = 'reason_code';
    const REASON_DESCRIPTION      = 'reason_description';
    const GATEWAY_DISPUTE_STATUS  = 'gateway_dispute_status';
    const RAISED_ON               = 'raised_on';
    const EXPIRES_ON              = 'expires_on';
    const STATUS                  = 'status';
    const INTERNAL_STATUS         = 'internal_status';
    const INTERNAL_RESPOND_BY     = 'internal_respond_by';
    const PHASE                   = 'phase';
    const COMMENTS                = 'comments';
    const CREATED_AT              = 'created_at';
    const UPDATED_AT              = 'updated_at';
    const RESOLVED_AT             = 'resolved_at';
    const BACKFILL                = 'backfill';
    const LIFECYCLE               = 'lifecycle';
    const GATEWAY                 = 'gateway';
    const NETWORK                 = 'card_network';

    const EMAIL_NOTIFICATION_STATUS = 'email_notification_status';

    // For emails
    const MERCHANT_EMAILS         = 'merchant_emails';
    const SKIP_EMAIL              = 'skip_email';
    const EMAIL                   = 'email';

    // For international disputes
    const BASE_AMOUNT             = 'base_amount';
    const BASE_CURRENCY           = 'base_currency';
    const GATEWAY_AMOUNT          = 'gateway_amount';
    const GATEWAY_CURRENCY        = 'gateway_currency';
    const CONVERSION_RATE         = 'conversion_rate';

    // Filter params constants
    const INTERNAL_RESPOND_BY_FROM      = 'internal_respond_by_from';
    const INTERNAL_RESPOND_BY_TO        = 'internal_respond_by_to';
    const ORDER_BY_INTERNAL_RESPOND     = 'order_by_internal_respond';
    const GATEWAY_DISPUTE_SOURCE        = 'gateway_dispute_source';
    const DEDUCTION_REVERSAL_AT_SET     = 'deduction_reversal_at_set';
    const DEDUCTION_REVERSAL_AT_FROM    = 'deduction_reversal_at_from';
    const DEDUCTION_REVERSAL_AT_TO      = 'deduction_reversal_at_to';

    // Bulk file constants
    const DEDUCTION_REVERSAL_DELAY_IN_DAYS = 'deduction_reversal_delay_in_days';

    /**
     *  Field for edit input, when accepted chargeback amount
     *  is lesser than disputed amount.
     */
    const ACCEPTED_AMOUNT         = 'accepted_amount';

    // Input keys
    const ACCEPT_DISPUTE          = 'accept_dispute';
    const SUBMIT                  = 'submit';

    // Output attributes
    const FILES                   = 'files';
    const RESPOND_BY              = 'respond_by';

    // For expands
    const PAYMENT                 = 'payment';
    const REASON                  = 'reason';
    const MERCHANT                = 'merchant';
    const TRANSACTION             = 'transaction';
    const EVIDENCE                = 'evidence';

    const SKIP_DEDUCTION  = 'skip_deduction';
    const RECOVERY_METHOD = 'recovery_method';

    const CONTACT                 = 'contact';

    const DISPUTE_PRECISION_FACTOR = 1000000;
    const DEDUCTION_SOURCE_TYPE_LENGTH = 30;

    const DEDUCTION_SOURCE_ID_LENGTH   = 14;


    //Lifecycle related constants
    const ADMIN_ID      = 'admin_id';
    const USER_ID       = 'user_id';
    const AUTH_TYPE     = 'auth_type';
    const CHANGE        = 'change';
    const APP           = 'app';
    const LIFECYCLE_NEW = 'new';
    const LIFECYCLE_OLD = 'old';

    private $backfill = false;

    protected static $sign = 'disp';

    protected $entity = 'dispute';

    protected $generateIdOnCreate = true;

    protected $revisionCreationsEnabled = true;

    protected $revisionEnabled = true;

    protected $fillable = [
        self::ID,
        self::AMOUNT,
        self::GATEWAY_AMOUNT,
        self::GATEWAY_CURRENCY,
        self::GATEWAY_DISPUTE_ID,
        self::GATEWAY_DISPUTE_STATUS,
        self::DEDUCT_AT_ONSET,
        self::REASON_CODE,
        self::REASON_DESCRIPTION,
        self::RAISED_ON,
        self::EXPIRES_ON,
        self::STATUS,
        self::INTERNAL_STATUS,
        self::INTERNAL_RESPOND_BY,
        self::PHASE,
        self::AMOUNT_DEDUCTED,
        self::AMOUNT_REVERSED,
        self::COMMENTS,
        self::EMAIL_NOTIFICATION_STATUS,
        self::DEDUCTION_SOURCE_ID,
        self::DEDUCTION_SOURCE_TYPE,
        self::DEDUCTION_REVERSAL_AT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::PAYMENT,
        self::PARENT_ID,
        self::REASON_ID,
        self::TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::BASE_AMOUNT,
        self::BASE_CURRENCY,
        self::GATEWAY_AMOUNT,
        self::GATEWAY_CURRENCY,
        self::CONVERSION_RATE,
        self::AMOUNT_DEDUCTED,
        self::DEDUCTION_SOURCE_TYPE,
        self::DEDUCTION_SOURCE_ID,
        self::DEDUCTION_REVERSAL_AT,
        self::AMOUNT_REVERSED,
        self::DEDUCT_AT_ONSET,
        self::GATEWAY_DISPUTE_ID,
        self::GATEWAY_DISPUTE_STATUS,
        self::REASON_CODE,
        self::REASON_DESCRIPTION,
        self::RAISED_ON,
        self::EXPIRES_ON,
        self::STATUS,
        self::INTERNAL_STATUS,
        self::INTERNAL_RESPOND_BY,
        self::PHASE,
        self::COMMENTS,
        self::EMAIL_NOTIFICATION_STATUS,
        self::EVIDENCE,
        self::LIFECYCLE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::RESOLVED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::AMOUNT_DEDUCTED,
        self::GATEWAY_DISPUTE_ID,
        self::REASON_CODE,
        self::REASON_DESCRIPTION,
        self::RESPOND_BY,
        self::STATUS,
        self::PHASE,
        self::COMMENTS,
        self::EVIDENCE,
        self::LIFECYCLE,
        self::CREATED_AT,
        self::REASON,
    ];

    protected $expanded = [
        self::TRANSACTION,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::AMOUNT_DEDUCTED,
        self::RESPOND_BY,
        self::REASON_DESCRIPTION,
        self::EVIDENCE,
        self::REASON,
        self::LIFECYCLE,
    ];

    protected $casts = [
        self::AMOUNT                => 'int',
        self::BASE_AMOUNT           => 'int',
        self::GATEWAY_AMOUNT        => 'int',
        self::AMOUNT_DEDUCTED       => 'int',
        self::AMOUNT_REVERSED       => 'int',
        self::DEDUCT_AT_ONSET       => 'bool',
        self::LIFECYCLE             => 'json',
        self::DEDUCTION_REVERSAL_AT => 'int',
    ];

    protected $guarded = [self::ID];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::RESOLVED_AT,
        self::RAISED_ON,
        self::EXPIRES_ON,
        self::RESPOND_BY,
        self::INTERNAL_RESPOND_BY,
        self::DEDUCTION_REVERSAL_AT,
    ];

    protected $defaults = [
        self::STATUS                => Status::OPEN,
        self::INTERNAL_STATUS       => InternalStatus::OPEN,
        self::DEDUCT_AT_ONSET       => false,
        self::AMOUNT_DEDUCTED       => 0,
        self::AMOUNT_REVERSED       => 0,
        self::DEDUCTION_SOURCE_TYPE => null,
        self::DEDUCTION_SOURCE_ID   => null,
        self::DEDUCTION_REVERSAL_AT => null,
    ];

    protected static $generators = [
        self::BASE_AMOUNT,
        self::AMOUNT,
        self::CURRENCY,
        self::EMAIL_NOTIFICATION_STATUS,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::AMOUNT_REVERSED,
        self::AMOUNT_DEDUCTED,
    ];

    public function preSave()
    {
        $this->traitPreSave();

        try
        {
            $this->updateLifecycleIfApplicable();
        }
        catch (\Exception $exception)
        {
            $this->getTrace()->traceException($exception);
        }

    }

    // ----------------------- Generators --------------------------------------

    public function generateBaseAmount($input)
    {
        if (isset($input['gateway_amount']) === false)
        {
            return;
        }

        $baseAmount = (new Currency\Core)->getBaseAmount(
                                $input[self::GATEWAY_AMOUNT],
                                $input[self::GATEWAY_CURRENCY],
                                $this->merchant->getCurrency());

        // 1% markup charged only when currency conversion happens.
        if ($input[self::GATEWAY_CURRENCY] !== Currency\Currency::INR)
        {
            $baseAmount = $baseAmount * (1.01);
        }

        if (($this->payment->getCurrency() === Currency\Currency::INR) and
            ($baseAmount > $this->payment->getBaseAmountUnrefunded()))
        {
            $baseAmount = $this->payment->getBaseAmountUnrefunded();
        }

        $this->setAttribute(self::BASE_AMOUNT, $baseAmount);
        $this->setAttribute(self::BASE_CURRENCY, Currency\Currency::INR);
    }

    public function generateAmount($input)
    {
        if (isset($input['gateway_amount']) === false)
        {
            return;
        }

        $gatewayCurrency = $input[self::GATEWAY_CURRENCY];
        $paymentCurrency = $this->payment->getCurrency();

        $disputeAmount = $input[self::GATEWAY_AMOUNT];
        $conversionRate = null;

        if ($gatewayCurrency !== $paymentCurrency)
        {
            $conversionRate = (new Currency\Core)->getConversionRate($gatewayCurrency, $paymentCurrency);

            $conversionRate = (int) ceil($conversionRate * self::DISPUTE_PRECISION_FACTOR);

            $disputeAmount = (new Currency\Core)->convertAmount(
                            $input[self::GATEWAY_AMOUNT],
                            $gatewayCurrency,
                            $paymentCurrency);

            $disputeAmount = $disputeAmount * (1.01);
        }

        if ($disputeAmount > $this->payment->getAmountUnrefunded())
        {
            $disputeAmount = $this->payment->getAmountUnrefunded();
        }

        $this->setAttribute(self::AMOUNT, $disputeAmount);
        $this->setAttribute(self::CONVERSION_RATE, $conversionRate);
    }

    public function generateCurrency($input)
    {
        $this->setAttribute(self::CURRENCY, $this->payment->getCurrency());
    }

    public function toDualWriteArray() : array
    {
        $array = $this->toArray();

        unset($array[self::PAYMENT]);
        unset($array[self::EVIDENCE]);
        unset($array[self::REASON_DESCRIPTION]);
        unset($array[self::REASON_CODE]);

        return $array;
    }


    protected function generateEmailNotificationStatus($input)
    {
        if(empty($input[Entity::SKIP_EMAIL]) === true && $input[Entity::BACKFILL] === false)
        {
            $emailNotificationStatus = EmailNotificationStatus::SCHEDULED;
        }
        else
        {
            $emailNotificationStatus = EmailNotificationStatus::DISABLED;
        }

        $this->setAttribute(self::EMAIL_NOTIFICATION_STATUS, $emailNotificationStatus);
    }

    // ----------------------- Generators Ends----------------------------------

    // ----------------------- Setters -----------------------------------------

    public function setAmountDeducted(int $amount)
    {
        $this->setAttribute(self::AMOUNT_DEDUCTED, $amount);
    }

    public function setDeductionSourceType($deductionSourceType)
    {
        $this->setAttribute(self::DEDUCTION_SOURCE_TYPE, $deductionSourceType);
    }

    public function setDeductionSourceId($deductionSourceId)
    {
        $this->setAttribute(self::DEDUCTION_SOURCE_ID, $deductionSourceId);
    }

    public function resetDeductionSourceAttributes()
    {
        $this->setDeductionSourceId(null);

        $this->setDeductionSourceType(null);
    }

    public function setPaymentId($paymentId)
    {
        $this->setAttribute(self::PAYMENT_ID, $paymentId);
    }

    public function setAmountReversed(int $amount)
    {
        $this->setAttribute(self::AMOUNT_REVERSED, $amount);
    }

    public function setAmount(int $amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setCurrency(string $currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setGatewayAmount(int $amount)
    {
        $this->setAttribute(self::GATEWAY_AMOUNT, $amount);
    }

    public function setGatewayCurrency(string $currency)
    {
        $this->setAttribute(self::GATEWAY_CURRENCY, $currency);
    }

    public function setBaseAmount(int $amount)
    {
        $this->setAttribute(self::BASE_AMOUNT, $amount);
    }

    public function setBaseCurrency(int $currency)
    {
        $this->setAttribute(self::BASE_CURRENCY, $currency);
    }

    public function setReasonDescription(string $description)
    {
        $this->setAttribute(self::REASON_DESCRIPTION, $description);
    }

    public function setReasonCode(string $code)
    {
        $this->setAttribute(self::REASON_CODE, $code);
    }

    public function setResolvedAt(int $time)
    {
        $this->setAttribute(self::RESOLVED_AT, $time);
    }

    public function setDeductionReversalAt($deductionReversalAt)
    {
        $this->setAttribute(self::DEDUCTION_REVERSAL_AT, $deductionReversalAt);
    }

    public function setExpiresOn(int $time)
    {
        $this->setAttribute(self::EXPIRES_ON, $time);
    }

    public function setInternalStatus(string $internalStatus)
    {
        $this->setAttribute(self::INTERNAL_STATUS, $internalStatus);
    }

    public function setPublicPaymentIdAttribute(array &$attributes)
    {
        $attributes[self::PAYMENT_ID] =
            Payment\Entity::getSignedId($this->getAttribute(self::PAYMENT_ID));
    }

    public function setPublicAmountDeductedAttribute(array &$attributes)
    {
        $netAmount = abs($this->getAmountDeducted() - $this->getAmountReversed());

        $attributes[self::AMOUNT_DEDUCTED] = $netAmount;
    }

    public function setPublicRespondByAttribute(array &$attributes)
    {
        $attributes[self::RESPOND_BY] = (int) $this->getExpiresOn();
    }

    public function setPublicEvidenceAttribute(array &$attributes)
    {
        if ($this->isEnabledForDisputePresentment() === false)
        {
            return;
        }

        if ($this->evidence === null)
        {
            return;
        }

        $attributes[self::EVIDENCE] = $this->evidence->toArrayPublic();
    }

    public function setPublicLifecycleAttribute(array &$attributes)
    {
        $app = App::getFacadeRoot();

        $basicAuth = $app['basicauth'];

        if ($basicAuth->isProxyAuth() === true)
        {
            $attributes[self::LIFECYCLE] = $this->getLifecyclePublic();

            return;
        }

        if ($basicAuth->isAdminAuth() === true)
        {
            $attributes[self::LIFECYCLE] = $this->getLifecycle();

            return;
        }

        unset($attributes[self::LIFECYCLE]);
    }

    public function setComments(string $comments = null)
    {
        $this->setAttribute(self::COMMENTS, $comments);
    }

    public function setPublicReasonAttribute(array &$attributes)
    {
        $app = App::getFacadeRoot();

        $basicAuth = $app['basicauth'];

        if ($basicAuth->isProxyAuth() === false)
        {
            unset($attributes[self::REASON]);

            return;
        }

        $attributes[self::REASON] = $this->reason->toArrayPublic();
    }

    public function setPublicReasonDescriptionAttribute(array &$attributes)
    {
        $app = App::getFacadeRoot();

        $basicAuth = $app['basicauth'];

        //
        // Attr reason_description to be sent only for dashboard
        // TODO: Remove after entity serializer
        //
        if ($basicAuth->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::REASON_DESCRIPTION]);
        }
    }

    protected function setLifecycle(array $lifecycle)
    {
        $this->setAttribute(self::LIFECYCLE, $lifecycle);
    }

    // ----------------------- Setters Ends-------------------------------------

    // ------------ Mutators Starts -------------------

    protected function setPhaseAttribute($phase)
    {
        $this->attributes[self::PHASE] = strtolower($phase);
    }

    protected function updateLifecycleIfApplicable()
    {
        $ba = App::getFacadeRoot()['basicauth'];

        if ($this->shouldUpdateLifecycle() === false)
        {
            return;
        }

        $baAdmin = $ba->getAdmin();

        $baMerchantId = $ba->getMerchantId();

        $baUser = $ba->getUser();

        $lifecycleEntry = [
            self::ADMIN_ID      => $baAdmin === null ? null : $baAdmin->getId(),
            self::MERCHANT_ID   => $baMerchantId,
            self::USER_ID       => $baUser === null ? null : $baUser ->getId(),
            self::AUTH_TYPE     => $ba->getAuthType(),
            self::APP           => $ba->getInternalApp() ?? null,
            self::CHANGE        => $this->getChangesForLifecycle(),
            self::CREATED_AT    => time(),
        ];

        $this->getTrace()->info(TraceCode::DISPUTE_LIFECYCLE_APPEND, $lifecycleEntry);

        $this->appendEntryToLifecycle($lifecycleEntry);
    }

    // ------------ Mutators Ends ---------------------

    // ----------------------- Getters -----------------------------------------

    public function getParentId()
    {
        return $this->getAttribute(self::PARENT_ID);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getAmountDeducted()
    {
        return $this->getAttribute(self::AMOUNT_DEDUCTED);
    }

    public function getAmountReversed()
    {
        return $this->getAttribute(self::AMOUNT_REVERSED);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getGatewayAmount()
    {
        return $this->getAttribute(self::GATEWAY_AMOUNT);
    }

    public function getGatewayCurrency()
    {
        return $this->getAttribute(self::GATEWAY_CURRENCY);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    public function getBaseCurrency()
    {
        return $this->getAttribute(self::BASE_CURRENCY);
    }

    public function getConversionRate()
    {
        return $this->getAttribute(self::CONVERSION_RATE);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getInternalStatus()
    {
        return $this->getAttribute(self::INTERNAL_STATUS);
    }

    public function getPhase()
    {
        return $this->getAttribute(self::PHASE);
    }

    public function getExpiresOn()
    {
        return $this->getAttribute(self::EXPIRES_ON);
    }

    public function getResolvedAt()
    {
        return $this->getAttribute(self::RESOLVED_AT);
    }

    public function getRaisedOn()
    {
        return $this->getAttribute(self::RAISED_ON);
    }

    public function getDeductAtOnset()
    {
        return $this->getAttribute(self::DEDUCT_AT_ONSET);
    }

    public function getReasonCode()
    {
        return $this->getAttribute(self::REASON_CODE);
    }

    public function getDeductionSourceId()
    {
        return $this->getAttribute(self::DEDUCTION_SOURCE_ID);
    }

    public function isChildDispute(): bool
    {
        return $this->isAttributeNotNull(self::PARENT_ID);
    }

    public function getComments()
    {
        return $this->getAttribute(self::COMMENTS);
    }

    public function getGatewayDisputeId()
    {
        return $this->getAttribute(self::GATEWAY_DISPUTE_ID);
    }

    protected function getLifecycle() : array
    {
        return $this->getAttribute(self::LIFECYCLE) ?? [];
    }

    protected function getLifecyclePublic() : array
    {
        $lifecycle =  $this->getLifecycle();

        $result = [];

        foreach ($lifecycle as $entry)
        {
            $isPublic = $this->isPublicLifecycleEntry($entry);

            if ($isPublic === false)
            {
                continue;
            }

            $entry = $this->filterNonPublicAttributesFromLifecycleEntry($entry);

            $result[] = $entry;
        }

        return $result;
    }


    // ----------------------- Getters Ends-------------------------------------

    // --------------- Relation to other entities ------------------------------

    public function payment()
    {
        return $this->belongsTo(Payment\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function parent()
    {
        return $this->belongsTo(Entity::class, self::PARENT_ID, self::ID);
    }

    public function child()
    {
        return $this->hasOne(Entity::class, self::PARENT_ID, self::ID);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction\Entity::class);
    }

    public function reason()
    {
        return $this->belongsTo(Reason\Entity::class);
    }

    public function adjustments()
    {
        return $this->morphMany(Adjustment\Entity::class, 'entity');
    }

    public function evidence()
    {
        return $this->hasOne(Evidence\Entity::class);
    }

    // --------------- Relation to other entity section ends --------------------

    public function associateReason($reason)
    {
        $this->reason()->associate($reason);

        $this->setReasonCode($reason->getCode());

        $this->setReasonDescription($reason->getDescription());
    }

    public function isClosed(): bool
    {
        return (in_array($this->getStatus(), Status::getClosedStatuses(), true) === true);
    }

    public function isLost(): bool
    {
        return ($this->getStatus() === Status::LOST);
    }

    public function isWon(): bool
    {
        return ($this->getStatus() === Status::WON);
    }

    public function hasMerchantAcceptedStatus(bool $acceptDispute): bool
    {
        return (($acceptDispute === true) and
                (in_array($this->getStatus(), Status::getMerchantAcceptedStatuses(), true) === true));
    }

    public function isNonTransactional(): bool
    {
        $nonTransactionalPhases = Phase::getNonTransactionalPhases();

        return (in_array($this->getPhase(), $nonTransactionalPhases, true) === true);
    }

    public function toArrayAdmin()
    {
        $array = parent::toArrayAdmin();

        $array[self::AMOUNT_DEDUCTED] = $this->getAmountDeducted();

        $disputeId = ltrim($array[self::ID], 'disp_');

        $array[self::GATEWAY] = (new \RZP\Models\Payment\Repository)->getDisputeGateway($disputeId);

        $array[self::NETWORK] = (new \RZP\Models\Card\Repository)->getCardNetwork($disputeId);

        return $array;
    }

    public function setBackfill(bool $val)
    {
        $this->backfill = $val;
    }

    public function isBackfill(): bool
    {
        return $this->backfill;
    }

    public function getPaymentAttribute()
    {
        if (empty($this->payment()->first()) === false)
        {
            return $this->payment()->first();
        }

        $payment = (new Payment\Repository)->findOrFailPublic($this->getPaymentId());

        $this->payment()->associate($payment);

        return $payment;
    }

    protected function isEnabledForDisputePresentment() : bool
    {
        if ($this->merchant === null)
        {
            return false;
        }
        return ($this->merchant->isFeatureEnabled(Feature\Constants::EXCLUDE_DISPUTE_PRESENTMENT) === false);
    }

    public function isCustomerDispute() : bool
    {
        $gatewayDisputeId = $this->getGatewayDisputeId() ?? '';

        if ((strlen($gatewayDisputeId) <= 7) or
            (substr($gatewayDisputeId, 0, 7) !== Constants::CUSTOMER_DISPUTE_GATEWAY_DISPUTE_ID_PREFIX))
        {
            return false;
        }

        return true;
    }

    protected function shouldUpdateLifecycle(): bool
    {
        if (empty($this->getDirty()) === true)
        {
            return false;
        }

        return true;
    }

    protected function appendEntryToLifecycle(array $entry)
    {
        $lifecycle = $this->getLifecycle();

        array_push($lifecycle, $entry);

        $this->setLifecycle($lifecycle);
    }

    protected function getChangesForLifecycle() : array
    {
        if ($this->exists === false)
        {
            return [
                self::LIFECYCLE_OLD       => null,
                self::LIFECYCLE_NEW       => $this->getFormattedRevisionsForPostCreate()['entity']['change']['new'] ?? null,
            ];
        }

        if ($this->updating === true)
        {
            $formattedRevisions = $this->getFormattedRevisionsForPostSave();

            return [
                self::LIFECYCLE_OLD   => $formattedRevisions['entity']['change']['old'],
                self::LIFECYCLE_NEW   => $formattedRevisions['entity']['change']['new'],
            ];
        }

    }

    protected function isPublicLifecycleEntry($entry): bool
    {
        // actions performed by admins should not be shown to merchant
        if (isset($entry[self::ADMIN_ID]) === true)
        {
            return false;
        }

        // dispute creation irrespective of source should not be shown to merchant
        if ($entry[self::CHANGE][self::LIFECYCLE_OLD] === null)
        {
            return false;
        }

        // dispute actions from apps/integrations should not be shown
        if (isset($entry[self::MERCHANT_ID]) === false)
        {
            return false;
        }

        return true;
    }

    private function filterNonPublicAttributesFromLifecycleEntry($entry)
    {
        unset($entry[self::ADMIN_ID], $entry[self::AUTH_TYPE], $entry[self::APP]);

        $filter =  function ($value, $key)
        {
            return in_array($key, $this->public) == true;
        };

        $entry[self::CHANGE][self::LIFECYCLE_NEW] = array_filter($entry[self::CHANGE][self::LIFECYCLE_NEW], $filter, ARRAY_FILTER_USE_BOTH);

        $entry[self::CHANGE][self::LIFECYCLE_OLD] = array_filter($entry[self::CHANGE][self::LIFECYCLE_OLD], $filter, ARRAY_FILTER_USE_BOTH);

        return $entry;
    }
}
