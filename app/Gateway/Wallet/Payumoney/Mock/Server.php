<?php

namespace RZP\Gateway\Wallet\Payumoney\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Gateway\Wallet\Payumoney;
use RZP\Models\Payment;

class Server extends Base\Mock\Server
{
    protected $accessToken = '8c31d80b-83ed-4f52-8377-71301790ccaa';

    protected $authHeader = 'Bearer 8c31d80b-83ed-4f52-8377-71301790ccaa';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateActionInput($input);

        $args = func_get_args();

        $paymentId = $args[1];

        $content = array(
            'mihpayid'              => '403993715514441547',
            'mode'                  => 'test',
            'status'                => 'success',
            'unmappedstatus'        => 'captured',
            'key'                   => 'Hlbv4P',
            'txnid'                 => 'pmwallet1110628236',
            'amount'                => '1000.0',
            'addedon'               => date('Y-m-d H:i:s'),
            'productinfo'           => 'productInfo',
            'firstname'             => 'vivek',
            'lastname'              => '',
            'address1'              => '',
            'address2'              => '',
            'city'                  => '',
            'state'                 => '',
            'country'               => '',
            'zipcode'               => '',
            'email'                 => 'vivek@gmail.com',
            'phone'                 => '8199080070',
            'udf1'                  => '',
            'udf2'                  => '',
            'udf3'                  => '',
            'udf4'                  => '',
            'udf5'                  => '',
            'udf6'                  => '',
            'udf7'                  => '',
            'udf8'                  => '',
            'udf9'                  => '',
            'udf10'                 => '',
            'hash'                  => '2451471f3b2e8cf5fbebf255b0034cd433274ab1fba20bebcb34c7d36d060d82d37327eae07c7eff7141d470f00aeb142987ac5746087de01a2d692a953da0e7',
            'field1'                => '613361387628',
            'field2'                => '999999',
            'field3'                => '1152205592161331',
            'field4'                => '2270245592161330',
            'field5'                => '',
            'field6'                => '',
            'field7'                => '',
            'field8'                => '',
            'field9'                => 'SUCCESS',
            'PG_TYPE'               => 'HDFCPG',
            'encryptedPaymentId'    => $input['paymentId'],
            'bank_ref_num'          => '1152205592161331',
            'bankcode'              => 'CC',
            'error'                 => 'E000',
            'error_Message'         => 'No Error',
            'cardToken'             => '32a29ce86dff3609ba8696db46a5647542027988',
            'name_on_card'          => 'payu',
            'cardnum'               => '512345XXXXXX2346',
            'cardhash'              => 'This field is no longer supported in postback params.',
            'card_merchant_param'   => '7fc8c60f4d8013bfdbefe054690e',
            'amount_split'          => '{\'PAYU\': \'1000.0\'}',
            'payuMoneyId'           => '1110628236',
            'discount'              => '0.00',
            'net_amount_debit'      => '1000'
        );

        $this->content($content);

        $publicId = $this->getSignedPaymentId($paymentId);

        $url = $this->route->getPublicCallbackUrlWithHash($publicId);

        $url .= '?' . http_build_query($content);

        return \Redirect::to($url);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($this->mockRequest['content']);

        $response = array(
            'status'    => 0,
            'message'   => 'Transaction status',
            'result'    => array(
                array(
                    'amount'                => 500,
                    'transactionDirection'  => -1,
                    'paymentId'             => 1110561680,
                    'status'                => 'success',
                    'merchantTransactionId' => $this->mockRequest['content']['merchantTransactionId'],
                    'completedOn'           => strtotime('-30 mins')
                )
            ),
            'errorCode' => null
        );

        $this->content($response, 'verify');

        return $this->makeResponse($response);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $this->content($input, 'validateRefund');

        $response = array(
            'status'    => 0,
            'rows'      => 0,
            'message'   => 'Refund Initiated',
            'result'    => $this->getPayuRefundId(),
            'guid'      => null,
            'sessionId' => null,
            'errorCode' => null
        );

        $this->content($response, 'refund');

