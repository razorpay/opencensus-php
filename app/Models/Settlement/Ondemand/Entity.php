<?php

namespace RZP\Models\Settlement\Ondemand;

use RZP\Models\Base\Traits\NotesTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Transaction;
use RZP\Models\Settlement\OndemandPayout;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    use NotesTrait;

    protected $generateIdOnCreate = true;

    protected static $sign = 'setlod';

    protected $entity = 'settlement.ondemand';

    const SETTLEMENT_ONDEMAND_PAYOUTS = 'settlement_ondemand_payouts';

    const ID_LENGTH = 14;

    protected $table  = Table::SETTLEMENT_ONDEMAND;

    const TRANSACTION    = 'transaction';


    const ID                             = 'id';
    const MERCHANT_ID                    = 'merchant_id';
    const USER_ID                        = 'user_id';
    const AMOUNT                         = 'amount';
    const TOTAL_AMOUNT_SETTLED           = 'total_amount_settled';
    const TOTAL_FEES                     = 'total_fees';
    const TOTAL_TAX                      = 'total_tax';
    const TOTAL_AMOUNT_REVERSED          = 'total_amount_reversed';
    const TOTAL_AMOUNT_PENDING           = 'total_amount_pending';
    const MAX_BALANCE                    = 'max_balance';
    const CURRENCY                       = 'currency';
    const STATUS                         = 'status';
    const NARRATION                      = 'narration';
    const NOTES                          = 'notes';
    const REMARKS                        = 'remarks';
    const TRANSACTION_ID                 = 'transaction_id';
    const TRANSACTION_TYPE               = 'transaction_type';
    const CREATED_AT                     = 'created_at';
    const UPDATED_AT                     = 'updated_at';
    const DELETED_AT                     = 'deleted_at';
    const SCHEDULED                      = 'scheduled';
    const SETTLEMENT_ONDEMAND_TRIGGER_ID = 'settlement_ondemand_trigger_id';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::TOTAL_AMOUNT_SETTLED,
        self::TOTAL_FEES,
        self::TOTAL_TAX,
        self::TOTAL_AMOUNT_REVERSED,
        self::TOTAL_AMOUNT_PENDING,
        self::MAX_BALANCE,
        self::CURRENCY,
        self::STATUS,
        self::NARRATION,
        self::NOTES,
        self::SETTLEMENT_ONDEMAND_PAYOUTS,
        self::CREATED_AT,
        self::SCHEDULED
    ];

    protected $casts = [
        self::MAX_BALANCE   => 'bool',
        self::AMOUNT        => 'int',
        self::SCHEDULED     => 'bool',
    ];

    protected $fillable = [
        self::AMOUNT,
        self::TOTAL_AMOUNT_SETTLED,
        self::TOTAL_FEES,
        self::TOTAL_TAX,
        self::TOTAL_AMOUNT_REVERSED,
        self::TOTAL_AMOUNT_PENDING,
        self::MAX_BALANCE,
        self::CURRENCY,
        self::STATUS,
        self::NARRATION,
        self::NOTES,
        self::REMARKS,
        self::TRANSACTION_ID,
        self::TRANSACTION_TYPE,
        self::SCHEDULED,
        self::SETTLEMENT_ONDEMAND_TRIGGER_ID
    ];

    public function settlementOnDemandPayouts()
    {
        return $this->hasMany(OndemandPayout\Entity::class, OndemandPayout\Entity::SETTLEMENT_ONDEMAND_ID, self::ID);
    }

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(\RZP\Models\User\Entity::class);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getMerchantId(): string
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getTransactionId(): string
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getAmountToBeSettled()
    {
        return ($this->getAttribute(self::AMOUNT) - $this->getAttribute(self::TOTAL_FEES));
    }

    public function addToTotalAmountReversed($amount)
    {
        $this->setAttribute(self::TOTAL_AMOUNT_REVERSED, $this->getTotalAmountReversed() + $amount);
    }

    public function addToTotalAmountSettled($amount)
    {
        $this->setAttribute(self::TOTAL_AMOUNT_SETTLED, $this->getTotalAmountSettled() + $amount);
    }

    public function deductFromTotalAmountSettled($amount)
    {
        $this->setAttribute(self::TOTAL_AMOUNT_SETTLED, $this->getTotalAmountSettled() - $amount);

        if ($this->getAttribute(self::TOTAL_AMOUNT_SETTLED) < 0)
        {
            throw new Exception\BadRequestValidationFailureException('Total Amount settled cannot be less the zero');
        }
    }

    public function deductFromTotalAmountPending($amount)
    {
        $this->setAttribute(self::TOTAL_AMOUNT_PENDING, $this->getTotalAmountPending() - $amount);

        if ($this->getAttribute(self::TOTAL_AMOUNT_PENDING) < 0)
        {
            throw new Exception\BadRequestValidationFailureException('Total Amount Pending cannot be less the zero');
        }
    }

    public function deductFromTotalTax($amount)
    {
        $this->setAttribute(self::TOTAL_TAX, $this->getAttribute(self::TOTAL_TAX) - $amount);

        if ($this->getAttribute(self::TOTAL_TAX) < 0)
        {
            throw new Exception\BadRequestValidationFailureException('Total Tax cannot be less the zero');
        }
    }

    public function deductFromTotalFees($amount)
    {
        $this->setAttribute(self::TOTAL_FEES, $this->getAttribute(self::TOTAL_FEES) - $amount);

        if ($this->getAttribute(self::TOTAL_FEES) < 0)
        {
            throw new Exception\BadRequestValidationFailureException('Total Fees cannot be less the zero');
        }
    }

    public function getCurrency(): string
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getTotalTax()
    {
        return $this->getAttribute(self::TOTAL_TAX);
    }

    public function getTotalFees()
    {
        return $this->getAttribute(self::TOTAL_FEES);
    }

    public function getTotalAmountReversed()
    {
        return $this->getAttribute(self::TOTAL_AMOUNT_REVERSED);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getTotalAmountPending()
    {
        return $this->getAttribute(self::TOTAL_AMOUNT_PENDING);
    }

    public function getTotalAmountSettled()
    {
        return $this->getAttribute(self::TOTAL_AMOUNT_SETTLED);
    }

    public function getSettlementOndemandTriggerId()
    {
        return $this->getAttribute(self::SETTLEMENT_ONDEMAND_TRIGGER_ID);
    }

    public function setTax($tax)
    {
        $this->setAttribute(self::TOTAL_TAX, $tax);
    }

    public function setTotalAmountPending($amount)
    {
        $this->setAttribute(self::TOTAL_AMOUNT_PENDING, $amount);
    }

    public function setFees($fees)
    {
        $this->setAttribute(self::TOTAL_FEES, $fees);
    }

    public function setStatus($status)
    {
        $currentStatus = $this->getStatus();

        Status::validateStatusUpdate($status, $currentStatus);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setScheduled($scheduled)
    {
        return $this->setAttribute(self::SCHEDULED, $scheduled);
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function transaction()
    {
        return $this->morphTo();
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getScheduled()
    {
        return $this->getAttribute(self::SCHEDULED);
    }

    public function shouldValidateAndUpdateBalances(): bool
    {
        return true;
    }

    public function toArrayPublic()
    {
        $arr = parent::toArrayPublic();

        $newArr = [
            self::ID                               => $arr[self::ID],
            self::ENTITY                           => $arr[self::ENTITY],
            'amount_requested'                     => $arr[self::AMOUNT],
            'amount_settled'                       => $arr[self::TOTAL_AMOUNT_SETTLED],
            'amount_pending'                       => $arr[self::TOTAL_AMOUNT_PENDING],
            'amount_reversed'                      => $arr[self::TOTAL_AMOUNT_REVERSED],
            'fees'                                 => $arr[self::TOTAL_FEES],
            'tax'                                  => $arr[self::TOTAL_TAX],
            self::CURRENCY                         => $arr[self::CURRENCY],
            'settle_full_balance'                  => $arr[self::MAX_BALANCE],
            self::STATUS                           => $arr[self::STATUS],
            'description'                          => $arr[self::NARRATION],
            self::NOTES                            => $arr[self::NOTES],
            self::CREATED_AT                       => $arr[self::CREATED_AT],
            self::SCHEDULED                        => $arr[self::SCHEDULED]
        ];

        if (isset($arr[self::SETTLEMENT_ONDEMAND_PAYOUTS]) === true)
        {
            $newArr[self::SETTLEMENT_ONDEMAND_PAYOUTS] = $arr[self::SETTLEMENT_ONDEMAND_PAYOUTS];
        }

        return $newArr;
    }
}
