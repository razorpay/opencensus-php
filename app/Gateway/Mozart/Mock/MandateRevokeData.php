<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class MandateRevokeData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function upi_icici($entities)
    {
        $response = [
            'data' =>
                [
                    'referenceNumber' => 'IFPO039F3940343',
                    'pgMerchantId' => 'HDFC000006002278',
                    'ref_url' => 'https://mer.invoice.com/upi/3ddsfsdg',
                    'amount' => 200,
                    'custRefNo' => '920515212270',
                    'mandateStatus' => 'REVOKED',
                    'reqStatus' => 'S',
                    'message' => 'Mandate Request Initiated to NPCI',
                    'payerVPA' => 'testvpa@yesb',
                    'credAcc' => '01601200021634',
                    'endDate' => '26 Jul 2019',
                    'txnId' => 'HDF542de25ds56ad9896ac96cef89475623',
                    'creditIFSC' => 'HDFC0000160',
                    'mcc' => '4121',
                    'startDate' => '24 Jul 2019',
                    'isVerified' => false,
                    'errorCode' => 'MD200',
                    '_raw' => '',
                    ''
                ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function billdesk_sihub($entities)
    {
        $response = [
            'data' =>
                [
                    'status' => 'deleted',
                    '_raw' => '',
                    ''
                ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        return $response;
    }
}

