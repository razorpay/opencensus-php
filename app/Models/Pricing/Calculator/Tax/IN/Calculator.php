<?php

namespace RZP\Models\Pricing\Calculator\Tax\IN;

use App;

use RZP\Models\Pricing\Calculator\Tax;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;
use RZP\Models\Pricing\Calculator\Tax\IN\Constants as INConstants;

class Calculator extends Tax\Base
{
    public function calculateTax($fee): array
    {
        $totalTaxes = 0;

        $totalPercentage = 0;

        $eligibleForGst = Utils::isEligibleForGst($fee, $this->entity, $this->amount);

        $this->taxComponents = Utils::getTaxComponents($this->merchant);

        foreach ($this->taxComponents as $name => $percentage)
        {
            if (in_array($name, [FeeBreakupName::CGST, FeeBreakupName::SGST], true) === true)
            {
                $taxValue = ((int) round(($percentage * $fee) / 10000));
            }
            else if ($name === FeeBreakupName::IGST)
            {
                // Calculate as per cgst percentage, and double it to get the exact tax value.
                // We do this so that if this value needs to be split later into sgst+cgst, it is an even value
                $calculationPercentage = INConstants::CGST_PERCENTAGE;

                $taxValue = 2 * ((int) round(($calculationPercentage * $fee) / 10000));
            }

            $taxValue = ($eligibleForGst === true) ? $taxValue : 0;

            $totalTaxes += $taxValue;

            $totalPercentage += $percentage;
        }

        return [FeeBreakupName::TAX, $totalPercentage, $totalTaxes];
    }
}
