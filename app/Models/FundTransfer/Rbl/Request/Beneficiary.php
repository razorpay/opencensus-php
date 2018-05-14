<?php

namespace RZP\Models\FundTransfer\Rbl\Request;

use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Rbl\RequestConstants;

class Beneficiary extends Base
{
    protected $input = [];

    protected $urlIdentifier;

    protected $requestTraceCode  = TraceCode::RBL_NODAL_BEN_ADD_REQUEST;

    protected $responseTraceCode = TraceCode::RBL_NODAL_BEN_ADD_RESPONSE;

    public function __construct()
    {
        parent::__construct();

        $this->urlIdentifier = $this->config['ben_add_url_suffix'];
    }

    public function setInput(array $input)
    {
        $this->input = $input;

        return $this;
    }

    public function requestBody(): string
    {
        return json_encode([
            'Beneficiary_Nodal_Account_Registration_Req' => [
                'Header' => [
                    'TranID'      => (string) rand(10000, 99999),
                    'Corp_ID'     => self::CORP_ID,
                    'Maker_ID'    => self::MAKER_ID,
                    'Checker_ID'  => self::CHECKER_ID,
                    'Approver_ID' => self::APPROVER_ID,
                ],
                'Body' => [
                    'Ben_IFSC'          => $this->input[RequestConstants::BEN_IFSC],
                    'Ben_Acct_No'       => $this->input[RequestConstants::BEN_ACCT_NO],
                    'Ben_Name'          => $this->input[RequestConstants::BEN_NAME],
                    'Ben_Address'       => $this->input[RequestConstants::BEN_ADDRESS],
                    'Ben_State'         => 'Karnataka',
                    'Ben_City'          => 'Bangalore',
                    'Ben_PinCd'         => '560030',
                    'Ben_DOB'           => '1960-01-01',
                    'Ben_BankName'      => $this->input[RequestConstants::BEN_BANKNAME],
                    'Ben_BankCd'        => $this->input[RequestConstants::BEN_BANKCD],
                    'Ben_BranchCd'      => $this->input[RequestConstants::BEN_BRANCHCD],
                    'Ben_Email'         => 'test@razorpay.com',
                    'Ben_Mobile'        => '9876543210',
                    'Ben_TrnParticulars' => 'CHANGING',
                    'Ben_PartTrnRmks'   => 'CHANGING',
                    'Issue_BranchCd'    => '0070',
                    'Ben_PAN'           => $this->input[RequestConstants::BEN_PAN],
                    'Ben_UID'           => '777777777777',
                    'Seller_Code'       => '01',
                    'Mode_of_Pay'       => [
                        Mode::NEFT  => [
                            'YN'    => 'Y',
                            'Limit' => [
                                'Daily'     => '100000',
                                'Weekly'    => '700000',
                                'Monthly'   => '3000000'
                            ]
                        ],
                        Mode::RTGS  => [
                            'YN'    => 'Y',
                            'Limit' => [
                                'Daily'     => '10000000',
                                'Weekly'    => '70000000',
                                'Monthly'   => '300000000'
                            ]
                        ],
                        Mode::DD => [
                            'YN'    => 'Y',
                            'Limit' => [
                                'Daily'     => '100',
                                'Weekly'    => '1000',
                                'Monthly'   => '10000'
                            ]
                        ],
                        Mode::FT => [
                            'YN'    => 'Y',
                            'Limit' => [
                                'Daily'     => '100',
                                'Weekly'    => '1000',
                                'Monthly'   => '10000'
                            ]
                        ],
                        Mode::IMPS => [
                            'YN'    => 'Y',
                            'Limit' => [
                                'Daily'     => '200000',
                                'Weekly'    => '1400000',
                                'Monthly'   => '6000000'
                            ]
                        ]
                    ],
                    'Bene_Type'           => 'Sole Proprietor',
                    'Ben_SettlementTerms' => 'Chanincludeged',
                    'Ben_CommercialTerms' => 'ABC001',
                    'KYC_Document'        => [[
                        'KYC_Doc_Id'        => 'Document1',
                        'KYC_Doc_Name'      => $this->input[RequestConstants::KYC_DOC_NAME],
                        'KYC_Doc_Type'      => 'POI',
                        'KYC_Doc_Format'    => 'PDF',
                        'KYC_Doc_Content'   => $this->input[RequestConstants::KYC_DOC_CONTENT]
                    ]],
                    'Remarks'             => 'NODAL BE NINQ UIRYPE NDINGAPPREJ',
                    'Ben_Action'          => '0',
                    'Nodal_Flag'          => 'N',
                    'Ben_ID'              => ''
                ],
                'Signature'     => [
                    'Signature' => 'Signature001'
                ]
            ]
        ]);
    }
}
