<?php

namespace Functional\Partner\Commission;

use RZP\Models\Partner\Commission\Calculator;

class Action
{
    /**
     * Gets triggered if the test's action function is not defined here explictly. Calculates commission.
     *
     * @param array $postSetupData
     * @param array $postActionData
     */
    public function defaultAction(array $postSetupData, array & $postActionData)
    {
        $calculator = new Calculator($postSetupData['source_entity']);

        $calculator->calculate();

        $postActionData['calculator'] = $calculator;
    }
}
