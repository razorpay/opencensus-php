<?php

namespace RZP\Models\LineItem\Tax;

use RZP\Models\LineItem;
use RZP\Models\Tax as TaxModel;
use RZP\Exception;

/**
 * Calculator for tax amounts of a line item against a particular tax.
 */
class Calculator
{
    protected $lineItem;
    protected $tax;

    public function __construct(
        LineItem\Entity $lineItem,
        TaxModel\Entity $tax)
    {
        $this->lineItem = $lineItem;
        $this->tax      = $tax;
    }

    /**
     * Calculates and returns tax amount for a line item against
     * given tax.
     *
     * @return int
     */
    public function getTaxAmount(): int
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

    /**
     * Gets tax amount when tax rate is of type percentage.
     *
     * @return int
     */
    protected function getTaxAmountForPercentageTypeTax(): int
    {
        $totalAmount = $this->lineItem->getAmount() * $this->lineItem->getQuantity();

        // Because we store percentage tax rate multiplied by 100 in our tables.
        $rate = $this->tax->getRate() * 0.01;

        // If line item's amount is not tax inclusive, then tax amount
        // is simply rate %.
        // But if it's tax inclusive, we just calculate back the taxed amount
        // and store it. The item's total amount is still tax inclusive.

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
