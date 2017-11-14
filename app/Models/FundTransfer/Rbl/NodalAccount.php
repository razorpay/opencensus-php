<?php

namespace RZP\Models\FundTransfer\Rbl;

use App;
use Requests;
use Config;
use Requests_Hooks;

use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Base as NodalBase;

class NodalAccount extends NodalBase\NodalAccount
{
    const TIMEOUT = '240';

    protected $headers = [];

    protected $options = [];

    protected $url = '';

    protected $trace;

    public function __construct()
    {
        parent::__construct();

        $this->config = Config::get('nodal.rbl');

        $this->username = $this->config['username'];

        $this->password = $this->config['password'];

        $clientId = $this->config['client_id'];

        $clientSecret = $this->config['client_password'];

        $clientCredArray = [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ];

        $this->baseUrl = $this->config['url'];

        $this->clientCreds = http_build_query($clientCredArray);

        $this->headers = [
            'Content-Type' => 'application/json'
        ];

        $this->options = $this->getRequestOptions();
    }

    public function addBeneficiary(array $input): array
    {
        $content = $this->getAddBeneficiaryData($input);

        $this->trace->info(TraceCode::RBL_NODAL_BEN_ADD_REQUEST, $content);

        $url = $this->baseUrl . $this->config['ben_add_url_suffix'] . $this->clientCreds;

        $responseArray = $this->getResponse($content, $url);

        foreach ($responseArray as $resp)
        {
            if ((empty($resp['Body']['Status']) === false) and
                ($resp['Bodyq']['Status'] === 'Failure'))
            {
                $this->trace->error(TraceCode::RBL_NODAL_BEN_ADD_RESPONSE, $responseArray);

                return $responseArray;
            }
        }

        $this->trace->info(TraceCode::RBL_NODAL_RESPONSE, $responseArray);

        return $responseArray;
    }

    public function initiateTransfer(string $amount): array
    {
        $content = $this->getTransferData($amount);

        $this->trace->info(TraceCode::RBL_NODAL_TRANSFER_REQUEST, $content);

        $url = $this->baseUrl . $this->config['fund_transfer_url_sufffix'] . $this->clientCreds;

        $responseArray = $this->getResponse($content, $url);

        foreach ($responseArray as $resp)
        {
            if ((empty($resp['Header']['Status']) === false) and
                ($resp['Bodyq']['Status'] === 'FAILED'))
            {
                $this->trace->error(TraceCode::RBL_NODAL_TRANSFER_RESPONSE, $responseArray);

                return $responseArray;
            }
        }

        $this->trace->info(TraceCode::RBL_NODAL_RESPONSE, $responseArray);

        return $responseArray;
    }

    protected function getRequestOptions(): array
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlSslOpts']);

        $options = [
            'hooks'   => $hooks,
            'timeout' => self::TIMEOUT,
            'auth'    => [
                $this->username,
                $this->password
            ],
        ];

