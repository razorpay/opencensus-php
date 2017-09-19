<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const PAYMENT_ID            = 'payment_id';
    const BANK                  = 'bank';
    const RECEIVED              = 'received';
    const AMOUNT                = 'amount';
    const CLIENT_CODE           = 'client_code';
    const MERCHANT_CODE         = 'merchant_code';
    const CUSTOMER_ID           = 'customer_id';
    const CUSTOMER_NAME         = 'customer_name';
    const BANK_PAYMENT_ID       = 'bank_payment_id';
    const STATUS                = 'status';
    const ERROR_MESSAGE         = 'error_message';
    const DATE                  = 'date';
    const REFUND_ID             = 'refund_id';
    const REFERENCE1            = 'reference1';
    const ACCOUNT_NUMBER        = 'account_number';
    //Credit Account number is the bank account to which money is transferred.
    const CREDIT_ACCOUNT_NUMBER = 'credit_account_number';
    const INT_PAYMENT_ID        = 'int_payment_id';
    const CAPS_PAYMENT_ID       = 'caps_payment_id';

    protected $entity = 'netbanking';

    protected $fields = array(
        self::ID,
        self::PAYMENT_ID,
        self::BANK,
        self::RECEIVED,
        self::AMOUNT,
        self::CLIENT_CODE,
        self::MERCHANT_CODE,
        self::CUSTOMER_ID,
        self::CUSTOMER_NAME,
        self::BANK_PAYMENT_ID,
        self::STATUS,
        self::ERROR_MESSAGE,
        self::DATE,
        self::REFUND_ID,
        self::REFERENCE1,
        self::ACCOUNT_NUMBER,
        self::INT_PAYMENT_ID,
        self::CAPS_PAYMENT_ID,
    );

    protected $fillable = array(
        self::BANK,
        self::AMOUNT,
        self::RECEIVED,
        self::CLIENT_CODE,
        self::MERCHANT_CODE,
        self::CUSTOMER_ID,
        self::CUSTOMER_NAME,
        self::BANK_PAYMENT_ID,
        self::ERROR_MESSAGE,
        self::DATE,
        self::STATUS,
        self::REFUND_ID,
        self::REFERENCE1,
        self::ACCOUNT_NUMBER,
        self::INT_PAYMENT_ID,
    );

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function setPaymentId($paymentId)
    {
        parent::setPaymentId($paymentId);

        $this->attributes[self::CAPS_PAYMENT_ID] = strtoupper($paymentId);
    }

    public function setAccountNumber($accountNumber)
    {
        $this->setAttribute(self::ACCOUNT_NUMBER, $accountNumber);
    }

    public function setCreditAccountNumber(string $creditAccountNumber)
    {
        $this->setAttribute(self::CREDIT_ACCOUNT_NUMBER, $creditAccountNumber);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function isTpv()
    {
        $accountNumber = $this->getAccountNumber();

        if (is_null($accountNumber) === true)
        {
            return false;
        }

        return true;
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getBankPaymentId()
    {
        return $this->getAttribute(self::BANK_PAYMENT_ID);
    }

    public function setBankPaymentId($bankPaymentId)
    {
        $this->setAttribute(self::BANK_PAYMENT_ID, $bankPaymentId);
    }

    public function setCustomerId($customerId)
    {
        $this->setAttribute(self::CUSTOMER_ID, $customerId);
    }

    public function setCustomerName($customerName)
    {
        $this->setAttribute(self::CUSTOMER_NAME, $customerName);
    }

    public function getIntPaymentId()
    {
        return $this->getAttribute(self::INT_PAYMENT_ID);
    }

    public function getCapsPaymentId()
    {
        return $this->getAttribute(self::CAPS_PAYMENT_ID);
    }

    public function getReference1()
    {
        return $this->getAttribute(self::REFERENCE1);
    }

    public function getReceived()
    {
        return $this->getAttribute(self::RECEIVED);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }
}
