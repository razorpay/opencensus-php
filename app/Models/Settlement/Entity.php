<?php

namespace RZP\Models\Settlement;

use RZP\Models\Base;
use RZP\Models\Merchant\BankAccount;
use RZP\Models\Transaction;
use RZP\Exception;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const BANK_ACCOUNT_ID       = 'bank_account_id';
    const AMOUNT                = 'amount';
    const FEES                  = 'fees';
    const SERVICE_TAX           = 'service_tax';
    const STATUS                = 'status';
    const TRANSACTION_ID        = 'transaction_id';
    const CHANNEL               = 'channel';
    const UTR                   = 'utr';
    const FAILURE_REASON        = 'failure_reason';
    const RETURN_UTR            = 'return_utr';

    protected $table = \RZP\Constants\Table::SETTLEMENT;

    protected static $sign = 'setl';

    protected $entity = 'settlement';

    protected $fillable = array(
//        self::AMOUNT,
        self::FEES,
        self::SERVICE_TAX,
        self::STATUS,
        self::MERCHANT_ID,
        self::BANK_ACCOUNT_ID,
        self::TRANSACTION_ID);

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::BANK_ACCOUNT_ID,
        self::AMOUNT,
        self::FEES,
        self::SERVICE_TAX,
        self::STATUS,
        self::TRANSACTION_ID,
        self::FAILURE_REASON,
        self::CHANNEL,
        self::UTR,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::STATUS,
        self::FEES,
        self::SERVICE_TAX,
        self::UTR,
        self::CREATED_AT);

    protected $amounts = array(
        self::AMOUNT,
        self::FEES,
        self::SERVICE_TAX,
    );

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function bankAccount()
    {
        return $this->belongsTo(
                                'RZP\Models\Merchant\BankAccount\Entity',
                                self::BANK_ACCOUNT_ID,
                                BankAccount\Entity::ID);
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function isStatusCreated()
    {
        return ($this->getStatus() === Status::CREATED);
    }

    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::FAILED);
    }

    public function setlTransactions()
    {
        return $this->hasMany('RZP\Models\Transaction\Entity');
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getFees()
    {
        return $this->getAttribute(self::FEES);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getServiceTax()
    {
        return $this->getAttribute(self::SERVICE_TAX);
    }

    public function setAmount($amount)
    {
        if (($amount <= 0) or
            (is_int($amount) === false))
        {
            throw new Exception\LogicException(
                'Something very wrong is happening! ' .
                'Settlement amount should not be 0 or -ve',
                null,
                ['amount' => $amount]);
        }

        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setStatus($status = Status::CREATED)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setChannel($channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setReturnUtr($utr)
    {
        $this->setAttribute(self::RETURN_UTR, $utr);
    }

    public function setFailureReason($reason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reason);
    }

    public function setFees($fee)
    {
        $this->setAttribute(self::FEES, $fee);
    }

    public function setServiceTax($serviceTax)
    {
        $this->setAttribute(self::SERVICE_TAX, $serviceTax);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    protected function getServiceTaxAttribute()
    {
        return (int) $this->attributes[self::SERVICE_TAX];
    }

    protected function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    protected function getFeesAttribute()
    {
        $fee = $this->attributes[self::FEES];

        if ($fee !== null)
        {
            $fee = (int) $fee;
        }

        return $fee;
    }

    public function save(array $options = array())
    {
        $this->validateAmount();

        return parent::save($options);
    }

    protected function validateAmount()
    {
        // if ($this->getAmount() <= 0)
        // {
        //     throw new Exception\LogicException(
        //         'Something very wrong is happening! ' .
        //         'Settlement amount should not be 0 or -ve',
        //         $this->toArray());
        // }
    }
}
