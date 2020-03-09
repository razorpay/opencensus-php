<?php

namespace RZP\Services\Mock;

use RZP\Models\D2cBureauReport;
use RZP\Services\Mozart as BaseMozart;
use RZP\Models\BankingAccount\Gateway\Rbl;

class Mozart extends BaseMozart
{
    public function sendMozartRequest(
        string $namespace,
        string $gateway,
        string $action,
        array $input,
        int $timeout = self::TIMEOUT,
        int $connectTimeout = self::CONNECT_TIMEOUT,
        string $version = 'v1',
        bool $useMozartMappedInternalErrorCode = false)
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
            case D2cBureauReport\Core::MOZART_GET_REPORT_ACTION:
                {
                    return [
                        'success'   => true,
                        'data'      => [
                            'score'         => '752',
                            'report'        => [
                                'active_accounts'                           => '1',
                                'closed_accounts'                           => '1',
                                'count_of_accounts'                         => '2',
                                'secured_account_outstanding_balance'       => '152000',
                                'total_outstanding_balance'                 => '152000',
                                'un_secured_account_outstanding_balance'    => '0'
                            ],
                            '_raw'          => 'garbage',
                            'raw_report'    => [
                                'INProfileResponse' => [
                                        'CAIS_Account'  => [
                                            'CAIS_Account_DETAILS' => [
                                                'AccountHoldertypeCode' => '1',
                                                'Account_Number'        => 'XXXXXXXX0304',
                                                'Account_Status'        => '11',
                                                'Account_Type'          => '51',
                                                'Amount_Past_Due'       => '501',
                                                'CAIS_Account_History'  => [
                                                    'Asset_Classification'  => '?',
                                                    'Days_Past_Due'         => '14',
                                                    'Month'                 => '08',
                                                    'Year'                  => '2019'
                                                ],
                                            ],
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
