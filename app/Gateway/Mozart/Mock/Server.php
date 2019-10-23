<?php

namespace RZP\Gateway\Mozart\Mock;

use Str;
use RZP\App;
use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;

class Server extends Base\Mock\Server
{
    protected $gateway;

    public function setGateway($gateway)
    {
        $this->gateway = $gateway;
    }

    public function authorize($input)
    {
        parent::authorize($input);

        $gateway = $this->gateway;

        return $this->$gateway($input);
    }

    public function reconcile($input)
    {
        $reconcileObj = new ReconcileData();

        return $this->processMockResponse($input, $reconcileObj, 'reconcile');
    }

    public function payInit($input)
    {
        $payInitObj = new PayInitData();

        return $this->processMockResponse($input, $payInitObj, 'pay_init');
    }

    public function payVerify($input)
    {
        $payVerifyObj = new PayVerifyData();

        return $this->processMockResponse($input, $payVerifyObj, 'pay_verify');
    }

    public function verify($input)
    {
        $verifyObj = new VerifyData();

        return $this->processMockResponse($input, $verifyObj, 'verify');
    }

    public function capture($input)
    {
        $captureObj = new CaptureData();

        return $this->processMockResponse($input, $captureObj, 'capture');
    }

    public function refund($input)
    {
        $refundObj = new RefundData();

        return $this->processMockResponse($input, $refundObj, 'refund');
    }

    public function verifyRefund($input)
    {
        $verifyRefundObj = new VerifyRefundData();

        return $this->processMockResponse($input, $verifyRefundObj, 'verify_refund');
    }

    public function intent($input)
    {
        $intentObj = new IntentData();

        return $this->processMockResponse($input, $intentObj, 'intent');
    }