        return $options;
    }

    public function setCurlSslOpts($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());

        curl_setopt($curl, CURLOPT_SSLKEY, $this->getClientCertificateKey());
    }

    protected function getClientCertificate(): string
    {
        $certPath = $this->getGatewayCertDirPath();

        $certFile = $certPath . '/' . $this->getClientCertificateName();

        // Download cert file from vault if already not present and store locally
        if (file_exists($certFile) === false)
        {
            $cert = $this->config['client_certificate'];

            $cert = str_replace('\n', PHP_EOL, $cert);

            file_put_contents($certFile, $cert);
        }

        return $certFile;
    }

    protected function getClientCertificateKey(): string
    {
        $certPath = $this->getGatewayCertDirPath();

        $certFile = $certPath . '/' . $this->getClientCertificateKeyName();

        // Download cert key file from vault if already not present and store locally
        if (file_exists($certFile) === false)
        {
            $key = $this->config['client_certificate_key'];

            $key = str_replace('\n', PHP_EOL, $key);

            file_put_contents($certFile, $key);
        }

        return $certFile;
    }

    protected function getClientCertificateName(): string
    {
        return $this->config['certificate_name'];
    }

    protected function getGatewayCertDirPath(): string
    {
        return $this->config['certificate_path'];
    }

    protected function getClientCertificateKeyName(): string
    {
        return $this->config['certificate_key_name'];
    }

    protected function getResponse(array $content, string $url): array
    {
        $response = Requests::post(
            $url,
            $this->headers,
            json_encode($content),
            $this->options);

        $responseArray = json_decode($response->body, true);

        return $responseArray;
    }

    protected function getTransferData(string $amount): array
    {
        $content = [
            'Single_Payment_Corp_Req' => [
                'Header' => [
                    'TranID'      => (string) rand(10000, 99999),
                    'Corp_ID'     => 'RZPAY',
                    'Maker_ID'    => 'M001',
                    'Checker_ID'  => 'C001',
                    'Approver_ID' => 'A001',
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

        return $content;
    }

    protected function getAddBeneficiaryData(array $input): array
    {
        $content = [
            'Beneficiary_Nodal_Account_Registration_Req' => [
                'Header' => [
                    'TranID'      => (string) rand(10000, 99999),
                    'Corp_ID'     => 'RZPAY',
                    'Maker_ID'    => 'M001',
                    'Checker_ID'  => 'C001',
                    'Approver_ID' => 'A001',
                ],
                'Body' => [
                    'Ben_IFSC'           => $input[RequestConstants::BEN_IFSC],
                    'Ben_Acct_No'        => $input[RequestConstants::BEN_ACCT_NO],
                    'Ben_Name'           => $input[RequestConstants::BEN_NAME],
                    'Ben_Address'        => $input[RequestConstants::BEN_ADDRESS],
                    'Ben_State'          => 'karnataka',
                    'Ben_City'           => 'Bengaluru',
                    'Ben_PinCd'          => '560030',
                    'Ben_DOB'            => '1960-01-01',
                    'Ben_BankName'       => $input[RequestConstants::BEN_BANKNAME],
                    'Ben_BankCd'         => $input[RequestConstants::BEN_BANKCD],
                    'Ben_BranchCd'       => $input[RequestConstants::BEN_BRANCHCD],
                    'Ben_Email'          => 'test@razorpay.com',
                    'Ben_Mobile'         => '9876543210',
                    'Ben_TrnParticulars' => 'CHANGING',
                    'Ben_PartTrnRmks'    => 'CHANGING',
                    'Issue_BranchCd'     => '0070',
                    'Ben_PAN'            => $input[RequestConstants::BEN_PAN],
                    'Ben_UID'            => '777777777777',
                    'Seller_Code'        => '01',
                    'Mode_of_Pay'        => [
                        Mode::NEFT => [
                            'YN' => 'Y',
                            'Limit' => [
                                'Daily'   => '100000',
                                'Weekly'  => '700000',
                                'Monthly' => '3000000'
                            ]
                        ],
                        Mode::RTGS=> [
                            'YN' => 'Y',
                            'Limit' => [
                                'Daily'   => '10000000',
                                'Weekly'  => '70000000',
                                'Monthly' => '300000000'
                            ]
                        ],
                        Mode::DD => [
                            'YN' => 'Y',
                            'Limit' => [
                                'Daily'   => '100',
                                'Weekly'  => '1000',
                                'Monthly' => '10000'
                            ]
                        ],
                        Mode::FT => [
                            'YN' => 'Y',
                            'Limit' => [
                                'Daily'   => '100',
                                'Weekly'  => '1000',
                                'Monthly' => '10000'
                            ]
                        ],
                        Mode::IMPS => [
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
                        'KYC_Doc_Name'    => $input[RequestConstants::KYC_DOC_NAME],
                        'KYC_Doc_Type'    => 'POI',
                        'KYC_Doc_Format'  => 'PDF',
                        'KYC_Doc_Content' => $input[RequestConstants::KYC_DOC_CONTENT]
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
