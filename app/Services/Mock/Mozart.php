<?php

namespace RZP\Services\Mock;

use RZP\Services\Mozart as BaseMozart;
use RZP\Models\BankingAccount\Gateway\Rbl;

class Mozart extends BaseMozart
{
    public function sendMozartRequest(
        string $namespace,
        string $gateway,
        string $action,
        array $input,
        $version = 'v1')
    {
        switch ($action)
        {
            case Rbl\Action::ACCOUNT_BALANCE:
                {
                    return [
                        'data'    => [
                            'success' => true,
                            Rbl\Fields::GET_ACCOUNT_BALANCE => [
                                Rbl\Fields::BODY            => [
                                    Rbl\Fields::BAL_AMOUNT  => [
                                        Rbl\Fields::AMOUNT_VALUE => '0'
                                    ]
                                ]
                            ]

                        ]
                    ];
                }

            default:
                {
                    return [
                        'success' => true,
                    ];
                }
        }
    }
}
