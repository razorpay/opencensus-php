<?php

namespace RZP\Services\Mock;

use RZP\Models\D2cBureauReport;
use RZP\Models\Gateway\Downtime;
use RZP\Services\Mozart as BaseMozart;
use RZP\Models\BankingAccount\Gateway\Rbl;

class Mozart extends BaseMozart
{
    public function sendMozartRequest(
        string $namespace,
        string $gateway,
        string $action,
        array $input,
        string $version = 'v1',
        bool $useMozartMappedInternalErrorCode = false,
        int $timeout = self::TIMEOUT,
        int $connectTimeout = self::CONNECT_TIMEOUT,bool $logResponse = true, bool $addEntities = true)
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
                            'ntc_score'     => null,
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
            case Downtime\Core::MOZART_GET_DOWNTIME_ACTION:
            {
                if($input["transaction_type"] === "Corporate")
                {
                    return [
                        'success'   => true,
                        'data'      => [
                            "bankList" => [
                                'ABB0235' => 'Active',
                                'ABMB0213' => 'Active',
                                'AMBB0208' => 'Blocked',
                                'AGRO02' => 'Active',
                                'BNP003' => 'Active',
                                'BIMB0340' => 'Active',
                                'BKRM0602' => 'Blocked',
                                'BMMB0342' => 'Blocked',
                                'BCBB0235' => 'Active',
                                'CIT0218' => 'Active',
                                'DBB0219' => 'Active',
                                'HSBC0223' => 'Active',
                                'HLB0224' => 'Active',
                                'KFH0346' => 'Active',
                                'MBB0228' => 'Active',
                                'OCBC0229' => 'Blocked',
                                'PBB0233' => 'Active',
                                'PBB0234' => 'Active',
                                'RHB0218' => 'Active',
                                'SCB0215' => 'Blocked',
                                'UOB0228' => 'Active',
                            ]
                        ]
                    ];
                }
                else
                {
                    return  [
                        'success' => true,
                        'data'    => [
                            "bankList" => [
                                'ABB0233'   => 'Active',
                                'ABMB0212'  => 'Active',
                                'AMBB0209'  => 'Active',
                                'AGRO01'    => 'Active',
                                'BIMB0340'  => 'Active',
                                'BKRM0602'  => 'Active',
                                'BMMB0341'  => 'Blocked',
                                'BOCM01'    => 'Active',
                                'BSN0601'   => 'Active',
                                'BCBB0235'  => 'Active',
                                'HSBC0223'  => 'Blocked',
                                'HLB0224'   => 'Active',
                                'KFH0346'   => 'Active',
                                'MBB0228'   => 'Active',
                                'MB2U0227'  => 'Blocked',
                                'OCBC0229'  => 'Active',
                                'PBB0233'   => 'Active',
                                'RHB0218'   => 'Blocked',
                                'SCB0216'   => 'Active',
                                'UOB0226'   => 'Active'
                            ]
                        ]
                    ];
                }
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
