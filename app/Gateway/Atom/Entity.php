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
    const DATE               = 'date';

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
        self::DATE,
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
        self::DATE,
    ];

    protected $casts = [
        self::AMOUNT   => 'int',
        self::RECEIVED => 'bool',
        self::SUCCESS  => 'bool',
    ];

    protected $primaryKey = self::ID;

    public $incrementing = true;

    public function getBankPaymentId()
    {
        return $this->getAttribute(self::BANK_PAYMENT_ID);
    }

    public function getGatewayPaymentId()
    {
        return $this->getAttribute(self::GATEWAY_PAYMENT_ID);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getDate()
    {
        return $this->getAttribute(self::DATE);
    }

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

    public function setGatewayPaymentId($gatewayPaymentId)
    {
        $this->setAttribute(self::GATEWAY_PAYMENT_ID, $gatewayPaymentId);
    }

    public function setBankPaymentId($bankPaymentId)
    {
        $this->setAttribute(self::BANK_PAYMENT_ID, $bankPaymentId);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setSuccess($success)
    {
        $this->setAttribute(self::SUCCESS, $success);
    }

    public function setReceived($received)
    {
        $this->setAttribute(self::RECEIVED, $received);
    }

    public function setDate($date)
    {
        $this->setAttribute(self::DATE, $date);
    }

    public function getReceived()
    {
        return $this->getAttribute(self::RECEIVED);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
