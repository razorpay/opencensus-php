<?php

namespace Functional\Partner\Commission;

use RZP\Models\Partner\Commission\Calculator;

class Action
{
    public function testImplicitVariable(array $postSetupData, array & $postActionData)
    {
        $calculator = new Calculator($postSetupData['source_entity']);

        $calculator->calculate();

        $postActionData['calculator'] = $calculator;
    }
}
