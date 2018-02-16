<?php

namespace RZP\Models\Offer;

use RZP\Exception;
use RZP\Models\Base;

class Calculator extends Base\Core
{
    /**
     * @var Entity
     */
    protected $offer;

    const MIN_PAYMENT_AMOUNT = 100;

    public function __construct(Entity $offer)
    {
        parent::__construct();

        $this->offer = $offer;
    }

    public function calculateDiscountedAmount(int $amount)
    {
        $discount = $this->calculateDiscount($amount);

        $discountedAmount = ($amount - $discount);

        //
        // Needs to be handled better later. Maybe return the original
        // amount, maybe handle zero-rupee payment in auth flow. Not
        // doing any of that right now. When in doubt, throw an exception.
        //
        if ($discountedAmount < self::MIN_PAYMENT_AMOUNT)
        {
            throw new Exception\LogicException(
                'Discounted amount less than minimum payment amount',
                null,
                [
                    'discounted_amount'  => $discountedAmount,
                    'min_payment_amount' => self::MIN_PAYMENT_AMOUNT,
                ]);
        }

        return $discountedAmount;
    }

    public function calculateDiscount(int $amount)
    {
        if (($this->offer->getMinAmount() !== null) and
            ($amount < $this->offer->getMinAmount()))
        {
            return 0;
        }

        $discount = $this->getRawDiscount($amount);

        if ($this->offer->getMaxCashback() !== null)
        {
            $discount = min($this->offer->getMaxCashback(), $discount);
        }

        return $discount;
    }

    protected function getRawDiscount(int $amount)
    {
        $discount = 0;

        if ($this->offer->getFlatCashback() !== null)
        {
            $discount = $this->offer->getFlatCashback();
        }
        else if ($this->offer->getPercentRate() !== null)
        {
            $discountFactor = $this->offer->getPercentRate() * $amount;

            $discount = $discountFactor / 10000;
        }

        return round($discount);
    }
}
