<?php

namespace RZP\Models\Pricing\Calculator\Tax\SG;

use App;

use RZP\Models\Pricing\Calculator\Tax;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;

class Calculator extends Tax\Base
{
    const GST_PERCENTAGE = 900;

    public function calculateTax($fee): array
    {
            $calculationPercentage = self::GST_PERCENTAGE;

            $taxValue = ((int)round(($calculationPercentage * $fee) / 10000));

            return [FeeBreakupName::TAX, $calculationPercentage, $taxValue];

    }
}
