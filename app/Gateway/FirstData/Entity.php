<?php

namespace RZP\Gateway\FirstData;

use RZP\Gateway\Base;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                            = 'id';
    const AMOUNT                        = 'amount';
    const ORDER_ID                      = 'order_id';
    const TDATE                         = 'tdate';
    const STATUS                        = 'status';
    const TRANSACTION_RESULT            = 'transaction_result';
    const APPROVAL_CODE                 = 'approval_code';
    const ERROR_MESSAGE                 = 'error_message';

    protected $fields = array(
        self::ID,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::RECEIVED,
        self::ACTION,
        self::AMOUNT,
        self::ORDER_ID,
        self::TDATE,
        self::STATUS,
        self::TRANSACTION_RESULT,
        self::APPROVAL_CODE,
        self::ERROR_MESSAGE,
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected $fillable = array(
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::RECEIVED,
        self::ACTION,
        self::AMOUNT,
        self::ORDER_ID,
        self::TDATE,
        self::STATUS,
        self::TRANSACTION_RESULT,
        self::APPROVAL_CODE,
        self::ERROR_MESSAGE,
    );

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $table = Constants\Table::FIRST_DATA;

    protected $primaryKey = self::ID;

    protected $entity = Constants\Table::FIRST_DATA;

    public $incrementing = true;

    protected $guarded = array();

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }

    public function getApprovalCode()
    {
        return $this->getAttribute(self::APPROVAL_CODE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getTdate()
    {
        return $this->getAttribute(self::TDATE);
    }

    public function setTdate($tdate)
    {
        return $this->setAttribute(self::TDATE, $tdate);
    }

    public function setStatus($status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    public function setErrorMessage($msg)
    {
        return $this->setAttribute(self::ERROR_MESSAGE, $msg);
    }

    public function setApprovalCode($code)
    {
        return $this->setAttribute(self::APPROVAL_CODE, $code);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

}
