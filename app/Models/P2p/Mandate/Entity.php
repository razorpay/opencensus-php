<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\Customer;
use RZP\Models\P2p\BankAccount;

/**
 * Class Entity
 *
 * @property Vpa\Entity $payer
 * @property Vpa\Entity $payee
 * @property UpiMandate\Entity $upi
 * @property Patch\Entity $patch
 *
 * @package RZP\Models\P2p\Mandate
 */
class Entity extends Base\Entity
{
    use Base\Traits\HasDevice;
    use Base\Traits\HasHandle;
    use Base\Traits\HasMerchant;
    use Base\Traits\HasBankAccount;

    const NAME                = 'name';
    const DEVICE_ID           = 'device_id';
    const MERCHANT_ID         = 'merchant_id';
    const CUSTOMER_ID         = 'customer_id';
    const HANDLE              = 'handle';
    const AMOUNT              = 'amount';
    const AMOUNT_RULE         = 'amount_rule';
    const CURRENCY            = 'currency';
    const PAYER_ID            = 'payer_id';
    const PAYEE_ID            = 'payee_id';
    const BANK_ACCOUNT_ID     = 'bank_account_id';
    const TYPE                = 'type';
    const FLOW                = 'flow';
    const MODE                = 'mode';
    const RECURRING_TYPE      = 'recurring_type';
    const RECURRING_VALUE     = 'recurring_value';
    const RECURRING_RULE      = 'recurring_rule';
    const UMN                 = 'umn';
    const STATUS              = 'status';
    const INTERNAL_STATUS     = 'internal_status';
    const START_DATE          = 'start_date';
    const END_DATE            = 'end_date';
    const ACTION              = 'action';
    const DESCRIPTION         = 'description';
    const GATEWAY             = 'gateway';
    const INTERNAL_ERROR_CODE = 'internal_error_code';
    const ERROR_CODE          = 'error_code';
    const ERROR_DESCRIPTION   = 'error_description';
    const COMPLETED_AT        = 'completed_at';
    const EXPIRE_AT           = 'expire_at';
    const REVOKED_AT          = 'revoked_at';
    const UNPAUSED_AT         = 'unpaused_at';
    const CYCLES_COMPLETED    = 'cycles_completed';
    const PAUSE_START         = 'pause_start';
    const PAUSE_END           = 'pause_end';
    const PAYER_TYPE          = 'payer_type';
    const PAYEE_TYPE          = 'payee_type';

    /************** Input  Properties ************/

    const MANDATE            = 'mandate';
    const CUSTOMER           = 'customer';
    const PAYER              = 'payer';
    const PAYEE              = 'payee';
    const PATCH              = 'patch';
    const BANK_ACCOUNT       = 'bank_account';
    const UPI                = 'upi';

    /************** Entity Properties ************/

    protected $entity               = 'p2p_mandate';
    protected static $sign          = 'cmdt';
    protected $generateIdOnCreate   = true;
    protected static $generators    = [];

    protected $dates = [
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::EXPIRE_AT,
        Entity::CREATED_AT,
        Entity::COMPLETED_AT,
        Entity::EXPIRE_AT,
        Entity::UPDATED_AT,
        Entity::PAUSE_START,
        Entity::PAUSE_END
    ];

    protected $fillable = [
        Entity::NAME,
        Entity::TYPE,
        Entity::FLOW,
        Entity::MODE,
        Entity::AMOUNT,
        Entity::AMOUNT_RULE,
        Entity::CURRENCY,
        Entity::PAYER_ID,
        Entity::PAYEE_ID,
        Entity::PAYER_TYPE,
        Entity::PAYEE_TYPE,
        Entity::BANK_ACCOUNT_ID,
        Entity::RECURRING_TYPE,
        Entity::RECURRING_VALUE,
        Entity::RECURRING_RULE,
        Entity::EXPIRE_AT,
        Entity::ACTION,
        Entity::UMN,
        Entity::STATUS,
        Entity::INTERNAL_STATUS,
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::DESCRIPTION,
        Entity::GATEWAY,
        Entity::GATEWAY_DATA,
        Entity::COMPLETED_AT,
        Entity::REVOKED_AT,
        Entity::CYCLES_COMPLETED,
        Entity::PAUSE_START,
        Entity::PAUSE_END,
        Entity::ERROR_CODE,
        Entity::INTERNAL_ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::PAUSE_START,
        Entity::PAUSE_END,
    ];

