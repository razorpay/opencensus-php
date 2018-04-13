<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\AuthType;

class AuthTypeSorter extends Terminal\Sorter
{
    protected $properties = [
        'auth_type',
    ];

    // Arrange card terminals in order of preferred auth type
    public function authTypeSorter($terminals)
    {
        $payment = $this->input['payment'];

        $preferredAuthentications = $payment->getMetadata('preferred_auth');

        // No need to sort unless the method is either card or EMI.
        // or preferredAuthentications is empty.
        if (($payment->isMethodCardOrEmi() === false) or
            (empty($preferredAuthentications) === true))
        {
            return $terminals;
        }

        $orderedTerminals = [];
        $unorderedTerminals = $terminals;

        foreach ($preferredAuthentications as $authType)
        {
            // As the terminals are from the priority list
            // append to the terminal
            //
            $terminals = $unorderedTerminals;

            foreach ($terminals as $key => $terminal)
            {

                if ($terminal->isAuthTypeEnabled($authType) === true)
                {
                    $orderedTerminals[] = $terminal;

                    unset($unorderedTerminals[$key]);
                }
            }
        }

        return $orderedTerminals;
    }
}
