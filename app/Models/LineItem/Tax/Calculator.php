<?php

namespace RZP\Models\LineItem\Tax;

use RZP\Models\LineItem;
use RZP\Models\Tax as TaxModel;
use RZP\Exception;

class Calculator
{
    const PERCENTAGE_MULT = 0.01;

    protected $lineItem;
    protected $tax;

    public function __construct(
        LineItem\Entity $lineItem,
        TaxModel\Entity $tax)
    {
        $this->lineItem = $lineItem;
        $this->tax      = $tax;
    }

    public function getTaxAmount()
    {
        switch ($this->tax->getRateType())
        {
            case TaxModel\RateType::FLAT:

                $taxAmount = $this->tax->getRate();

                break;

            case TaxModel\RateType::PERCENTAGE:

                $taxAmount = $this->getTaxAmountForPercentageTypeTax();

                break;

            default:

                throw new LogicException('Invalid tax rate type!');
        }

        return $taxAmount;
    }

    protected function getTaxAmountForPercentageTypeTax()
    {
        $totalAmount = $this->lineItem->getAmount() * $this->lineItem->getQuantity();

        $rate = $this->tax->getRate() * self::PERCENTAGE_MULT;

        if ($this->lineItem->isTaxInclusive() === false)
        {
            return round($totalAmount * $rate * 0.01);
        }
        else
        {
            return round(($totalAmount * $rate)/(100 + $rate));
        }
    }
}
