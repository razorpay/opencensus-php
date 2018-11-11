<?php

namespace Rzp\Models\P2p\Transaction;

use RZP\Models\P2p\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\Entity
{
    const ID                   = 'id';

    const ENTITY               = 'entity';

    const TXN_ID               = 'txn_id';

    const STATUS               = 'status';

    const AMOUNT               = 'amount';

    const DESCRIPTION          = 'description';

    const TYPE                 = 'type';

    const CURRENCY             = 'currency';

    const ERROR_DESCRIPTION    = 'error_description';

    const ERROR_CODE           = 'error_code';

    const TRANSACTION_TYPE     = 'transaction_type';

    const RRN                  = 'rrn';

    const CREATED_AT           = 'created_at';

    const COMPLETED_AT         = 'completed_at';

    const EXPIRE_AT            = 'expire_at';


    /**************** GETTER *******************/

    /**
     * @return id
     */
    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    /**
     * @return entity
     */
    public function getEntity()
    {
        return $this->getAttribute(self::ENTITY);
    }

    /**
     * @return txn_id
     */
    public function getTxnId()
    {
        return $this->getAttribute(self::TXN_ID);
    }

    /**
     * @return status
     */
    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    /**
     * @return amount
     */
    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    /**
     * @return description
     */
    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    /**
     * @return type
     */
    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    /**
     * @return currency
     */
    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    /**
     * @return error_description
     */
    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    /**
     * @return error_code
     */
    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    /**
     * @return transaction_type
     */
    public function getTransactionType()
    {
        return $this->getAttribute(self::TRANSACTION_TYPE);
    }

    /**
     * @return rrn
     */
    public function getRrn()
    {
        return $this->getAttribute(self::RRN);
    }

    /**
     * @return created_at
     */
    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    /**
     * @return completed_at
     */
    public function getCompletedAt()
    {
        return $this->getAttribute(self::COMPLETED_AT);
    }

    /**
     * @return expire_at
     */
    public function getExpireAt()
    {
        return $this->getAttribute(self::EXPIRE_AT);
    }

    /**************** SETTER *******************/

    /**
     * @return $this
     */
    public function setId($id)
    {
        return $this->setAttribute(self::ID, $id);
    }

    /**
     * @return $this
     */
    public function setEntity($entity)
    {
        return $this->setAttribute(self::ENTITY, $entity);
    }

    /**
     * @return $this
     */
    public function setTxnId($txnId)
    {
        return $this->setAttribute(self::TXN_ID, $txnId);
    }

    /**
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    /**
     * @return $this
     */
    public function setAmount($amount)
    {
        return $this->setAttribute(self::AMOUNT, $amount);
    }

    /**
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setAttribute(self::DESCRIPTION, $description);
    }

    /**
     * @return $this
     */
    public function setType($type)
    {
        return $this->setAttribute(self::TYPE, $type);
    }

    /**
     * @return $this
     */
    public function setCurrency($currency)
    {
        return $this->setAttribute(self::CURRENCY, $currency);
    }

    /**
     * @return $this
     */
    public function setErrorDescription($errorDescription)
    {
        return $this->setAttribute(self::ERROR_DESCRIPTION, $errorDescription);
    }

    /**
     * @return $this
     */
    public function setErrorCode($errorCode)
    {
        return $this->setAttribute(self::ERROR_CODE, $errorCode);
    }

    /**
     * @return $this
     */
    public function setTransactionType($transactionType)
    {
        return $this->setAttribute(self::TRANSACTION_TYPE, $transactionType);
    }

    /**
     * @return $this
     */
    public function setRrn($rrn)
    {
        return $this->setAttribute(self::RRN, $rrn);
    }

    /**
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setAttribute(self::CREATED_AT, $createdAt);
    }

    /**
     * @return $this
     */
    public function setCompletedAt($completedAt)
    {
        return $this->setAttribute(self::COMPLETED_AT, $completedAt);
    }

    /**
     * @return $this
     */
    public function setExpireAt($expireAt)
    {
        return $this->setAttribute(self::EXPIRE_AT, $expireAt);
    }
}
