<?php

namespace RZP\Models\Pricing\Calculator\Tax\MY;

use App;
use RZP\Models\Pricing\Calculator\Tax;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;

class Calculator extends Tax\Base
{
    public function calculateTax($fee): array
    {
        return [FeeBreakupName::TAX, 0, 0];
    }
}