    protected $visible = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::MERCHANT_ID,
        Entity::CUSTOMER_ID,
        Entity::AMOUNT,
        Entity::AMOUNT_RULE,
        Entity::CURRENCY,
        Entity::GATEWAY,
        Entity::PAYER_ID,
        Entity::PAYEE_ID,
        Entity::PAYER_TYPE,
        Entity::PAYEE_TYPE,
        Entity::BANK_ACCOUNT_ID,
        Entity::CUSTOMER,
        Entity::PAYER,
        Entity::PAYEE,
        Entity::TYPE,
        Entity::FLOW,
        Entity::MODE,
        Entity::RECURRING_TYPE,
        Entity::RECURRING_VALUE,
        Entity::RECURRING_RULE,
        Entity::UMN,
        Entity::STATUS,
        Entity::INTERNAL_STATUS,
        Entity::EXPIRE_AT,
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::ACTION,
        Entity::DESCRIPTION,
        Entity::GATEWAY_DATA,
        Entity::ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::INTERNAL_ERROR_CODE,
        Entity::COMPLETED_AT,
        Entity::EXPIRE_AT,
        Entity::REVOKED_AT,
        Entity::CYCLES_COMPLETED,
        Entity::PAUSE_START,
        Entity::PAUSE_END,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $public = [
        Entity::ENTITY,
        Entity::ID,
        Entity::AMOUNT,
        Entity::AMOUNT_RULE,
        Entity::CURRENCY,
        Entity::PAYER_ID,
        Entity::PAYEE_ID,
        Entity::PAYER_TYPE,
        Entity::PAYEE_TYPE,
        Entity::TYPE,
        Entity::FLOW,
        Entity::RECURRING_TYPE,
        Entity::RECURRING_VALUE,
        Entity::RECURRING_RULE,
        Entity::STATUS,
        Entity::EXPIRE_AT,
        Entity::CREATED_AT,
        Entity::COMPLETED_AT,
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::DESCRIPTION,
        Entity::ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::UMN,
        Entity::REVOKED_AT,
        Entity::CYCLES_COMPLETED,
        Entity::PAUSE_START,
        Entity::PAUSE_END,
    ];


    protected $defaults = [
        Entity::NAME                => '',
        Entity::AMOUNT_RULE         => '',
        Entity::PAYER_ID            => '',
        Entity::PAYEE_ID            => '',
        Entity::PAYER_TYPE          => '',
        Entity::PAYEE_TYPE          => '',
        Entity::STATUS              => '',
        Entity::GATEWAY             => '',
        Entity::UMN                 => '',
    ];

    protected $casts = [
        Entity::ID                   => 'string',
        Entity::MERCHANT_ID          => 'string',
        Entity::CUSTOMER_ID          => 'string',
        Entity::PAYER_TYPE           => 'string',
        Entity::PAYER_ID             => 'string',
        Entity::PAYEE_TYPE           => 'string',
        Entity::PAYEE_ID             => 'string',
        Entity::BANK_ACCOUNT_ID      => 'string',
        Entity::TYPE                 => 'string',
        Entity::FLOW                 => 'string',
        Entity::MODE                 => 'string',
        Entity::AMOUNT               => 'int',
        Entity::CURRENCY             => 'string',
        Entity::DESCRIPTION          => 'string',
        Entity::GATEWAY              => 'string',
        Entity::STATUS               => 'string',
        Entity::INTERNAL_STATUS      => 'string',
        Entity::ERROR_CODE           => 'string',
        Entity::ERROR_DESCRIPTION    => 'string',
        Entity::INTERNAL_ERROR_CODE  => 'string',
        Entity::RECURRING_TYPE       => 'string',
        Entity::RECURRING_RULE       => 'string',
        Entity::RECURRING_VALUE      => 'int',
        Entity::COMPLETED_AT         => 'int',
        Entity::EXPIRE_AT            => 'int',
        Entity::REVOKED_AT           => 'int',
        Entity::CYCLES_COMPLETED     => 'int',
        Entity::PAUSE_START          => 'int',
        Entity::PAUSE_END            => 'int',
        Entity::CREATED_AT           => 'int',
        Entity::UPDATED_AT           => 'int',
    ];

