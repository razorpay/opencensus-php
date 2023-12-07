<?php

namespace RZP\Models\Transfer\Payment;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Exception\LogicException;
use Illuminate\Database\Eloquent\Relations;

class Entity extends Base\PublicEntity
{
    const ID           = 'id';
    const PAYMENT_ID   = 'payment_id';
    const AMOUNT       = 'amount';
    const AMOUNT_TRANSFERRED = 'amount_transferred';


    protected $entity = Constants\Entity::TRANSFER_PAYMENT;

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::AMOUNT_TRANSFERRED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::AMOUNT_TRANSFERRED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $fillable = [
        self::ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::AMOUNT_TRANSFERRED,
    ];

    protected $defaults = [
        self::AMOUNT_TRANSFERRED => 0,
    ];


    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getAmountTransferred()
    {
        return $this->getAttribute(self::AMOUNT_TRANSFERRED);
    }

    public function transferAmount(int $amount)
    {
        $amountUntransferred = $this->getAmountUntransferred();

        if ($amount > $amountUntransferred)
        {
            throw new LogicException(
                'Transfer amount should be less than or equal to amount not transferred yet',
                null,
                [
                    'amount'                => $amount,
                    'amount_untransferred'  => $amountUntransferred,
                    'payment_id'            => $this->getId(),
                ]);
        }

        $amountTransferred = $this->getAmountTransferred() + $amount;

        $this->setAttribute(self::AMOUNT_TRANSFERRED, $amountTransferred);
    }

    public function getAmountUntransferred()
    {
        return $this->getAmount() - $this->getAmountTransferred();
    }

    public function decrementAmountTransferred(int $amount)
    {
        $this->decrement(self::AMOUNT_TRANSFERRED, $amount);
    }

    public function isTransferred()
    {
        return (($this->getAttribute(self::AMOUNT_TRANSFERRED) > 0) === true);
    }
}
