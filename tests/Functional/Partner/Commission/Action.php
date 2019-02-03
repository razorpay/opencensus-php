<?php

namespace Functional\Partner\Commission;

use RZP\Models\Partner\Commission\Calculator;

class Action
{
    public function BptVjGnFv6ITBm(array $postSetupData, array & $postActionData)
    {
        $calculator = new Calculator($postSetupData['source_entity']);

        $calculator->calculate();

        $postActionData['calculator'] = $calculator;
    }
}