    /**
     * @return \RZP\Models\P2p\Mandate\Entity
     */
    public function setStatus(string $status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    /**
     * @return \RZP\Models\P2p\Mandate\Entity
     */
    public function setInternalStatus(string $internalStatus)
    {
        $this->setStatus($internalStatus);

        return $this->setAttribute(self::INTERNAL_STATUS, $internalStatus);
    }

    /**
     * @return string self::INTERNAL_STATUS
     */
    public function getInternalStatus()
    {
        return $this->getAttribute(self::INTERNAL_STATUS);
    }

    /**
     * This is the method to mark the internal statuses of mandate to be authroized
     */
    public function markApproved()
    {
        $this->setInternalStatus(Status::APPROVED);
    }

    /**
     * This is the method to check if the status is marked as completed
     * @return bool
     */
    public function isApproved(): bool
    {
        return in_array($this->getInternalStatus(), [Status::APPROVED]);
    }

    /**
     * This is the method to mark the internal statuses of mandate to be authroized
     */
    public function markCompleted()
    {
        $this->setInternalStatus(Status::COMPLETED);
        $this->setAttribute(self::COMPLETED_AT, $this->freshTimestamp());
    }


    /**
     * This is the method to mark the internal statuses of mandate to be rejected
     */
    public function markRejected()
    {
        $this->setInternalStatus(Status::REJECTED);
    }

    /**
     * This is the method to check if the status is marked as completed
     * @return bool
     */
    public function isCompleted(): bool
    {
        return in_array($this->getInternalStatus(), [Status::COMPLETED]);
    }

    /**
     * This is the method to check if mandate statuses is failed
     * @return bool
     */
    public function isFailed(): bool
    {
        return in_array($this->getInternalStatus(), [Status::FAILED, Status::REJECTED, Status::EXPIRED]);
    }

    /**
     * This is the method to mark the internal status of mandate to be unpaused
     */
    public function markRevoked()
    {
        $this->setInternalStatus(Status::REVOKED);
        $this->setStatus(Status::REVOKED);
        $this->setAttribute(self::REVOKED_AT, $this->freshTimestamp());
    }

    /**
     * This is the method to check if mandate statuses is failed
     * @return bool
     */
    public function isPaused(): bool
    {
        return in_array($this->getInternalStatus(), [Status::PAUSED]);
    }

    /**
     * This is the method to mark the internal status of mandate to be paused
     */
    public function markPaused()
    {
        $this->setInternalStatus(Status::PAUSED);
    }

    /**
     * This is the method to mark the internal status of mandate to be paused
     */
    public function markRequested()
    {
        $this->setInternalStatus(Status::REQUESTED);
    }
    /**
     * @return $this
     */
    public function setErrorCode(string $errorCode)
    {
        return $this->setAttribute(self::ERROR_CODE, $errorCode);
    }

    /**
     * @return $this
     */
    public function setErrorDescription(string $errorDescription)
    {
        return $this->setAttribute(self::ERROR_DESCRIPTION, $errorDescription);
    }

    /**
     * @return $this
     */
    public function setInternalErrorCode(string $internalErrorCode)
    {
        return $this->setAttribute(self::INTERNAL_ERROR_CODE, $internalErrorCode);
    }

    /**
     *  This is the method to check if the status is marked as revoked
     * @return bool
     */
    public function isRevoked(): bool
    {
        return in_array($this->getInternalStatus(), [Status::REVOKED]);
    }

    /**
     *  This is the method to check if the status is marked as Requested
     * @return bool
     */
    public function isRequested(): bool
    {
        return in_array($this->getInternalStatus(), [Status::REQUESTED]);
    }

    /**
     *  This is the method to check if the status is marked as Requested
     * @return bool
     */
    public function isRejected(): bool
    {
        return in_array($this->getInternalStatus(), [Status::REJECTED]);
    }

    /***************** SETTERS *****************/

    /**
     * @param array $array
     */
    public function setPublicEntityAttribute(array & $array)
    {
        $array[self::ENTITY] = 'customer.mandate';
    }

    /**
     * @param Vpa\Entity $payer
     *
     * @return mixed|Entity
     */
    public function setPayer(Vpa\Entity $payer)
    {
        return $this->setAttribute(self::PAYER, $payer);
    }

    /**
     * @param Vpa\Entity $payee
     *
     * @return mixed|Entity
     */
    public function setPayee(Vpa\Entity $payee)
    {
        return $this->setAttribute(self::PAYEE, $payee);
    }

    /**
     * @param BankAccount\Entity $bankAccount
     *
     * @return mixed|Entity
     */
    public function setBankAccount(BankAccount\Entity $bankAccount)
    {
        return $this->setAttribute(self::BANK_ACCOUNT, $bankAccount);
    }

    /**
     * @param Customer\Entity $customer
     *
     * @return mixed|Entity
     */
    public function setCustomer(Customer\Entity $customer)
    {
        return $this->setAttribute(self::CUSTOMER, $customer);
    }

    /**
     * @param UpiMandate\Entity $upi
     *
     * @return mixed|Entity
     */
    public function setUpi(UpiMandate\Entity $upi)
    {
        return $this->setAttribute(self::UPI, $upi);
    }

    /***************** GETTERS *****************/

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        return $this->getAttribute(self::CUSTOMER);
    }

