<?php

namespace RZP\Models\P2p\Transaction;

use Carbon\Carbon;
use RZP\Base\BuilderEx;
use RZP\Models\P2p\Vpa;
use RZP\Models\Customer;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\BankAccount;

/**
 * @property Vpa\Entity $payer
 * @property Vpa\Entity $payee
 * @property Concern\Entity $concern
 * @property UpiTransaction\Entity $upi
 * @property boolean $isConcernEligible
 *
 * Class Entity
 * @package RZP\Models\P2p\Transaction
 */
class Entity extends Base\Entity
{
    use Base\Traits\HasMerchant;
    use Base\Traits\HasDevice;
    use Base\Traits\HasHandle;
    use Base\Traits\SoftDeletes;
    use Base\Traits\HasBankAccount;

    const MERCHANT_ID                   = 'merchant_id';
    const CUSTOMER_ID                   = 'customer_id';
    const PAYER_TYPE                    = 'payer_type';
    const PAYER_ID                      = 'payer_id';
    const PAYEE_TYPE                    = 'payee_type';
    const PAYEE_ID                      = 'payee_id';
    const BANK_ACCOUNT_ID               = 'bank_account_id';
    const METHOD                        = 'method';
    const TYPE                          = 'type';
    const FLOW                          = 'flow';
    const MODE                          = 'mode';
    const AMOUNT                        = 'amount';
    const AMOUNT_MINIMUM                = 'amount_minimum';
    const AMOUNT_AUTHORIZED             = 'amount_authorized';
    const CURRENCY                      = 'currency';
    const DESCRIPTION                   = 'description';
    const GATEWAY                       = 'gateway';
    const STATUS                        = 'status';
    const INTERNAL_STATUS               = 'internal_status';
    const ERROR_CODE                    = 'error_code';
    const ERROR_DESCRIPTION             = 'error_description';
    const INTERNAL_ERROR_CODE           = 'internal_error_code';
    const PAYER_APPROVAL_CODE           = 'payer_approval_code';
    const PAYEE_APPROVAL_CODE           = 'payee_approval_code';
    const INITIATED_AT                  = 'initiated_at';
    const EXPIRE_AT                     = 'expire_at';
    const COMPLETED_AT                  = 'completed_at';

    /************** Input  Properties ************/

    const TRANSACTION          = 'transaction';
    const CUSTOMER             = 'customer';
    const PAYER                = 'payer';
    const PAYEE                = 'payee';
    const BANK_ACCOUNT         = 'bank_account';
    const CL                   = 'cl';
    const CONCERN              = 'concern';
    const CONCERNS             = 'concerns';
    const IS_CONCERN_ELIGIBLE  = 'is_concern_eligible';
    const IS_PENDING_COLLECT   = 'is_pending_collect';
    const IS_SELF_TRANSFER     = 'is_self_transfer';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_transaction';
    protected static $sign        = 'ctxn';
    protected $generateIdOnCreate = true;
    protected static $generators  = [];

    protected $dates = [
        Entity::INITIATED_AT,
        Entity::EXPIRE_AT,
        Entity::COMPLETED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::PAYER,
        Entity::PAYEE,
        Entity::METHOD,
        Entity::TYPE,
        Entity::FLOW,
        Entity::MODE,
        Entity::AMOUNT,
        Entity::CURRENCY,
        Entity::DESCRIPTION,
        Entity::GATEWAY,
        Entity::STATUS,
        Entity::INTERNAL_STATUS,
        Entity::ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::INTERNAL_ERROR_CODE,
        Entity::PAYER_APPROVAL_CODE,
        Entity::PAYEE_APPROVAL_CODE,
        Entity::INITIATED_AT,
        Entity::EXPIRE_AT,
        Entity::COMPLETED_AT,
    ];

