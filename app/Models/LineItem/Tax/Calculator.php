<?php

namespace RZP\Models\LineItem\Tax;

use RZP\Models\Base;
use RZP\Models\LineItem;
use RZP\Models\Tax as TaxModel;
use RZP\Exception\BadRequestException;
use RZP\Error\ErrorCode;

/**
 * Calculator for tax amounts of a line item against a particular tax.
 */
class Calculator
{
    /**
     * Give a line item and set of taxes to be applied on it, it returns the taxable amount of line item. It also
     * handles whether line item amount is tax inclusive or exclusive.
     * @param  LineItem\Entity       $lineItem
     * @param  Base\PublicCollection $taxes
     * @return int
     * @throws BadRequestException
     */
    public static function getTaxableAmountOfLineItem(LineItem\Entity $lineItem, Base\PublicCollection $taxes): int
    {
        $totalAmount = $lineItem->getAmount() * $lineItem->getQuantity();

        // If line item is tax exclusive, just return the total amount.
        if ($lineItem->isTaxInclusive() === false)
        {
            return $totalAmount;
        }

        //
        // If line item is tax inclusive, calculate the taxable amount (the amount)
        // on which all the tax was applied.
        // Formula:
        //
        // Taxable Amount = (Total Amount - Accumulative flat taxes)/(1 + Accumulative percent taxes)
        //
        $flatTaxAmount = $percentageTaxAmounts = 0;

        foreach ($taxes as $tax)
        {
            self::assertTaxEntityIsValid($tax);

            if ($tax->getRateType() === TaxModel\RateType::PERCENTAGE)
            {
                $percentageTaxAmounts += $tax->getRatePercentValue();
            }
            else
            {
                $flatTaxAmount += ($tax->getRate() * $lineItem->getQuantity());
            }
        }

        $taxableAmount = ($totalAmount - $flatTaxAmount) / ($percentageTaxAmounts + 1);

        //
        // In case of tax inclusive line item amounts and flat taxes, there is a chance taxable amount would come as
        // negative which is a validation error.
        //
        if ($taxableAmount <= 0)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ITEM_TAX_DETAILS);
        }

        return (int) round($taxableAmount);
    }

    public static function getTaxAmount(LineItem\Entity $lineItem, int $taxableAmount, $tax): float
    {
        self::assertTaxEntityIsValid($tax);

        if ($tax->getRateType() === TaxModel\RateType::PERCENTAGE)
        {
            return $taxableAmount * $tax->getRatePercentValue();
        }
        else
        {
            return $lineItem->getQuantity() * $tax->getRate();
        }
    }

    public static function assertTaxEntityIsValid($tax)
    {
        assertTrue(($tax instanceof TaxModel\Entity) or ($tax instanceof Entity));
    }
}
