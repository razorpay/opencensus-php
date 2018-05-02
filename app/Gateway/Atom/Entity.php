<?php

namespace RZP\Gateway\Atom;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                 = 'id';
    const PAYMENT_ID         = 'payment_id';
    const REFUND_ID          = 'refund_id';
    const ACTION             = 'action';
    const RECEIVED           = 'received';
    const ACCOUNT_NUMBER     = 'account_number';
    const AMOUNT             = 'amount';
    const GATEWAY_PAYMENT_ID = 'gateway_payment_id';
    const STATUS             = 'status';
    const ERROR_CODE         = 'error_code';
    const SUCCESS            = 'success';
    const BANK_CODE          = 'bank_code';
    const BANK_NAME          = 'bank_name';
    const BANK_PAYMENT_ID    = 'bank_payment_id';
    const ERROR_DESCRIPTION  = 'gateway_result_description';
    //These attributes were used in old code, Now we dont need them
    const TOKEN              = 'token';
    const METHOD             = 'method';
    const CALLBACK_DATA      = 'callback_data';

    protected $entity = Constants\Entity::ATOM;

    protected $fields = [
        self::PAYMENT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::BANK_CODE,
        self::BANK_NAME,
        self::BANK_PAYMENT_ID,
        self::ERROR_DESCRIPTION,
        self::REFUND_ID,
        self::AMOUNT,
        self::STATUS,
        self::SUCCESS,
        self::ACCOUNT_NUMBER,
        self::RECEIVED,
    ];

    protected $fillable = [
        self::PAYMENT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::TOKEN,
        self::BANK_CODE,
        self::BANK_NAME,
        self::BANK_PAYMENT_ID,
        self::ERROR_DESCRIPTION,
        self::REFUND_ID,
        self::AMOUNT,
        self::STATUS,
        self::ACCOUNT_NUMBER,
        self::RECEIVED,
        self::ERROR_CODE,
        self::SUCCESS,
    ];

    protected $casts = [
        self::AMOUNT   => 'int',
        self::RECEIVED => 'bool',
        self::SUCCESS  => 'bool',
    ];

    protected $primaryKey = self::ID;

    public $incrementing = true;

    public function setAction(string $action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setPaymentId($paymentId)
    {
        $this->setAttribute(self::PAYMENT_ID, $paymentId);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setRefundId($refundId)
    {
        $this->setAttribute(self::REFUND_ID, $refundId);
    }

    public function setAccountNumber($accountNumber)
    {
        $this->setAttribute(self::ACCOUNT_NUMBER, $accountNumber);
    }

    public function getBankPaymentId()
    {
        return $this->getAttribute(Entity::BANK_PAYMENT_ID);
    }
}
