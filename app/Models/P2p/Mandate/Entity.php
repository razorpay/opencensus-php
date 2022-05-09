<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\Customer;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Mandate\Status;

/**
 * Class Entity
 *
 * @property Vpa\Entity $payer
 * @property Vpa\Entity $payee
 * @property UpiMandate\Entity $upi
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
    const CYCLES_COMPLETED    = 'cycles_completed';

    /************** Input  Properties ************/

    const MANDATE            = 'mandate';
    const CUSTOMER           = 'customer';
    const PAYER              = 'payer';
    const PAYEE              = 'payee';
    const BANK_ACCOUNT       = 'bank_account';
    const UPI                = 'upi';
    const IS_PENDING_COLLECT = 'is_pending_collect';

    protected static $sign = 'cmdt';

    /************** Entity Properties ************/

    protected $entity = 'p2p_mandate';

    protected $dates = [
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::EXPIRE_AT,
        Entity::CREATED_AT,
        Entity::COMPLETED_AT,
        Entity::EXPIRE_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::TYPE,
        Entity::FLOW,
        Entity::MODE,
        Entity::AMOUNT,
        Entity::AMOUNT_RULE,
        Entity::CURRENCY,
        Entity::PAYER_ID,
        Entity::PAYEE_ID,
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
        Entity::IS_PENDING_COLLECT,
        Entity::COMPLETED_AT,
        Entity::REVOKED_AT,
        Entity::CYCLES_COMPLETED,
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
        Entity::IS_PENDING_COLLECT,
        Entity::ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::INTERNAL_ERROR_CODE,
        Entity::COMPLETED_AT,
        Entity::EXPIRE_AT,
        Entity::REVOKED_AT,
        Entity::CYCLES_COMPLETED,
    ];

    protected $public = [
        Entity::ENTITY,
        Entity::ID,
        Entity::AMOUNT,
        Entity::AMOUNT_RULE,
        Entity::CURRENCY,
        Entity::PAYER,
        Entity::PAYEE,
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
        Entity::IS_PENDING_COLLECT,
        Entity::ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::UMN,
        Entity::REVOKED_AT,
        Entity::CYCLES_COMPLETED,
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
     *  This is the method to check if the status is marked as completed
     * @return bool
     */
    public function isRevoked(): bool
    {
        return in_array($this->getInternalStatus(), [Status::REVOKED]);
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

        if (isset($array[self::PAYER]))
        {
            $array[self::PAYER] = $this->payer->toArrayBeneficiary();
        }

        if (isset($array[self::PAYEE]))
        {
            $array[self::PAYEE] = $this->payee->toArrayBeneficiary();
        }

        return $array;
    }
}
