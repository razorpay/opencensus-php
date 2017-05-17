<?php

namespace RZP\Models\LineItem\Tax;

use RZP\Models\Base;
use RZP\Models\LineItem;
use RZP\Models\Tax as TaxModel;

/**
 * Calculator for tax amounts of a line item against a particular tax.
 */
class Calculator
{
    /**
     * Give a line item and set of taxes to be applied on it, it returns
     * the taxable amount of line item.
     * It also handles whether line item amount is tax inclusive or exclusive.
     *
     * @param  LineItem\Entity       $lineItem
     * @param  Base\PublicCollection $taxes
     *
     * @return int
     */
    public static function getTaxableAmountOfLineItem(
        LineItem\Entity $lineItem,
        Base\PublicCollection $taxes): int
    {
        $totalAmount = $lineItem->getAmount() * $lineItem->getQuantity();

        // If line item is tax exclusive, just return the total amount.
        if ($lineItem->isTaxInclusive() === false)
        {
            return $totalAmount;
        }

        // If line item is tax inclusive, calculate the taxable amount (the amount)
        // on which all the tax was applied.
        // Formula:
        // 
        // Taxable Amount = (Total Amount - Accumulative flat taxes)/(1 + Accumulative percent taxes)

        $flatTaxAmount = $percentageTaxAmounts = 0;

        foreach ($taxes as $tax)
        {
            if ($tax->getRateType() === TaxModel\RateType::PERCENTAGE)
            {
                $percentageTaxAmounts += $tax->getRatePercentValue();
            }
            else
            {
                $flatTaxAmount += $tax->getRate();
            }
        }

        $taxableAmount = ($totalAmount - $flatTaxAmount) / ($percentageTaxAmounts + 1);

        return (int) round($taxableAmount);
    }

    /**
     * Get tax amount against a given tax and amount.
     *
     * @param  int             $taxableAmount
     * @param  TaxModel\Entity $tax
     *
     * @return int
     */
    public static function getTaxAmount(
        int $taxableAmount,
        TaxModel\Entity $tax): int
    {
        if ($tax->getRateType() === TaxModel\RateType::PERCENTAGE)
        {
            return (int) (round($taxableAmount * $tax->getRatePercentValue()));
        }
        else
        {
            return $tax->getRate();
        }
    }
}
