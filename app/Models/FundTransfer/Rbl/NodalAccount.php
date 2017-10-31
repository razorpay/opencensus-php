<?php

namespace RZP\Models\FundTransfer\Rbl;

use Requests;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Base as NodalBase;

class NodalAccount extends NodalBase\NodalAccount
{
    const DEFAULT_MODE = Mode::RTGS;

    protected $headers = [];

    protected $options = [];

    protected $url = '';

    public function __construct()
    {
        parent::__construct();

        $username = Config::get('nodal.rbl.username');

        $password = Config::get('nodal.rbl.password');

        $clientId = Config::get('nodal.rbl.client_id');

        $clientSecret = Config::get('nodal.rbl.client_password');

        $this->url = Config::get('nodal.rbl.url') . '?client_id=' . $clientId . 'client_secret=' . $clientSecret;

        $this->headers = [
            'Content-Type' => 'application/json'
        ];

        $this->options = [
            'auth' => [
                $username, $password
            ]
        ];
    }

    public function addBeneficiary(array $input)
    {
        $content = $this->getAddBeneficiaryData($input);

        return $this->getResponse($content);
    }

    public function initiateTransfer(string $amount)
    {
        $content = $this->getTransferData($amount);

        return $this->getResponse($content);
    }

    protected function getResponse(string $content)
    {
        $response = Requests::post(
            $this->url,
            $this->headers,
            $content,
            $this->options);

        return json_decode($response->body, true);
    }

    protected function getTransferData(string $amount)
    {
        $content = [
            'Single_Payment_Corp_Req' => [
                'Header' => [
                    'TranID'     => rand(10000, 99999),
                    'Corp_ID'    => 'RZPAY',
                    'Maker_ID'   => 'M001',
                    'Checker_ID' => 'C001',
                ],
                'Body' => [
                    'Amount'               => $amount,
                    'Debit_Acct_No'        => '409000444755',
                    'Debit_Acct_Name'      => 'Razorpay',
                    'Debit_IFSC'           => 'RATN',
                    'Debit_Mobile'         => '9876543210',
                    'Debit_TrnParticulars' =>  '',
                    'Debit_PartTrnRmks'    => '',
                    'Mode_of_Pay'          => $this->getTransferMode($amount),
                    'Remarks'              => 'WE',
                    'RptCode'              => '',
                    'Ben_ID'               => '',
                    // Hardcode value of our Nodal Account
                ],
                'Signature' =>[
                    'Signature' => 'Signature'
                ],
            ]
        ];

        return json_encode($content);
    }

    protected function getAddBeneficiaryData(array $input)
    {
        $content = [
            'Beneficiary_Nodal_Account_Registration_Req' => [
                'Header' => [
                    'TranID'      => rand(10000, 99999),
                    'Corp_ID'     => 'RZPAY',
                    'Maker_ID'    => 'M001',
                    'Checker_ID'  => 'C001',
                    'Approver_ID' => 'A001',
                ],
                'Body' => [
                    'Ben_IFSC'           => $input['ben_ifsc'],
                    'Ben_Acct_No'        => $input['ben_acct_no'],
                    'Ben_Name'           => $input['ben_name'],
                    'Ben_Address'        => $input['ben_address'],
                    'Ben_State'          => 'karnataka',
                    'Ben_City'           => 'Bengaluru',
                    'Ben_PinCd'          => '560030',
                    'Ben_DOB'            => '1960-01-01',
                    'Ben_BankName'       => $input['ben_bankName'],
                    'Ben_BankCd'         => $input['Ben_BankCd'],
                    'Ben_BranchCd'       => $input['Ben_BranchCd'],
                    'Ben_Email'          => 'test@razorpay.com',
                    'Ben_Mobile'         => '9876543210',
                    'Ben_TrnParticulars' => 'CHANGING',
                    'Ben_PartTrnRmks'    => 'CHANGING',
                    'Issue_BranchCd'     => '0070',
                    'Ben_PAN'            => $input['ben_pan'],
                    'Ben_UID'            => '777777777777',
                    'Seller_Code'        => '01',
                    'Mode_of_Pay'        => [
                        'NEFT' => [
                            'YN' => 'Y',
                            'Limit' => [
                                'Daily'   => '100000',
                                'Weekly'  => '700000',
                                'Monthly' => '3000000'
                            ]
                        ],
                        'RTGS'=> [
                            'YN' => 'Y',
                            'Limit' => [
                                'Daily'   => '10000000',
                                'Weekly'  => '70000000',
                                'Monthly' => '300000000'
                            ]
                        ],
                        'DD' => [
                            'YN' => 'Y',
                            'Limit' => [
                                'Daily'   => '100',
                                'Weekly'  => '1000',
                                'Monthly' => '10000'
                            ]
                        ],
                        'FT' => [
                            'YN' => 'Y',
                            'Limit' => [
                                'Daily'   => '100',
                                'Weekly'  => '1000',
                                'Monthly' => '10000'
                            ]
                        ],
                        'IMPS' => [
                            'YN' => 'Y',
                            'Limit' => [
                                'Daily'   => '200000',
                                'Weekly'  => '1400000',
                                'Monthly' => '6000000'
                            ]
                        ]
                    ],
                    'Bene_Type'           => 'Sole Proprietor',
                    'Ben_SettlementTerms' => 'Chanincludeged',
                    'Ben_CommercialTerms' => 'ABC001',
                    'KYC_Document' => [
                        'KYC_Doc_Id'      => 'Document1',
                        'KYC_Doc_Name'    => $input['kyc_doc_name'],
                        'KYC_Doc_Type'    => 'POI',
                        'KYC_Doc_Format'  => 'PDF',
                        'KYC_Doc_Content' => $input['kyc_doc_content']
                    ],
                    'Remarks'    => 'NODAL BE NINQ UIRYPE NDINGAPPREJ',
                    'Ben_Action' => '0',
                    'Nodal_Flag' => 'N',
                    'Ben_ID'     => ''
                ],
                'Signature' => [
                    'Signature' => 'Signature001'
                ]
            ]
        ];

        return $content;
    }
}