        return $this->makeResponse($response);
    }

    public function otpGenerate($input)
    {
        $this->validateActionInput($input, 'otpGenerate');

        $mobile = $input['mobile'];

        $response = array(
            'status' => 0,
            'message' => 'SMS sent to ' . substr_replace($mobile, 'xxxxxx', 1, -3),
            'errorCode' => null,
            'guid' => null,
            'result' => null,
            'userVaultDTO' => null
        );

        $this->content($response, 'otpGenerate');

        return $this->makeResponse($response);
    }

    public function getBalance($input)
    {
        $this->validateActionInput($input, 'getBalance');

        $response = array(
            'status' => 0,
            'message' => 'Wallet limit',
            'errorCode' => null,
            'guid' => null,
            'result' => array(
                'maxLimit' => 5000,
                'availableBalance' => 500,
                'minLimit' => 10
            ),
            'userVaultDTO' => null,
            'mode' => 'test'
        );

        $this->content($response, 'getBalance');

        return $this->makeResponse($response);
    }

    public function otpSubmit($input)
    {
        $this->validateActionInput($input, 'otpSubmit');

        $response = array(
            'status' => 0,
            'message' => 'access token',
            'errorCode' => null,
            'guid' => null,
            'result' => array(
                'headers' => array(
                    'Cache-Control' => array(
                        'no-store'
                    ),
                    'Pragma' => array(
                        'no-cache'
                    )
                ),
                'body' => array(
                    'access_token' => $this->accessToken,
                    'token_type' => 'bearer',
                    'refresh_token' => 'bfd54a5a-d10a-4e5f-ad51-1d0fd310a4d1',
                    'expires_in' => 7690192,
                    'scope' => 'read trust write'
                ),
                'statusCode' => 'OK'
            ),
            'userVaultDTO' => array(
                'availableAmount' => 22,
                'minLimit' => null,
                'maxLimit' => null
            )
        );

        if ($input['otp'] === Otp::EXPIRED)
        {
            $response = array(
                'status'        => -1,
                'message'       => Payumoney\ResponseCode::getResponseMessage('3010008'),
                'errorCode'     => '3010008',
                'guid'          => 'nnhg6878duq7ihb2dtfj6apff',
                'result'        => null,
                'userVaultDTO'  => null
            );
        }

        if ($input['otp'] === Otp::INCORRECT)
        {
            $response = array(
                'status'        => -1,
                'message'       => Payumoney\ResponseCode::getResponseMessage('3010007'),
                'errorCode'     => '3010007',
                'guid'          => 'nnhg6878duq7ihb2dtfj6apff',
                'result'        => null,
                'userVaultDTO'  => null
            );
        }

        return $this->makeResponse($response);
    }

    public function topupWallet($input)
    {
        if (isset($input['txnDetails']))
        {
            $input['txnDetails'] = json_decode($input['txnDetails'], true);
        }

        $this->validateActionInput($input, 'topupWallet');

        $this->topupRequest = $input;

        $response = array(
            'status' => 0,
            'message' => 'Payment added successfully',
            'errorCode' => null,
            'guid' => null,
            'result' => '0B663A7D4700F95709A3F5761254B406',
            'userVaultDTO' => null,
            'mode' => 'test'
        );

        return $this->makeResponse($response);
    }

    public function debitWallet($input)
    {
        $this->validateActionInput($input, 'debitWallet');

        if (!isset($this->mockRequest['headers']['Authorization']))
        {
            $response = array(
                'error'              => 'unauthorized',
                'error_description'  => 'Full authentication is required to access this resource',
            );

            return $this->makeResponse($response);
        }

        if ($this->mockRequest['headers']['Authorization'] === $this->authHeader)
        {
            $response = array(
                'status'        => 0,
                'message'       => 'Use wallet successful',
                'errorCode'     => null,
                'guid'          => null,
                'result'        => 1110562955,
                'userVaultDTO'  => null
            );

            $this->content($response, 'debit');

            return $this->makeResponse($response);
        }

        $response = array(
            'error'              => 'invalid_token',
            'error_description'  => 'Invalid access token: ' . $this->mockRequest['headers']['Authorization'],
        );

        return $this->makeResponse($response);
    }

    protected function getPayuTxnId()
    {
        return random_int(1000000000, 2567890123);
    }

    protected function getPayuRefundId()
    {
        return random_int(10000, 35000);
    }

    protected function makeResponse($json)
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
