<?php

namespace RZP\Gateway\Mozart\Mock;

use Str;
use RZP\App;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Mozart\Action;
use RZP\Gateway\Mozart\UpiJuspay;


class Server extends Base\Mock\Server
{
    use UpiRecurringCallbacks;

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

    public function createVirtualAccount($input)
    {
        $createVirtualAccountObj = new CreateVirtualAccount();

        return $this->processMockResponse($input, $createVirtualAccountObj, Action::CREATE_VIRTUAL_ACCOUNT);
    }

    public function payInit($input)
    {
        $payInitObj = new PayInitData();

        return $this->processMockResponse($input, $payInitObj, 'pay_init');
    }

    public function capture($input)
    {
        $captureObj = new CaptureData();

        return $this->processMockResponse($input, $captureObj, 'capture');
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

    public function debit($input)
    {
        $captureObj = new DebitData();

        return $this->processMockResponse($input, $captureObj, 'debit');
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

    public function checkAccount($input)
    {
        $elligiblityObj = new CheckAccountData();

        return $this->processMockResponse($input, $elligiblityObj, 'check_account');
    }


    public function authInit($input)
    {
        $mandateCreateObj = new AuthInitData();

        return $this->processMockResponse($input, $mandateCreateObj, Action::AUTH_INIT);
    }

    public function authVerify($input)
    {
        $authVerifyObj = new AuthVerifyData();

        return $this->processMockResponse($input, $authVerifyObj, Action::AUTH_VERIFY);
    }

    public function authenticateInit($input)
    {
        $authInitObj = new AuthenticateInitData();

        return $this->processMockResponse($input, $authInitObj, Action::AUTHENTICATE_INIT);
    }

    public function authenticateVerify($input)
    {
        $authVerifyObj = new AuthenticateVerifyData();

        return $this->processMockResponse($input, $authVerifyObj, Action::AUTHENTICATE_VERIFY);
    }

    public function checkBalance($input)
    {
        $checkBalanceObj = new CheckBalanceData();

        return $this->processMockResponse($input, $checkBalanceObj, Action::CHECK_BALANCE);
    }

    public function mandateRevoke($input)
    {
        $mandateRevokeObj = new MandateRevokeData();

        return $this->processMockResponse($input, $mandateRevokeObj, Action::MANDATE_REVOKE);
    }

    public function callbackDecryption($input)
    {
        $callbackDecryptionObj = new CallbackDecryption();

        return $this->processMockResponse($input, $callbackDecryptionObj, Action::CALLBACK_DECRYPTION);
    }

    public function notify($input)
    {
        $notifyObject = new NotifyData();

        return $this->processMockResponse($input, $notifyObject, Action::NOTIFY);
    }

    protected function makeResponseJson($body)
    {
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    public function preProcess($input)
    {
        $preProcessObj = new PreProcess();

        return $this->processMockResponse($input, $preProcessObj, Action::PRE_PROCESS);
    }

    protected function processMockResponse($input, $actionClass, $action, $gateway = null)
    {
        $input = json_decode($input, true);

        if ((isset($input['entities']) === false) and
            (($action === Action::PRE_PROCESS) or
             ($action === Action::CREATE_VIRTUAL_ACCOUNT) or
                ($action === Action::VERIFY)))
        {
            $input['entities'] = $input;
        }

        $entities = $input['entities'];

        $gateway = $gateway === null ? $this->getGateway($entities) : $gateway;

        $this->request($entities, $action);

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
                break;

            case 'upi_juspay':
                $content = $this->makeCallbackForUpiJuspay($payment);

                $server['HTTP_X-Merchant-Payload-Signature']  = 'signature';

                $raw = json_encode($content);
                break;
        }

        return [
            'url'       => $url,
            'method'    => $method,
            'raw'       => $raw,
            'server'    => $server,
        ];
    }

    public function getAsyncCallbackContent(array $payment,array $terminal = [])
    {
        $gateway = $payment['gateway'] ?? '';

        if ($gateway !== 'upi_airtel')
        {
            return [];
        }

        $description = $payment['description'] ?? '';

        switch ($description)
        {
            case 'payment_failed':
                $response = [
                    'amount' => $payment['amount'] / 100,
                    'mid' => 'MER0000000548542',
                    'rrn' => '987654321',
                    'txnStatus' => 'FAILED',
                    'hdnOrderID' => ltrim($payment['id'], 'pay_'),
                    'messageText' => 'failed',
                    'code'      => '1',
                    'errorCode' => 'U30',
                    'payerVPA'	=> $payment['vpa'],
                    'payeeVPA'  => $terminal['gateway_merchant_id2'] ?? 'razorpay@mairtel',
                    'txnRefNo'	=> 'FT2129114821982611',
                ];

                break;
            default:
                $response = [
                    'amount' => $payment['amount'] / 100,
                    'mid' => 'MER0000000548542',
                    'rrn' => '987654321',
                    'txnStatus' => 'SUCCESS',
                    'hdnOrderID' => ltrim($payment['id'], 'pay_'),
                    'messageText' => 'success',
                    'code'      => '0',
                    'errorCode' => '000',
                    'payerVPA'	=> $payment['vpa'],
                    'payeeVPA'  => $terminal['gateway_merchant_id2'] ?? 'razorpay@mairtel',
                    'txnRefNo'	=> 'FT2129114821982611',
                ];
        }

        $str = implode('#', $response);

        $secret = $this->getUpiAirtelSecret();

        $str .= '#'.$secret;

        $hash = hash(HashAlgo::SHA512, $str);

        $response['hash'] = $hash;

        return json_encode($response);
    }

    public function getUnexpectedAsyncCallbackContentForAirtel()
    {
        $response = [
            'code'        => '0',
            'errorCode'   => '000',
            'messageText' => 'success',
            'rrn'         => '987654321',
            'txnStatus'   => 'SUCCESS',
            'amount'      => 20,
            'hdnOrderID'  => str_random(12),
            'payerVPA'    => 'unexpected@airtel',
            'payeeVPA'    => 'razorpay@mairtel',
            'txnRefNo'	=> 'FT2129114821982611',
        ];

        $str = implode('#', $response);

        $secret = $this->getUpiAirtelSecret();

        $str .= '#'.$secret;

        $hash = hash(HashAlgo::SHA512, $str);

        $response['hash'] = $hash;

        return json_encode($response);
    }

    public function getAsyncRefundCallbackContentForAirtel(array $override = [])
    {
        $response = [
            'code'          => '0',
            'errorCode'     => '0',
            'messageText'   => 'SUCCESS',
            'rrn'           => '200517825983',
            'txnStatus'     => 'SUCCESS',
            'amount'        => 200,
            'hdnOrderID'    => str_random(14),
            'payerVPA'      => 'razorpay@mairtel',
            'payeeVPA'      => 'customer@airtel',
            'txnRefNo'	    => 'FT2129114821982611',
            'mid'           => 'MER0000000548542',
        ];

        $response = array_merge($response, $override);

        $str = implode('#', $response);

        $secret = $this->getUpiAirtelSecret();

        $str .= '#'.$secret;

        $hash = hash(HashAlgo::SHA512, $str);

        $response['hash'] = $hash;

        return json_encode($response);
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

        return json_encode($response);
    }

    protected function cred($input)
    {
        return;

    }

    protected function payu($input)
    {
        // This encResp value is never used as the pay_verify response from mozart is mocked.
        $content = [
            'encResp' => 'random_encrypted_string'
        ];

        $this->content($content, 'authorize');

        $request = [
            'url'          => $input['callbackUrl'],
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }

    protected function ccavenue($input)
    {
        // This encResp value is never used as the pay_verify response from mozart is mocked.
        $content = [
            'encResp' => 'random_encrypted_string'
        ];

        $this->content($content, 'authorize');

        $request = [
            'url'          => $input['callbackUrl'],
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
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

    protected function wallet_phonepeswitch($input)
    {
        $content = $input;

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

    protected function getsimpl($input)
    {
        $content = $input;

        $content = [
            'available_credit_in_paise'     => '10000000',
            'merchant_payload'              => $content['paymentId'],
            'success'                       => true,
            'token'                         => '83hd48h387d83n78fn8rf83r7if83r'
        ];

        $this->content($content, 'authorize');

        $request = [
            'method'  => 'POST',
            'url'     => '/v1/callback/getsimpl',
            'content' => $content
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

    protected function netbanking_scb($input)
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

    protected function netbanking_kvb($input)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'gateway_payment_static_callback_post',
            [
                'method'    => 'netbanking',
                'gateway'   => 'netbanking_kvb',
                'mode'      => 'test',
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

    protected function netbanking_jsb($input)
    {
        $content = [
            'qs' => 'random_encrypted_string'
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

    public function merchantOnboard($input)
    {
        $mockCase = $this->app['config']->get('hitachi_merchant_onboarding_creation.case');

        switch ($mockCase)
        {
            case "1":
            default:
                $responseBody =  [
                        'data' => [
                            "_raw" => "{\"TID\":\"38R68287\",\"Response Code\":\"00\",\"Response description\":\"Success\",\"MID\":\"38RR00000068287\",\"S_no\":1585390152}",
                            'identifiers'   => [
                                "gateway_merchant_id"=> "38RR00000010001",
                                "gateway_terminal_id"=> "38R10001",

                            ]
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
                    'data'      =>  [
                        '_raw'          =>  '{\"Response description\":\"Failure\",\"Response Code\":\"05\"}'
                    ],
                    'error'     =>  [
                        'description'               =>  'Failed at gateway',
                        'gateway_error_code'        =>  '05',
                        'gateway_error_description' =>  'Failed at gateway',
                        'gateway_status_code'       =>  200,
                        'internal_error_code'       =>  'GATEWAY_ERROR_ONBOARDING_FAILED',
                    ],
                    'success'   => false,
                ];
                break;
        }


        $response = \Response::make($responseBody);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    public function createTerminal($body)
    {
        $reqjson = json_decode($body, true);

        $reqType = $reqjson['entities']['request_type'];

        $mockCase = $this->app['config']->get('worldline_terminal_onboarding_creation.case');

        switch ($mockCase)
        {
            case "1":
            default:
                // asserts that req_type is 'N' for first onboarding request
                assert($reqType === 'N');

                $responseBody = [
                    'data' => [
                        'description'   => "SUCCESS",
                        'res_code'      => "00",
                        'retry'         =>  "false",
                        'status'        => "terminal_creation_successful",
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
            case "6":
                $responseBody = [
                    "data" => [
                    "_raw" => "{\"REQTYPE\":\"N\",\"BANKCODE\":\"00031\",\"MID\":\"999000000000069\",\"TID\":\"12380309\",\"REQRRN\":\"DrZX1T3gRojN470\",\"RESDTTM\":\"13122019104436\",\"RESCODE\":\"05\",\"RESDESC\":\"Duplicate Merchant code\"}",
                    "description" => "Duplicate Merchant code",
                    "res_code" => "05",
                    "retry" => "false",
                    "status" => "terminal_creation_failed"
                    ],
                    "error" =>  [
                    "description" => "",
                    "gateway_error_code" => "05",
                    "gateway_error_description" => "(No error description was mapped for this error code)",
                    "gateway_status_code" => 200,
                    "internal_error_code" => "GATEWAY_ERROR_UNKNOWN_ERROR"
                    ],
                    "next" => [],
                    "success" => false
                ];
                break;
                case "7":
                    // if request reached here, it means checkDbConstraints did not through exception, throwing exception here to fail test
                    // this is necessary to confirm that error is raised while creating terminal in checkDbConstraints and not by creating actual terminal after receiving gateway response
                    throw new Exception\LogicException('Request should not have reached here');
                break;
                case "8":
                    // asserts that req_type is 'A' for additional tid flow instead of 'N'
                    assert($reqType === 'A');

                    $responseBody = [
                        'data' => [
                            'description'   => "SUCCESS",
                            'res_code'      => "00",
                            'retry'         =>  "false",
                            'status'        => "terminal_creation_successful",
                            '_raw'          => "{\"TID\":\"9137251R\",\"REQRRN\":null,\"RESDTTM\":\"23082019134719\",\"RESCODE\":\"00\",\"RESDESC\":\"Success\",\"REQTYPE\":\"N\",\"BANKCODE\":\"00031\",\"MID\":\"999122000040351\"}"
                        ],
                        'error'             => [],
                        'external_trace_id' => "",
                        'mozart_id'         => "blfq216r1gunssphbs01",
                        'next'              => null,
                        'success'           => true
                    ];
                break;
                case "9":
                    $ex = new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_FATAL_ERROR);
                    throw $ex;
                break;
        }

        $response = \Response::make($responseBody);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    public function decrypt($input)
    {
        $data = json_decode($input, true);

        $signature = $data['signature'];

        $decryptedMessage = [
            'signingKeyExpiration' => '1986519021673',
            'messageExpiration' => '1977862000000',
            'messageId' => 'some-message-id',
            'gatewayMerchantId' => '10000000000000',
            'paymentMethod' => 'CARD',
            'paymentMethodDetails' => [
                'expirationMonth' => 10,
                '3dsEciIndicator' => 'eci indicator',
                '3dsCryptogram' => 'AAAAAA...',
                'authMethod' => 'CRYPTOGRAM_3DS',
                'pan' => '4444333322221111',
                'expirationYear' => 2120,
            ]
        ];

        $decryptedMessage2 = [
            'signingKeyExpiration' => '1986519021673',
            'messageExpiration' => '1977862000000',
            'messageId' => 'some-message-id',
            'gatewayMerchantId' => '10000000000000',
            'paymentMethod' => 'CARD',
            'paymentMethodDetails' => [
                'expirationMonth' => 10,
                '3dsEciIndicator' => 'eci indicator',
                '3dsCryptogram' => 'AAAAAA...',
                'authMethod' => 'CRYPTOGRAM_3DS',
                'pan' => '4532948024710971',
                'expirationYear' => 2120,
            ]
        ];

        $responseBody = [];

        switch ($signature)
        {
            Case "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY":
                $responseBody = [
                    'data' => [
                        '_raw'             => '',
                        'decryptedMessage' => $decryptedMessage,
                    ],
                    'error'             => [],
                    'external_trace_id' => '',
                    'mozart_id'         => 'blfq216r1gunssphbs01',
                    'next'              => null,
                    'success'           => true,
                ];
                break;
            Case "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==2":
                $responseBody = [
                    'data' => [
                        '_raw'             => '',
                        'decryptedMessage' => $decryptedMessage2,
                    ],
                    'error'             => [],
                    'external_trace_id' => '',
                    'mozart_id'         => 'blfq216r1gunssphbs01',
                    'next'              => null,
                    'success'           => true,
                ];
                break;
            Case "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==3":
                $decryptedMessage['messageExpiration'] = '1117862000000';
                $responseBody = [
                    'data' => [
                        '_raw'             => '',
                        'decryptedMessage' => $decryptedMessage,
                    ],
                    'error'             => [],
                    'external_trace_id' => '',
                    'mozart_id'         => 'blfq216r1gunssphbs01',
                    'next'              => null,
                    'success'           => true,
                ];
                break;
            Case "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==5":
                throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_INTERNAL_SERVER_ERROR);
            Case "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==6":
                $decryptedMessage['paymentMethodDetails']['3dsCryptogram'] = '';
                $responseBody = [
                    'data' => [
                        '_raw'             => '',
                        'decryptedMessage' => $decryptedMessage,
                    ],
                    'error'             => [],
                    'external_trace_id' => '',
                    'mozart_id'         => 'blfq216r1gunssphbs01',
                    'next'              => null,
                    'success'           => true,
                ];
                break;
            Case "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==7":
                $decryptedMessage['paymentMethodDetails']['pan'] = '123';
                $responseBody = [
                    'data' => [
                        '_raw'             => '',
                        'decryptedMessage' => $decryptedMessage,
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

    public function disableTerminal($body)
    {
        $mockCase = $this->app['config']->get('worldline_terminal_onboarding_disable.case');

        switch ($mockCase)
        {
            case "1":
            default:
                $responseBody = [
                    'data' => [
                    'description'   => 'Success',
                    'res_code'      => '00',
                    'status'        => 'terminal_deactivation_successful'
                ],
                'error'             => [],
                'external_trace_id' => '',
                'mozart_id'         => 'ckfw236r1gensdphqs51',
                'next'              => null,
                'success'           => true,
            ];
            break;
            case "2":
                $responseBody = [
                    'data' => [
                    'description'   => 'Failed',
                    'res_code'      => '00',
                    'status'        => 'terminal_deactivation_failed'
                ],
                'error'             => [],
                'external_trace_id' => '',
                'mozart_id'         => 'ckfw236r1gensdphqs51',
                'next'              => null,
                'success'           => false,
            ];
            break;
            case "3":
                $responseBody = [
                    'data' => [
                    'raw'           => "{\"BANKCODE\":\"00031\",\"MID\":\"999000000000072\",\"TID\":\"12380330\",\"REQRRN\":\"DyQMfdzfyAybZ31577778271\",\"RESDTTM\":\"31122019011432\",\"RESCODE\":\"05\",\"RESDESC\":\"Merchant is already in deactive state\",\"REQTYPE\":\"D\"}",
                    'description'   => "Merchant is already in deactive state",
                    'res_code'      => '05',
                    'retry'         => "false",
                    'status'        => 'terminal_deactivation_failed'
                ],
                'error'             => [
                    'description'               =>  "GATEWAY_ERROE",
                    'gateway_error_code'        =>  "05",
                    'gateway_error_description' =>  "GATEWAY_ERROR",
                    'gateway_status_code'       =>  200,
                    'internal_error_code'       =>  "GATEWAY_ERROR_INVALID_DATA"
                ],
                'external_trace_id' => "1af7c9e0b229bbc862afd7e1ccd19cdf",
                'mozart_id'         => 'bo5foo6ef6s0qcbubbs0',
                'next'              => null,
                'success'           => false,
            ];
            break;
        }


        $response = \Response::make($responseBody);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    public function enableTerminal($body)
    {
        $mockCase = $this->app['config']->get('worldline_terminal_onboarding_enable.case');

        switch ($mockCase)
        {
            case "1":
            default:
                $responseBody = [
                    'data' => [
                    'description'   => 'Success',
                    'res_code'      => '00',
                    'status'        => 'terminal_reactivation_successful'
                ],
                'error'             => [],
                'external_trace_id' => '',
                'mozart_id'         => 'wefw236r1wdf2hqs51',
                'next'              => null,
                'success'           => true,
            ];
            break;
            case "2":
                $responseBody = [
                    'data' => [
                    'description'   => 'Failed',
                    'res_code'      => '00',
                    'status'        => 'terminal_reactivation_failed'
                ],
                'error'             => [],
                'external_trace_id' => '',
                'mozart_id'         => 'wefw236r1wdf2hqs51',
                'next'              => null,
                'success'           => false,
            ];
            break;
            case "3":
                $responseBody = [
                    'data' => [
                    'raw'           => "{\"BANKCODE\":\"00031\",\"MID\":\"999000000000072\",\"TID\":\"12380330\",\"REQRRN\":\"DyQMfdzfyAybZ31577778271\",\"RESDTTM\":\"31122019011432\",\"RESCODE\":\"05\",\"RESDESC\":\"Merchant is already in deactive state\",\"REQTYPE\":\"D\"}",
                    'description'   => "Merchant is already in active state",
                    'res_code'      => '05',
                    'retry'         => "false",
                    'status'        => 'terminal_reactivation_failed'
                ],
                'error'             => [
                    'description'               =>  "GATEWAY_ERROE",
                    'gateway_error_code'        =>  "05",
                    'gateway_error_description' =>  "GATEWAY_ERROR",
                    'gateway_status_code'       =>  200,
                    'internal_error_code'       =>  "GATEWAY_ERROR_INVALID_DATA"
                ],
                'external_trace_id' => "1af7c9e0b229bbc862afd7e1ccd19cdf",
                'mozart_id'         => 'bo5foo6ef6s0qcbubbs0',
                'next'              => null,
                'success'           => false,
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
        if ((isset($entities['gateway']) === true) and
            (($entities['gateway'] === 'google_pay') or
             ($entities['gateway'] === 'billdesk_sihub') or
             ($entities['gateway'] === 'paysecure') or
              $entities['gateway'] === 'bt_rbl'))
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

        $response = $this->encondeResponse($content);

        return [
            'response' => $response
        ];
    }

    public function getAsyncCallbackContentCred(array $payment)
    {
        $this->action = 'callback';

        $content = $this->callbackResponseContentCred($payment);

        $this->content($content, 'callback');

        return [
            'response' => $content['response']
        ];
    }

    protected function callbackResponseContentCred($payment)
    {
        $response = [
            'response' => [
                'tracking_id'=> ltrim($payment['id'], 'pay_'),
                'reference_id'=> 'abc',
                'state'=> 'COMPLETED',
                'amount'=> [
                    'currency'=> 'INRPAISE',
                    'value'=> $payment['amount'],
                ],
            ],
            'status'=> 'OK',
            'error_code'=> '',
            'error_message'=> '',
            'error_description'=> ''
        ];

        return $response;
    }

    protected function makeCallbackForUpiJuspay(array $payment)
    {
        $content = [
            UpiJuspay\Fields::AMOUNT                    => $payment['amount'],
            UpiJuspay\Fields::CUSTOM_RESPONSE           => '{}',
            UpiJuspay\Fields::EXPIRY                    => '2016-11-25T00:10:00+05:30',
            UpiJuspay\Fields::GATEWAY_REFERENCE_ID      => '806115044725',
            UpiJuspay\Fields::GATEWAY_RESPONSE_CODE     => '00',
            UpiJuspay\Fields::GATEWAY_RESPONSE_MESSAGE  => 'Transaction is approved',
            UpiJuspay\Fields::GATEWAY_TRANSACTION_ID    => 'XYZd0c077f39c454979...',
            UpiJuspay\Fields::MERCHANT_CHANNEL_ID       => 'DEMOUATAPP',
            UpiJuspay\Fields::MERCHANT_ID               => 'DEMOUAT01',
            UpiJuspay\Fields::MERCHANT_REQUEST_ID       => $payment['id'],
            UpiJuspay\Fields::PAYEE_VPA                 => 'merchant@abc',
            UpiJuspay\Fields::PAYER_NAME                => 'Customer Name',
            UpiJuspay\Fields::PAYER_VPA                 => 'customer@xyz',
            UpiJuspay\Fields::TRANSACTION_TIMESTAMP     => '2016-11-25T00:00:00+05:30',
            UpiJuspay\Fields::TYPE                      => 'MERCHANT_CREDITED_VIA_COLLECT',
            UpiJuspay\Fields::UDF_PARAMETERS            => '{}',
        ];

        switch ($payment['description'])
        {
            case 'failedCallback':
                $content[UpiJuspay\Fields::GATEWAY_RESPONSE_CODE]    = 'U69';
                $content[UpiJuspay\Fields::GATEWAY_RESPONSE_MESSAGE] = 'Transaction is failed';
                break;

            case 'intentPayment':
                $udfParameters = [
                    'ref_id' => $payment['id'],
                ];

                $content[UpiJuspay\Fields::TYPE]                = 'MERCHANT_CREDITED_VIA_PAY';
                $content[UpiJuspay\Fields::UDF_PARAMETERS]      = json_encode($udfParameters);
                unset($content[UpiJuspay\Fields::EXPIRY]);
                break;
            case 'intentWithRefIdAbsent':
                $content[UpiJuspay\Fields::TYPE]                = 'MERCHANT_CREDITED_VIA_PAY';
                unset($content[UpiJuspay\Fields::EXPIRY]);
                break;
        }

        return $content;
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

    protected function encondeResponse($content)
    {
        $data = base64_encode(json_encode($content));

        return $data;
    }

    protected function encryptForMandate($plaintext, $iv)
    {
        $key = $this->getEncryptionKey();

        $cipher = $this->getCipherInstanceForMandate($key);

        $cipher->setIV(hex2bin($iv));

        $cipherText = $cipher->encrypt($plaintext);

        return strtoupper(bin2hex($cipherText));
    }

    protected function getCipherInstanceForMandate($key)
    {
        $cipher = new AES(AES::MODE_CBC);

        $cipher->setKey($key);

        $cipher->enablePadding();

        return $cipher;
    }

    /**
     * Encrypting recurring callback for ICICI key
     */
    protected function encryptICICIKey($plaintext)
    {
        $rsa = $this->getRSAInstance();

        return $rsa->encrypt($plaintext);
    }

    protected function getRSAInstance()
    {
        $rsa = new RSA();

        $rsa->loadKey($this->getPublicKey());

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa;
    }

    /**
     * Public key of the client that is connecting
     * to us, in this case, the Mock Gateway
     */
    protected function getPublicKey()
    {
        return file_get_contents(__DIR__ . '/keys/mockclient.pub');
    }

    protected function encrypt($plaintext)
    {
        $key = $this->getEncryptionKey();

        $ciphertext = $this->getCipherInstance($key)
            ->encrypt($plaintext);

        return strtoupper(bin2hex($ciphertext));
    }

    protected function getEncryptionKey()
    {
        $key = config('gateway.upi_mindgate.gateway_encryption_key');

        return hex2bin($key);
    }

    protected function getCipherInstance($key)
    {
        $cipher = new AES(AES::MODE_ECB);

        $cipher->setKey($key);

        return $cipher;
    }
}