    public function toArrayPublic()
    {
        $array = parent::toArrayPublic();

        $array[self::UPI] = $this->upi->toArrayPublic();

        $array[self::PAYER] = $this->payer->toArrayBeneficiary();

        $array[self::PAYEE] = $this->payee->toArrayBeneficiary();

        $array[self::PATCH] = $this->patch->toArrayPublic();

        return $array;
    }

    /**
     * This is the method to get customer
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    /**
     * This is the method to asscociate customer association
     * @param Customer\Entity $handle
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associateCustomer(Customer\Entity $handle)
    {
        return $this->customer()->associate($handle);
    }

    /***
     * This is the method to get payer details
     * @return mixed
     */
    public function payer()
    {
        return $this->morphTo(self::PAYER)->withTrashed();
    }

    /**
     * This is the method to get payee details
     * @return mixed
     */
    public function payee()
    {
        return $this->morphTo(self::PAYEE)->withTrashed();
    }

    /**
     * This is the method to get bank account details
     * @return mixed
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount\Entity::class)->withTrashed();
    }

    /**
     * This is the method to get upi details
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function upi()
    {
        return $this->hasOne(UpiMandate\Entity::class, UpiMandate\Entity::MANDATE_ID);
    }

    /**
     * @return string self::Amount
     */
    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    /**
     * @return string self::AmountRule
     */
    public function getAmountRule()
    {
        return $this->getAttribute(self::AMOUNT_RULE);
    }

    /**
     * @return string self::Start date
     */
    public function getStartDate()
    {
        return $this->getAttribute(self::START_DATE);
    }

    /**
     * @return string self::end date
     */
    public function getEndDate()
    {
        return $this->getAttribute(self::START_DATE);
    }

    /**
     * @return string self::Expireat
     */
    public function getExpiry()
    {
        return $this->getAttribute(self::EXPIRE_AT);
    }

    /**
     * @return string self::Expireat
     */
    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    /**
     * @return string self::Action
     */
    public function getAction()
    {
        return $this->getAttribute(self::ACTION);
    }

    /**
     * This is the method to get upi details
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function patch()
    {
        return $this->hasMany(Patch\Entity::class, Patch\Entity::MANDATE_ID);
    }

    /**
     * @return string self::TYPE
     */
    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    /**
     * @return string self::FLOW
     */
    public function getFlow()
    {
        return $this->getAttribute(self::FLOW);
    }

    /**
     * @return string self::MODE
     */
    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    /**
    * @return string self::GATEWAY
    */
    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    /**
     * @return string self::STATUS
     */
    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    /**
     * @return string self::ERROR_CODE
     */
    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    /**
     * @return string self::PAYER_TYPE
     */
    public function getPayerType()
    {
        return $this->getAttribute(self::PAYER_TYPE);
    }

    /**
     * @return string self::PAYEE_TYPE
     */
    public function getPayeeType()
    {
        return $this->getAttribute(self::PAYEE_TYPE);
    }
}