    protected $visible = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::CUSTOMER_ID,
        Entity::PAYER_TYPE,
        Entity::PAYER_ID,
        Entity::PAYEE_TYPE,
        Entity::PAYEE_ID,
        Entity::BANK_ACCOUNT_ID,
        Entity::METHOD,
        Entity::TYPE,
        Entity::FLOW,
        Entity::MODE,
        Entity::AMOUNT,
        Entity::CURRENCY,
        Entity::DESCRIPTION,
        Entity::GATEWAY,
        Entity::STATUS,
        Entity::INTERNAL_STATUS,
        Entity::ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::INTERNAL_ERROR_CODE,
        Entity::PAYER_APPROVAL_CODE,
        Entity::PAYEE_APPROVAL_CODE,
        Entity::IS_CONCERN_ELIGIBLE,
        Entity::IS_PENDING_COLLECT,
        Entity::INITIATED_AT,
        Entity::EXPIRE_AT,
        Entity::COMPLETED_AT,
        Entity::CREATED_AT,
        Entity::CUSTOMER,
        Entity::PAYER,
        Entity::PAYEE,
        Entity::BANK_ACCOUNT,
        Entity::UPI,
    ];

    protected $public = [
        Entity::ENTITY,
        Entity::ID,
        Entity::TYPE,
        Entity::FLOW,
        Entity::AMOUNT,
        Entity::CURRENCY,
        Entity::DESCRIPTION,
        Entity::STATUS,
        Entity::ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::IS_CONCERN_ELIGIBLE,
        Entity::IS_PENDING_COLLECT,
        Entity::INITIATED_AT,
        Entity::EXPIRE_AT,
        Entity::COMPLETED_AT,
        Entity::CREATED_AT,
        Entity::PAYER,
        Entity::PAYEE,
        Entity::BANK_ACCOUNT,
        Entity::UPI,
        Entity::CONCERN,
    ];

    protected $defaults = [
        Entity::PAYER_TYPE           => null,
        Entity::PAYER_ID             => null,
        Entity::PAYEE_TYPE           => null,
        Entity::PAYEE_ID             => null,
        Entity::METHOD               => null,
        Entity::TYPE                 => null,
        Entity::FLOW                 => null,
        Entity::MODE                 => null,
        Entity::AMOUNT               => null,
        Entity::CURRENCY             => null,
        Entity::DESCRIPTION          => null,
        Entity::GATEWAY              => null,
        Entity::STATUS               => null,
        Entity::INTERNAL_STATUS      => null,
        Entity::ERROR_CODE           => null,
        Entity::ERROR_DESCRIPTION    => null,
        Entity::INTERNAL_ERROR_CODE  => null,
        Entity::PAYER_APPROVAL_CODE  => null,
        Entity::PAYEE_APPROVAL_CODE  => null,
        Entity::INITIATED_AT         => null,
        Entity::EXPIRE_AT            => null,
        Entity::COMPLETED_AT         => null,
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
        Entity::METHOD               => 'string',
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
        Entity::PAYER_APPROVAL_CODE  => 'string',
        Entity::PAYEE_APPROVAL_CODE  => 'string',
        Entity::IS_CONCERN_ELIGIBLE  => 'boolean',
        Entity::IS_PENDING_COLLECT   => 'boolean',
        Entity::INITIATED_AT         => 'int',
        Entity::EXPIRE_AT            => 'int',
        Entity::COMPLETED_AT         => 'int',
        Entity::CREATED_AT           => 'int',
        Entity::UPDATED_AT           => 'int',
    ];

    protected $with = [
        Entity::UPI,
    ];

    protected $appends = [
        Entity::IS_CONCERN_ELIGIBLE,
        Entity::IS_PENDING_COLLECT,
    ];

    /***************** SETTERS *****************/

    /**
     * @return $this
     */
    public function setMerchantId(string $merchantId)
    {
        return $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    /**
     * @return $this
     */
    public function setCustomerId(string $customerId)
    {
        return $this->setAttribute(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @return $this
     */
    public function setPayerType(string $payerType)
    {
        return $this->setAttribute(self::PAYER_TYPE, $payerType);
    }

    /**
     * @return $this
     */
    public function setPayerId(string $payerId)
    {
        return $this->setAttribute(self::PAYER_ID, $payerId);
    }

    /**
     * @return $this
     */
    public function setPayeeType(string $payeeType)
    {
        return $this->setAttribute(self::PAYEE_TYPE, $payeeType);
    }

    /**
     * @return $this
     */
    public function setPayeeId(string $payeeId)
    {
        return $this->setAttribute(self::PAYEE_ID, $payeeId);
    }

    /**
     * @return $this
     */
    public function setBankAccountId(string $bankAccountId)
    {
        return $this->setAttribute(self::BANK_ACCOUNT_ID, $bankAccountId);
    }

    /**
     * @return $this
     */
    public function setMethod(string $method)
    {
        return $this->setAttribute(self::METHOD, $method);
    }

    /**
     * @return $this
     */
    public function setType(string $type)
    {
        return $this->setAttribute(self::TYPE, $type);
    }

    /**
     * @return $this
     */
    public function setFlow(string $flow)
    {
        return $this->setAttribute(self::FLOW, $flow);
    }

    /**
     * @return $this
     */
    public function setMode(string $mode)
    {
        return $this->setAttribute(self::MODE, $mode);
    }

    /**
     * @return $this
     */
    public function setAmount(int $amount)
    {
        return $this->setAttribute(self::AMOUNT, $amount);
    }

    /**
     * @return $this
     */
    public function setCurrency(string $currency)
    {
        return $this->setAttribute(self::CURRENCY, $currency);
    }

    /**
     * @return $this
     */
    public function setDescription(string $description)
    {
        return $this->setAttribute(self::DESCRIPTION, $description);
    }

    /**
     * @return $this
     */
    public function setGateway(string $gateway)
    {
        return $this->setAttribute(self::GATEWAY, $gateway);
    }

    /**
     * @return $this
     */
    public function setStatus(string $status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    /**
     * @return $this
     */
    public function setInternalStatus(string $internalStatus)
    {
        $this->setStatus($internalStatus);

        return $this->setAttribute(self::INTERNAL_STATUS, $internalStatus);
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
     * @return $this
     */
    public function setPayerApprovalCode(string $payerApprovalCode)
    {
        return $this->setAttribute(self::PAYER_APPROVAL_CODE, $payerApprovalCode);
    }

    /**
     * @return $this
     */
    public function setPayeeApprovalCode(string $payeeApprovalCode)
    {
        return $this->setAttribute(self::PAYEE_APPROVAL_CODE, $payeeApprovalCode);
    }

    /**
     * @return $this
     */
    public function setInitiatedAt(int $initiatedAt)
    {
        return $this->setAttribute(self::INITIATED_AT, $initiatedAt);
    }

    /**
     * @return $this
     */
    public function setExpireAt(int $expireAt)
    {
        return $this->setAttribute(self::EXPIRE_AT, $expireAt);
    }

    /**
     * @return $this
     */
    public function setCompletedAt(int $completedAt)
    {
        return $this->setAttribute(self::COMPLETED_AT, $completedAt);
    }

    public function markCompleted()
    {
        $this->setInternalStatus(Status::COMPLETED);
        $this->setAttribute(self::COMPLETED_AT, $this->freshTimestamp());
    }

    public function markInitiated()
    {
        $this->setInternalStatus(Status::INITIATED);
        $this->setAttribute(self::INITIATED_AT, $this->freshTimestamp());
    }

    /***************** GETTERS *****************/

    /**
     * @return string self::MERCHANT_ID
     */
    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    /**
     * @return string self::CUSTOMER_ID
     */
    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    /**
     * @return string self::PAYER_TYPE
     */
    public function getPayerType()
    {
        return $this->getAttribute(self::PAYER_TYPE);
    }

    /**
     * @return string self::PAYER_ID
     */
    public function getPayerId()
    {
        return $this->getAttribute(self::PAYER_ID);
    }

    /**
     * @return string self::PAYEE_TYPE
     */
    public function getPayeeType()
    {
        return $this->getAttribute(self::PAYEE_TYPE);
    }

    /**
     * @return string self::PAYEE_ID
     */
    public function getPayeeId()
    {
        return $this->getAttribute(self::PAYEE_ID);
    }

    /**
     * @return string self::BANK_ACCOUNT_ID
     */
    public function getBankAccountId()
    {
        return $this->getAttribute(self::BANK_ACCOUNT_ID);
    }

    /**
     * @return string self::METHOD
     */
    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
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
     * @return int self::AMOUNT
     */
    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    /**
     * @return string self::AMOUNT
     */
    public function getRupeesAmount()
    {
        return number_format(floatval($this->getAmount() / 100), 2, '.', '');
    }

    /**
     * @return string self::CURRENCY
     */
    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    /**
     * @return string self::DESCRIPTION
     */
    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
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
     * @return string self::INTERNAL_STATUS
     */
    public function getInternalStatus()
    {
        return $this->getAttribute(self::INTERNAL_STATUS);
    }

    /**
     * @return string self::ERROR_CODE
     */
    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    /**
     * @return string self::ERROR_DESCRIPTION
     */
    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    /**
     * @return string self::INTERNAL_ERROR_CODE
     */
    public function getInternalErrorCode()
    {
        return $this->getAttribute(self::INTERNAL_ERROR_CODE);
    }

    /**
     * @return string self::PAYER_APPROVAL_CODE
     */
    public function getPayerApprovalCode()
    {
        return $this->getAttribute(self::PAYER_APPROVAL_CODE);
    }

    /**
     * @return string self::PAYEE_APPROVAL_CODE
     */
    public function getPayeeApprovalCode()
    {
        return $this->getAttribute(self::PAYEE_APPROVAL_CODE);
    }

    /**
     * @return int self::INITIATED_AT
     */
    public function getInitiatedAt()
    {
        return $this->getAttribute(self::INITIATED_AT);
    }

    /**
     * @return int self::EXPIRE_AT
     */
    public function getExpireAt()
    {
        return $this->getAttribute(self::EXPIRE_AT) ??
               Carbon::now()->addMinutes(5)->getTimestamp();
    }

    /**
     * @return int self::COMPLETED_AT
     */
    public function getCompletedAt()
    {
        return $this->getAttribute(self::COMPLETED_AT);
    }

    public function isCompleted(): bool
    {
        return in_array($this->getInternalStatus(), [Status::COMPLETED]);
    }

    public function isProcessing(): bool
    {
        return in_array($this->getInternalStatus(), [Status::REQUESTED, Status::INITIATED, Status::PENDING]);
    }

    public function isFailed(): bool
    {
        return in_array($this->getInternalStatus(), [Status::FAILED, Status::REJECTED, Status::EXPIRED]);
    }

    public function isCreated(): bool
    {
        return in_array($this->getInternalStatus(), [Status::CREATED]);
    }

    public function isPendingCollect(): bool
    {
        return $this->getAttribute(self::IS_PENDING_COLLECT);
    }

    public function isConcernEligible()
    {
        return $this->getAttribute(self::IS_CONCERN_ELIGIBLE);
    }

    /***************** RELATIONS *****************/

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function associateCustomer(Customer\Entity $handle)
    {
        return $this->customer()->associate($handle);
    }

    public function payer()
    {
        return $this->morphTo(self::PAYER)->withTrashed();
    }

    public function payee()
    {
        return $this->morphTo(self::PAYEE)->withTrashed();
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount\Entity::class)->withTrashed();
    }

    public function upi()
    {
        return $this->hasOne(UpiTransaction\Entity::class, UpiTransaction\Entity::TRANSACTION_ID);
    }

    public function concerns()
    {
        return $this->hasMany(Concern\Entity::class, Concern\Entity::TRANSACTION_ID);
    }

    public function concern()
    {
        return $this->hasOne(Concern\Entity::class, Concern\Entity::TRANSACTION_ID)
                    ->whereNotIn(Concern\Entity::STATUS, [Concern\Status::CREATED])
                    ->latest();
    }

    public function setPublicEntityAttribute(array & $array)
    {
        $array[self::ENTITY] = 'customer.transaction';
    }

    protected function getIsConcernEligibleAttribute()
    {
        return in_array($this->getInternalStatus(), [
            Status::FAILED,
            Status::PENDING,
        ]);
    }

    protected function getIsPendingCollectAttribute()
    {
        return ($this->getStatus() === Status::REQUESTED);
    }

    protected function getIsSelfTransferAttribute()
    {
        $payerDeviceId = $this->payer->getDeviceId();

        $payeeDeviceId = $this->payee->getDeviceId();

        return $payerDeviceId === $payeeDeviceId;
    }

    public function isSelfTransfer(): bool
    {
        return $this->getAttribute(self::IS_SELF_TRANSFER);
    }

    public function toArrayPublic(): array
    {
        $array = parent::toArrayPublic();

        $array[self::UPI] = $this->upi->toArrayPublic();

        if (isset($array[self::PAYER]))
        {
            $array[self::PAYER] = $this->payer->toArrayBeneficiary();
        }

        if (isset($array[self::PAYEE]))
        {
            $array[self::PAYEE] = $this->payee->toArrayBeneficiary();
        }

        if (isset($array[self::BANK_ACCOUNT]))
        {
            $array[self::BANK_ACCOUNT] = $this->bankAccount->toArrayPublic();
        }

        if (isset($array[self::CONCERN]))
        {
            $array[self::CONCERN] = $this->concern->toArrayPublic();
        }

        return $array;
    }

    public function toArrayPartner(): array
    {
        $array = $this->toArrayPublic();

        $array[self::CUSTOMER_ID]   = Customer\Entity::getSignedId($this->getCustomerId());
        $array[self::UPI]           = $this->upi->toArrayPublic();
        $array[self::PAYER]         = array_except($this->payer->toArrayPartner(), self::BANK_ACCOUNT);
        $array[self::PAYEE]         = array_except($this->payee->toArrayPartner(), self::BANK_ACCOUNT);
        $array[self::BANK_ACCOUNT]  = $this->bankAccount->toArrayPublic();
        $array[self::MODE]          = $this->getMode();

        return $array;
    }

    public function toArrayTrace(): array
    {
        return array_only($this->toArray(), [
            self::ID,
            self::DEVICE_ID,
            self::HANDLE,
            self::AMOUNT,
            self::TYPE,
            self::FLOW,
        ]);
    }
}
