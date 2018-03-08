<?php

namespace RZP\Models\Offer;

use RZP\Models\Base;

class Calculator extends Base\Core
{
    /**
     * @var Entity
     */
    protected $offer;

    public function __construct(Entity $offer)
    {
        parent::__construct();

        $this->offer = $offer;
    }

    public function calculateDiscountedAmount(int $amount)
    {
        $discount = $this->calculateDiscount($amount);

        return max(0, ($amount - $discount));
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
            $percentDiscount = $this->offer->getPercentRate()/10000;

            $discount = $percentDiscount * $amount;
        }

        return intval($discount);
    }
}
