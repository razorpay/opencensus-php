<?php

namespace RZP\Gateway\Upi\Yesbank\Mock;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Yesbank;

class Gateway extends Yesbank\Gateway
{
    use Base\Mock\GatewayTrait;

    public function getQrRefId($input): string
    {
        if ($input['terminal']['merchant_id'] === 'LiveAccountMer')
        {
            return throw new Exception\RuntimeException('Invalid Response from Mozart');
        }
        return '107611570997';
    }

    public function getGatewayTimeTakenMs()
    {
        return 50;
    }

    public function getQrPaymentStatus($input)
    {
        $result = [
            'data'              => [
                '_raw'     => 'raw data',
                'meta'     => [
                    'request'  => [
                        'content' => 'raw content',
                        'plain'   => 'YES0000010861259|RZPYMgZ6klrW305KHhqrv2||||||||||||NA|NA'
                    ],
                    'response' => [
                        'content' => [
                            'Add10'               => 'NA',
                            'Add2'                => 'NA',
                            'Add3'                => 'SAVINGS',
                            'Add4'                => 'SAVINGS',
                            'Add5'                => 'NA',
                            'Add6'                => 'NA',
                            'Add7'                => 'NA',
                            'Add8'                => 'NA',
                            'Add9'                => 'NA',
                            'Amount'              => '1.0',
                            'ApprovalNumber'      => '933462',
                            'CustRefNo'           => '326836533213',
                            'MerchantRefNo'       => 'RZPY' . $input['qr_code']['id'] . 'qrv2',
                            'NpciRefId'           => 'NA',
                            'NpciTxnId'           => 'YBL144231ce5eb64a58998c6e6f14fa263a',
                            'PayeeAadhaar'        => 'NA',
                            'PayeeAcountNo'       => 'SCRUBBED_PII (17)',
                            'PayeeIfsc'           => 'YESB0000022',
                            'PayeeName'           => 'SCRUBBED_PII (17)',
                            'PayeeVpa'            => 'randomvpa@yesbank',
                            'PayerAccountName'    => 'SCRUBBED_PII (17)',
                            'PayerAccountNo'      => 'SCRUBBED_PII (17)',
                            'PayerIfsc'           => 'SBIN0012159',
                            'PayerVpa'            => '7747931160@ybl',
                            'ResponseCode'        => '00',
                            'Status'              => 'SUCCESS',
                            'StatusDescription'   => 'Transaction success',
                            'TimeOutTxnStatus'    => 'NA',
                            'TxnAuthDate'         => '2023:09:25 20:12:13',
                            'YblTxnId'            => '13508165557',
                            'TransactionAuthDate' => '2023:09:25 20:12:13',
                            'PayerNote'           => 'PaymenttoMitasha',
                            'PayeeAadhar'         => 'NA',
                            'PayeeAcountNumber'   => 'SCRUBBED_PII (10)',
                            'PayerIfscCode'       => 'SCRUBBED_PII (10)'
                        ],
                        'plain'   => [
                            'Add10'             => 'NA',
                            'Add2'              => 'NA',
                            'Add3'              => 'PaymenttoMitasha',
                            'Add4'              => 'SAVINGS',
                            'Add5'              => 'NA',
                            'Add6'              => 'NA',
                            'Add7'              => 'NA',
                            'Add8'              => 'NA',
                            'Add9'              => 'NA',
                            'Amount'            => '1.0',
                            'ApprovalNumber'    => '933462',
                            'CustRefNo'         => '326836533213',
                            'MerchantRefNo'     => 'RZPY' . $input['qr_code']['id'] . 'qrv2',
                            'NpciRefId'         => 'NA',
                            'NpciTxnId'         => 'YBL144231ce5eb64a58998c6e6f14fa263a',
                            'PayeeAadhaar'      => 'NA',
                            'PayeeAcountNo'     => 'SCRUBBED_PII (17)',
                            'PayeeIfsc'         => 'YESB0000022',
                            'PayeeName'         => 'SCRUBBED_PII (17)',
                            'PayeeVpa'          => 'randomvpa@yesbank',
                            'PayerAccountName'  => 'SCRUBBED_PII (17)',
                            'PayerAccountNo'    => 'SCRUBBED_PII (17)',
                            'PayerIfsc'         => 'SBIN0012159',
                            'PayerVpa'          => '7747931160@ybl',
                            'ResponseCode'      => '00',
                            'Status'            => 'SUCCESS',
                            'StatusDescription' => 'Transaction success',
                            'TimeOutTxnStatus'  => 'NA',
                            'TxnAuthDate'       => '2023:09:25 20:12:13',
                            'YblTxnId'          => '13508165557'
                        ]
                    ]
                ],
                'payment'  => [
                    'amount_authorized' => 4000,
                    'currency'          => 'INR'
                ],
                'status'   => 'payment_successful',
                'terminal' => [
                    'gateway' => 'upi_yesbank',
                    'vpa'     => 'randomvpa@yesbank'
                ],
                'upi'      => [
                    'account_number'      => 'SCRUBBED_PII (17)',
                    'gateway_payment_id'  => '13508165557',
                    'gateway_status_code' => '00',
                    'ifsc'                => 'SBIN0012159',
                    'merchant_reference'  => 'RZPY' . $input['qr_code']['id'] . 'qrv2',
                    'npci_reference_id'   => '326836533213',
                    'npci_txn_id'         => 'YBL144231ce5eb64a58998c6e6f14fa263a',
                    'status_code'         => '00',
                    'vpa'                 => '7747931160@ybl'
                ]
            ],
            'error'             => null,
            'external_trace_id' => '7f44e3b4c264d0dabc78e9f26972aaf2',
            'mozart_id'         => 'ck8u2e8t16k08odib4j0',
            'next'              => [],
            'success'           => true,
            'qr_status_check'   => true
        ];

        return [
            'callbackData' => $result,
            'gateway'      => 'upi_yesbank'
        ];
    }
}
