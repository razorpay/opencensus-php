<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;

class CaptureData extends Base\Mock\Server
{
    public function upi_mindgate($entities)
    {
        if ($entities['upi']['type'] === 'otm')
        {
            if (isset($entities['gateway']['pay_init']) === false)
            {
                throw new BadRequestValidationFailureException('Must have pay_init data when capturing');
            }

            $response = [
                'data' => [
                    'referenceNumber' => 'IFPO039F3940343',
                    'pgMerchantId' => 'HDFC000006002278',
                    'ref_url' => 'https://mer.invoice.com/upi/3ddsfsdg',
                    'amount' => 200,
                    'custRefNo' => '920515212270',
                    'mandateStatus' => 'COMPLETED',
                    'reqStatus' => 'S',
                    'message' => 'Transaction success',
                    'payerVPA' => 'testvpa@yesb',
                    'payeeVPA' => 'india.uber@hdfcbank',
                    'credAcc' => '01601200021634',
                    'endDate' => '26 Jul 2019',
                    'txnId' => 'HDF542de25ds56ad9896ac96cef89475623',
                    'creditIFSC' => 'HDFC0000160',
                    'mcc' => '4121',
                    'startDate' => '24 Jul 2019',
                    'isVerified' => false,
                    'respCode' => 'MD200',
                    'umn' => 'MER5cb6b2b0640caa3d93d190095c003@hdfcbank',
                    'status' => 'mandate_execution_successful',
                    '_raw' => '',
                ],
                'error'     => null,
                'success'   => true,
                'mozart_id'         => '',
                'external_trace_id' => ''
            ];

            switch ($entities['payment']['description'])
            {
                case 'failedExecute':
                    $response['success'] = false;
                    $response['error'] = [
                        'internal_error_code'       => 'BAD_REQUEST_PAYMENT_INVALID_CAPTURE',
                        'gateway_error_code'        => 'U4',
                        'gateway_error_description' => 'Bad request , execute failed'
                    ];
            }

            return $response;
        }

        return null;
    }

}
