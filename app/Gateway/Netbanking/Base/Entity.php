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
    const VERIFICATION_ID       = 'verification_id';
    const ACCOUNT_NUMBER        = 'account_number';
    const ACCOUNT_TYPE          = 'account_type';
    const ACCOUNT_SUBTYPE       = 'account_subtype';
    const ACCOUNT_BRANCHCODE    = 'account_branch_code';
    // Credit Account number is the bank account to which money is transferred.
    const CREDIT_ACCOUNT_NUMBER = 'credit_account_number';
    const INT_PAYMENT_ID        = 'int_payment_id';
    const CAPS_PAYMENT_ID       = 'caps_payment_id';

    //
    // Recurring specific fields
    //

    const SI_TOKEN              = 'si_token';
    const SI_STATUS             = 'si_status';
    const SI_MSG                = 'si_message';

    /**
     * Number of years from now to set for end_date.
     * For charge at will payments, we don't know off hand
     * how long the merchant wants the subscription to go on
     */
    const MAX_RECURRING_END_YEARS = 10;

    protected $entity = 'netbanking';

    protected $fields = [
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
    ];

    protected $fillable = [
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
        self::VERIFICATION_ID,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_SUBTYPE,
        self::ACCOUNT_BRANCHCODE,
        self::INT_PAYMENT_ID,
        self::SI_TOKEN,
        self::SI_STATUS,
        self::SI_MSG,
    ];

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setDate($date)
    {
        $this->setAttribute(self::DATE, $date);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
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

    public function setRefundId($refundId)
    {
        $this->setAttribute(self::REFUND_ID, $refundId);
    }

    public function setAccountType(string $accountType)
    {
        $this->setAttribute(self::ACCOUNT_TYPE, $accountType);
    }

    public function setAccountSubType(string $accountSubType)
    {
        $this->setAttribute(self::ACCOUNT_SUBTYPE, $accountSubType);
    }

    public function setAccountBranchCode(string $accountBranchCode)
    {
        $this->setAttribute(self::ACCOUNT_BRANCHCODE, $accountBranchCode);
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

    public function setReceived($received)
    {
        $this->setAttribute(self::RECEIVED, $received);
    }

    public function setReference1($reference1)
    {
        $this->setAttribute(self::REFERENCE1, $reference1);
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

    public function getVerificationId()
    {
        return $this->getAttribute(self::VERIFICATION_ID);
    }

    public function getReceived()
    {
        return $this->getAttribute(self::RECEIVED);
    }

    public function getErrorMessage()
    {
        return $this->getAttribute(self::ERROR_MESSAGE);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getClientCode()
    {
        return $this->getAttribute(self::CLIENT_CODE);
    }

    public function getSIToken()
    {
        return $this->getAttribute(self::SI_TOKEN);
    }

    public function getSIStatus()
    {
        return $this->getAttribute(self::SI_STATUS);
    }

    public function getSIMessage()
    {
        return $this->getAttribute(self::SI_MSG);
    }

    public function getDate()
    {
        return $this->getAttribute(self::DATE);
    }

	protected function setErrorMessageAttribute($message)
	{
		//to reduce the length of error message in case it extends database column field size.
		$this->attributes[self::ERROR_MESSAGE] = substr($message, 0, 255);
	}
}
