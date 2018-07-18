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

    /**
     * @param int $amount
     * @param $percentDiscount
     * @return int
     * @throws Exception\LogicException
     *
     *
     * Here percent discount can come from input if offer doesn't have
     * information of discount. For example in case of emi offers the
     * discount information is in emi plans and not in offers. So percent
     * discount is passed to calculate the discount.
     */
    public function calculateDiscountedAmount(int $amount, $percentDiscount): int
    {
        $discount = $this->calculateDiscount($amount, $percentDiscount);

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

    /**
     * @param int $amount
     * @param $percentDiscount
     * @return int|mixed
     *
     * Here percent discount can come from input if offer doesn't have
     * information of discount. For example in case of emi offers the
     * discount information is in emi plans and not in offers. So percent
     * discount is passed to calculate the discount.
     */
    public function calculateDiscount(int $amount, $percentDiscount): int
    {
        if ($percentDiscount !== null)
        {
            return $this->getPercentDiscount($amount, $percentDiscount);
        }

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
            $discount = $this->getPercentDiscount($amount, $this->offer->getPercentRate());
        }

        return $discount;
    }

    protected function getPercentDiscount(int $amount, int $percent): int
    {
        $discount = $amount * $percent / 10000;

        return intval(number_format(round($discount), 2, '.', ''));
    }
}