    protected function makeResponseJson($body)
    {
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function processMockResponse($input, $actionClass, $action)
    {
        $input = json_decode($input, true);

        $entities = $input['entities'];

        $gateway = $this->getGateway($entities);

        $response = $actionClass->$gateway($entities);

        $this->content($response, $action);

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function getCallbackRequest(array $payment)
    {
        $url = '/callback/' . $payment['gateway'];
        $method = 'post';
        $server = [
            'CONTENT_TYPE' => 'application/json'
        ];

        switch ($payment['gateway'])
        {
            case 'upi_citi':
                $content = [
                    'TxnRefNo'             => '700000135-100000001120',
                    'OrderNo'              => $payment['id'],
                    'NPCITxnId'            => 'CITI7FA2285C01AC932AE05392BCBBA925A',
                    'TimeStamp'            => '2019-01-17T15:42:44+05:30',
                    'TranAuthDate'         => '2019-01-17T00:00:00',
                    'StatusCode'           => '1',
                    'StatusDesc'           => 'NPCI Success - Pending Posting',
                    'RespCode'             => '00',
                    'SettlementAmount'     => amount_format_IN($payment['amount']),
                    'SettlementCurrency'   => 'INR'
                ];

                switch ($payment['description'])
                {
                    case 'toBeRejected':
                        $content['StatusCode'] = '3';
                        $content['RespCode']   = 'ZA';
                        break;

                    case 'callbackAmountMismatch':
                        $content['SettlementAmount'] = amount_format_IN($payment['amount'] - 1);
                }

                $raw = json_encode(['PushNotificationToSSG' => $content]);
        }

        return [
            'url'       => $url,
            'method'    => $method,
            'raw'       => $raw,
            'server'    => $server,
        ];;
    }

    public function getAsyncCallbackContent(array $payment)
    {
        $response = [
            'code'      => '0',
            'errorCode' => '000',
            'messageText' => 'success',
            'rrn' => '987654321',
            'txnStatus' => 'SUCCESS',
            'amount' => $payment['amount'] / 100,
            'hdnOrderID' => ltrim($payment['id'], 'pay_'),
        ];

        $str = implode('#', $response);

        $secret = $this->getUpiAirtelSecret();

        $str .= '#'.$secret;

        $hash = hash(HashAlgo::SHA512, $str);

        $response['hash'] = $hash;

        return [json_encode($response)];
    }

    public function getFailedAsyncCallbackContent(array $payment)
    {
        $response = [
            'code'      => 0,
            'errorCode' => 000,
            'messageText' => 'success',
            'rrn' => '987654321',
            'txnStatus' => 'FAILED',
            'amount' => $payment['amount'] / 100,
            'hdnOrderID' => ltrim($payment['id'], 'pay_'),
        ];

        $str = implode('#', $response);

        $secret = $this->getUpiAirtelSecret();

        $str .= '#'.$secret;

        $hash = hash(HashAlgo::SHA512, $str);

        $response['hash'] = $hash;

        return [json_encode($response)];
    }

    protected function wallet_phonepe($input)
    {
        $content = $input;

        $content['checksum'] = 'randomHash';

        $paymentId = $content['paymentId'];

        $this->content($content, 'authorize');

        $publicId = $this->getSignedPaymentId($paymentId);

        $url = $this->route->getPublicCallbackUrlWithHash($publicId);
        $request = [
            'url'          => $url,
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }

    protected function wallet_paypal($input)
    {
        $content = $input;
        $content = [
            'token'     => 'PayPal_Token',
            'PayId'     => '8DS61651XA862144J',
            'status'    => 'callback_successful',
        ];

        $this->content($content, 'authorize');

        $request = [
            'url'          => $input['callbackUrl'],
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }

    protected function netbanking_sib($input)
    {
        // this encrypted value is never used as the pay_verify response from mozart is mocked
        $content = [
              'ENC_STR' => 'random_encrypted_string'
        ];

        $request = [
            'url'          => $input['callbackUrl'],
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }

    protected function netbanking_ubi($input)
    {
        // this encrypted value is never used as the pay_verify response from mozart is mocked
        $content = [
            'ENC_STR' => 'random_encrypted_string'
        ];

        $request = [
            'url'          => $input['callbackUrl'],
            'content'      => $content,
            'method'       => 'get',
        ];

        return $this->makePostResponse($request);
    }

    protected function netbanking_cbi($input)
    {
        $request = [
            'url'          => $input['callbackUrl'] . '?encdata=encrypted_data_here',
            'content'      => [],
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }

    protected function netbanking_yesb($input)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'gateway_payment_callback_yesb_post',
            [
                'paymentId' => $input['paymentId'],
                'amount'    => number_format($input['amount'] / 100, 2, '.', '')
            ]);

        $request = [
            'url'     => $url,
            'content' => ['encdata' => 'dummy_response_data'],
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    protected function netbanking_cub($input)
    {

        // this encrypted value is never used as the pay_verify response from mozart is mocked
        $content = [
            'ENC_STR' => 'random_encrypted_string'
        ];

        $request = [
            'url'          => $input['callbackUrl'],
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }

    protected function netbanking_ibk($input)
    {

        // this encrypted value is never used as the pay_verify response from mozart is mocked
        $content = [
            'ENC_STR' => 'random_encrypted_string'
        ];

        $request = [
            'url'          => $input['callbackUrl'],
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }

    protected function netbanking_idbi($input)
    {

        // this encrypted value is never used as the pay_verify response from mozart is mocked
        $content = [
            'random_encrypted_string' => ''
        ];

        $request = [
            'url'          => $input['callbackUrl'],
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function google_pay($input)
    {
        $response = [
            'code'      => 0,
            'errorCode' => 000,
            'messageText' => 'success',
            'rrn' => '987654321',
        ];
    }

    public function createTerminal($body)
    {
        $mockCase = $this->app['config']->get('atos_terminal_onboarding_creation.case');

        switch ($mockCase)
        {
            case "1":
                $responseBody = [
                    'data' => [
                        'description'   => "Success",
                        'res_code'        => "00",
                        '_raw'          => "{\"TID\":\"9137251R\",\"REQRRN\":null,\"RESDTTM\":\"23082019134719\",\"RESCODE\":\"00\",\"RESDESC\":\"Success\",\"REQTYPE\":\"N\",\"BANKCODE\":\"00031\",\"MID\":\"999122000040351\"}"
                    ],
                    'error'             => [],
                    'external_trace_id' => "",
                    'mozart_id'         => "blfq216r1gunssphbs01",
                    'next'              => null,
                    'success'           => true
                ];
                break;

            case "2":
                $responseBody = [
                    'data'      =>  [],
                    'error'     =>  [
                        'description'               => "INPUT_VALIDATION_FAILED {\"component\":\"Validate\",\"data\":\"{\\\"entities.bank_details.account_number\\\":[\\\"The entities.bank_details.account_number field is required\\\"],\\\"entities.merchant_details.business_registered_address\\\":[\\\"The entities.merchant_details.business_registered_address field is required\\\"],\\\"entities.merchant_details.business_registered_city\\\":[\\\"The entities.merchant_details.business_registered_city field is required\\\"],\\\"entities.merchant_details.business_registered_pin\\\":[\\\"The entities.merchant_details.business_registered_pin field is required\\\"],\\\"entities.merchant_details.business_registered_state\\\":[\\\"The entities.merchant_details.business_registered_state field is required\\\"],\\\"entities.merchant_details.contact_mobile\\\":[\\\"The entities.merchant_details.contact_mobile field is required\\\"],\\\"entities.merchant_details.contact_name\\\":[\\\"The entities.merchant_details.contact_name field is required\\\"]}\",\"message\":\"Error performing validation\",\"step_name\":\"Validator\"}",
                        'gateway_error_code'        =>  "",
                        'gateway_error_description' =>  "",
                        'gateway_status_code'       =>  0,
                        'internal_error_code'       =>  "BAD_REQUEST_VALIDATION_FAILURE",
                    ],
                    'success'   => false,   
                 ];
                 break;

            case "3":
                $responseBody = [
                    'data'      =>  [
                        '_raw'          =>  '{\"MID\":\"999122000040352\",\"TID\":\"9137251R\",\"REQRRN\":\"1000000131\",\"RESDTTM\":\"03092019115555\",\"RESCODE\":\"05\",\"RESDESC\":\"Invalid Terminal ID\",\"REQTYPE\":\"E\",\"BANKCODE\":\"00031\"}',
                        'description'   =>  'Invalid Terminal ID',
                        'retry'         =>  'false',
                    ],
                    'error'     =>  [
                        'description'               =>  "",
                        'gateway_error_code'        =>  '05',
                        'gateway_error_description' =>  '(No error description was mapped for this error code)',
                        'gateway_status_code'       =>  200,
                        'internal_error_code'       =>  'GATEWAY_ERROR_UNKNOWN_ERROR',
                    ],
                    'success'   => false,   
                ];
                break;
            
            case "4":
                $responseBody = [
                    'data' => [],
                    'error' =>[
                        'description' =>  "Invalid route",
                        'gateway_error_code' =>  "",
                        'gateway_error_description' => "",
                        'gateway_status_code' =>  0,
                        'internal_error_code' =>  "SERVER_ERROR_LOGICAL_ERROR"
                    ],
                    'external_trace_id' => "a21c02be54abfc98f5421f001ba4ad4d",
                    'mozart_id' => "bm8eb47cfeaesrbbagsg",
                    'next' => [],
                    'success' => false
                ];
                break;

            case "5":
                $responseBody = [
                    "data" => [
                    "_raw" => "{\"BANKCODE\":\"00031\",\"MID\":\"999000000000031\",\"TID\":\"12380040\",\"REQRRN\":\"DLze1ggH2WSxHi0\",\"RESDTTM\":\"24092019152250\",\"RESCODE\":\"05\",\"RESDESC\":\"Duplicate MVISAPAN\",\"REQTYPE\":\"N\"}",
                    "description" => "Duplicate MVISAPAN",
                    "res_code" => "05",
                    "retry" => "false",
                    "status" => "terminal_creation_failed"
                    ],
                    "error" =>  [
                    "description" => "GATEWAY_ERROR",
                    "gateway_error_code" => "05",
                    "gateway_error_description" => "GATEWAY_ERROR",
                    "gateway_status_code" => 200,
                    "internal_error_code" => "GATEWAY_ERROR_INVALID_DATA"
                    ],
                    "next" => [],
                    "success" => false
                ];
                break;
        }

        $response = \Response::make($responseBody);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    public function verifyTerminal($body)
    {
        $mockCase = $this->app['config']->get('atos_terminal_onboarding_verification.case');

        switch ($mockCase)
        {
            case "1":
                $responseBody = [
                    'data' => [
                        'description'   => 'Success',
                        'res_code'        => '00',
                        'status'        => 'callback_successful',
                        '_raw'          => '{\'TID\':\'9137251R\',\'REQRRN\':null,\'RESDTTM\':\'23082019134719\',\'RESCODE\':\'00\',\'RESDESC\':\'Success\',\'REQTYPE\':\'N\',\'BANKCODE\':\'00031\',\'MID\':\'999122000040351\'}'
                    ],
                    'error'             => [],
                    'external_trace_id' => '',
                    'mozart_id'         => 'blfq216r1gunssphbs01',
                    'next'              => null,
                    'success'           => true,
                ];
                break;
            case "2":
                $responseBody = [
                    'data' => [
                        'description'   => 'Failed',
                        'res_code'        => '00',
                        'status'        => 'callback_failed',
                        '_raw'          => '{\'TID\':\'9137251R\',\'REQRRN\':null,\'RESDTTM\':\'23082019134719\',\'RESCODE\':\'00\',\'RESDESC\':\'Success\',\'REQTYPE\':\'N\',\'BANKCODE\':\'00031\',\'MID\':\'999122000040351\'}'
                    ],
                    'error'             => [],
                    'external_trace_id' => '',
                    'mozart_id'         => 'blfq216r1gunssphbs01',
                    'next'              => null,
                    'success'           => true,
                ];
                break;
        }

        $response = \Response::make($responseBody);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function getUpiAirtelSecret()
    {
        return $this->app['config']->get('gateway.mozart.upi_airtel.test_hash_secret');
    }

    protected function getGateway($entities)
    {
        if ((isset($entities['gateway']) === true) and ($entities['gateway'] === 'google_pay'))
        {
            return $entities['gateway'];
        }

        return $entities['payment']['gateway'];
    }

    public function getAsyncCallbackContentWalletPhonepe(array $payment)
    {
        $this->action = 'callback';

        $content = $this->callbackResponseContent($payment);

        $this->content($content, 'callback');

        $response = $this->makeIntentResponsePhonepe($content);

        return [
            'response' => $response
        ];
    }

    protected function callbackResponseContent(array $payment)
    {
        $response = [
            'code' => 'PAYMENT_SUCCESS',
            'success' => true,
            'data' => [
                'amount' => $payment['amount'],
                'merchantId' => 'abc',
                'transactionId' => ltrim($payment['id'], 'pay_'),
                'providerReferenceId' => 'PHONEPE1'
            ]
        ];

        return $response;
    }

    protected function makeIntentResponsePhonepe($content)
    {
        $data = base64_encode(json_encode($content));

        return $data;
    }

}
