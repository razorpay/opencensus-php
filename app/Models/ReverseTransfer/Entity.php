<?php

namespace RZP\Models\ReverseTransfer;

use RZP\Models\Base;
use RZP\Models\Transfer;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const TRANSFER_ID       = 'transfer_id';
    const MERCHANT_ID       = 'merchant_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const BASE_AMOUNT       = 'base_amount';
    const TRANSACTION_ID    = 'transaction_id';

    protected static $sign = 'revtrf';

    protected $entity = 'reverse_transfer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TRANSFER_ID,
        self::TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::TRANSFER_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::AMOUNT        => 'int',
        self::BASE_AMOUNT   => 'int'
    ];

    protected $amounts = [
        self::AMOUNT,
        self::BASE_AMOUNT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::TRANSFER_ID,
    ];

    // -------------------- Relations ---------------------------

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function transfer()
    {
        return $this->belongsTo('RZP\Models\Transfer\Entity');
    }

    // -------------------- End Relations -----------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    public function setBaseAmount()
    {
        $transfer = $this->transfer;

        $amount = $this->getAmount();

        $unreversedAmount = $transfer->getAmountUnreversed();

        if ($amount === $unreversedAmount)
        {
            $baseAmount = $transfer->getBaseAmountUnreversed();
        }
        else
        {
            $conversionRate = $transfer->getCurrencyConversionRate();

            $baseAmount = (int) floor($amount * $conversionRate);
        }

        $this->setAttribute(self::BASE_AMOUNT, $baseAmount);
    }

    public function setPublicTransferIdAttribute(array & $attributes)
    {
        $transferId = $this->getAttribute(self::TRANSFER_ID);

        if ($transferId !== null)
        {
            $attributes[self::TRANSFER_ID] = Transfer\Entity::getSignedId($transferId);
        }
    }
}
