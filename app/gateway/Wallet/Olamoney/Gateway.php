<?php

namespace Gateway\Wallet\Olamoney;

use Trace\Trace;
use EE\Exception;
use Constants\Mode;
use Trace\TraceCode;
use EE\Error\ErrorCode;
use Gateway\Wallet\Base;
use Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const CURRENCY = 'INR';

    protected $gateway = 'wallet_olamoney';

    protected $canRunOtpFlow = false;

    // find out significance of gateway_payment_id, gateway_payment_id_2
    protected $map = array(
        'email'     => 'email',
        'contact'   => 'contact',
        'status'    => 'status_code',
        'amount'    => 'amount',
        'received' => 'received',
    );

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getBillGeneratorRequest($input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callbackRedirectFlow($input);
    }

    public function callbackRedirectFlow($input)
    {
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $this->verifySecureHash($input['gateway']);

        $this->createGatewayPaymentEntity($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'request' => $input['gateway'],
                'gateway' => self::$gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        $this->verifyPaymentCallbackResponse($input);
    }

    protected function createGatewayPaymentEntity($input)
    {
        $contentToSave = array(
            'amount'    => $input['payment']['amount'],
            'received'  => true,
            'email'     => $input['payment']['email'],
            'contact'   => $input['payment']['contact'],
            'status' => $input['payment']['status'],
        );

        parent::createGatewayPaymentEntity($contentToSave);
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];

        if ($content['status'] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content['status'],
                $content['message']);
        }
    }

    public function verifySecureHash($content)
    {
        $fieldsInOrder = array(
            'type',
            'status',
            'merchantBillId',
            'transactionId',
            'amount',
            'comments',
            'udf',
            'timestamp',
        );

        $hash = $content['hash'];

        $content = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        $generatedHash = $this->getHashOfArray($content);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getBillGeneratorRequest($input)
    {
        $request = $this->getOtpGenerateRequestArray($input);

        $query = http_build_query(array(
                    'bill'   => base64_encode($request['content']),
                    'phone'  => $input['payment']['contact'],
                ));

        $request = [
            'method'  => 'get',
            'url'     => $request['url']."?".$query,
        ];

        return $request;
    }

    public function otpGenerate($input)
    {
        $this->action($input, Action::OTP_GENERATE);

        $request = $this->getOtpGenerateRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $code = $content['status'];

        if ($code !== Status::SUCCESS)
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

            if (isset($content['errorCode']))
            {
                $errorCode = ResponseCodeMap::getApiErrorCode($content['errorCode']);
            }

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['status'],
                $content['message']);
        }
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $request = $this->getOtpSubmitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        if ($content['status'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($content['errorCode']);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['status'],
                $content['message']);
        }

        return $data;
    }

    protected function getOtpGenerateRequestArray($input)
    {
        $amount = ($input['payment']['amount'] / 100);

        $content = array(
            'command'           => Command::DEBIT,
            'accessToken'       => $this->getAccessToken($input['terminal']),
            'uniqueId'          => $input['payment']['id'],
            'comments'          => 'Razorpay_payment',
            'udf'               => $input['payment']['public_id'],
            'returnUrl'         => $input['callbackUrl'],
            'notificationUrl'   => '',
            'amount'            => number_format($amount, 2, '.', ''),
            'currency'          => self::CURRENCY,
            'couponCode'        => 'NA',
        );

        $content['hash'] = $this->getHashForOtpGenerate($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = ['Content-Type' => 'application/json'];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getOtpSubmitRequestArray($input)
    {
        $content = array(
            'command'           => Command::CAPTURE,
            'accessToken'       => $this->getAccessToken($input['terminal']),
            'uniqueId'          => $input['payment']['id'],
            'comments'          => 'Razorpay payment',
            'udf'               => $input['payment']['public_id'],
            'returnUrl'         => $input['callbackUrl'],
            'notificationUrl'   => '',
            'amount'            => ($input['payment']['amount'] / 100),
            'currency'          => self::CURRENCY,
            'couponCode'        => 'NA',
            'otp'               => $input['gateway']['otp'],
        );

        $content['hash'] = $this->getHashForOtpSubmit($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyPayment($verify)
    {
        $walletPayment = $verify->payment;
        $input = $verify->input;
        $verifyResponse = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($verifyResponse['status'] === Status::COMPLETED)
        {
            $verify->gatewaySuccess = true;

            if (($input['payment']['status'] !== 'created') and
                ($input['payment']['status'] !== 'failed'))
            {
                $verify->apiSuccess = true;
            }
            else
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = false;
            }
        }
        else if ($verifyResponse['status'] !== Status::COMPLETED)
        {
            $verify->gatewaySuccess = false;

            if (($walletPayment === NULL) and
                (($input['payment']['status'] === 'failed') or
                 ($input['payment']['status'] === 'created')))
            {
                $verify->apiSuccess = false;
            }
            else if ($walletPayment['status'] === Status::SUCCESS)
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = true;
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        if (!verify->match)
        {
            $verify->payment = $this->saveVerifyContent($walletPayment,
                                                        $input['payment'],
                                                        $verifyResponse);
        }

        return $verify->status;
    }

    protected function saveVerifyContent($walletPayment, array $payment, $verifyResponse)
    {
        $this->action = Action::AUTHORIZE;

        if ($verifyResponse['status'] === Status::COMPLETED and $walletPayment === NULL)
        {
            $walletPayment = createGatewayPaymentEntity($payment);
        }

        $this->action = Action::VERIFY;

        return $walletPayment;
    }

    protected function getWalletAttributesFromVerify($walletPayment, array $payment)
    {
        $contentToSave = array(
            'amount'    => isset($content['amount']) ? $content['amount'] : ,
            'received'  => true,
            'email'     => $input['payment']['email'],
            'contact'   => $input['payment']['contact'],
            'status' => $input['payment']['status'],
        );

        parent::createGatewayPaymentEntity($contentToSave);

        return $contentToSave;
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);
        $this->response = $response;

        $content = json_decode($response->body, true);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $content,
                'gateway' => self::$gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getVerifyRequestArray($input)
    {
        $content = array(
            'uniqueBillId' => $input['payment']['id'],
            'accessToken' => $this->getAccessToken($input['terminal']),
            'timestamp' => time(), // change this to YYYY-MM-DD HH MM SS format
            );
        $content['hash'] = $this->getHashForVerifyRequest($content);

        $query = http_build_query($content);

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function getHashForVerifyRequest($content)
    {
        $str = $content['access_token'].'|'.$content['uniqueBillId'].'||'.$content['timestamp'].'|||'.$this->getSecret();

        return $this->getHashOfString($str);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequest($input);
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);
        $content = json_decode($response->body, true);
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $this->createWalletRefundEntity($content, $input);

        if ($content['status'] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $content['status'],
                $content['message']);
        }
    }

    protected function createWalletRefundEntity($content, $input)
    {
        $refundAttributes = $this->getRefundEntityAttributesFromRefundResponse($content, $input);

        return $this->createGatewayRefundEntity($refundAttributes);
    }

    protected function getRefundEntityAttributesFromRefundResponse($content, $input)
    {
        $refundAttributes = array(
            'payment_id'            => $input['payment']['id'],
            'action'                => $this->action,
            'amount'                => $input['payment']['amount'],
            'received'              => 1,
            'wallet'                => $input['payment']['wallet'],
            'email'                 => $input['payment']['email'],
            'contact'               => $input['payment']['contact'],
            'gateway_merchant_id'   => $input['terminal']['gateway_merchant_id2'],
            'refund_id'             => $input['refund']['id'],
            'response_code'         => isset($content['errorCode']) ? $content['errorCode'] : '',
            'status_code'           => $content['status'],
            'error_message'         => isset($content['message']) ? $content['message'] : '',
        );

        return $refundAttributes;
    }

    protected function getRefundRequest($input)
    {
        $content = array(
            'command'           => Command::REFUND,
            'accessToken'       => $this->getAccessToken($input['terminal']),
            'uniqueId'          => $input['refund']['id'], // what to put here?
            'comments'          => 'Razorpay_refund',
            'udf'               => $input['payment']['public_id'],
            'returnUrl'         => '',
            'notificationUrl'   => '',
            'amount'            => $input['refund']['amount'] / 100,
            'balanceType'       => 'cash',
            'balanceName'       => 'cash',
            'saleId'            => $input['payment']['id'], //what to put here?
            'currency'          => self::CURRENCY,
        );

        $content['hash'] = $this->getHashForRefundRequest($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = ['Content-Type' => 'application/json'];

        return $request;
    }

    protected function getHashForRefundRequest(array $content)
    {
        $fieldsInOrder = array(
            'accessToken',
            'uniqueId',
            'comments',
            'udf',
            'returnUrl',
            'notificationUrl',
            'currency',
            'amount',
            'balanceType',
            'balanceName',
            'saleId',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getAccessToken($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_access_token'];
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getHashForOtpGenerate($content)
    {
        $fieldsInOrder = array(
            'accessToken',
            'uniqueId',
            'comments',
            'udf',
            'returnUrl',
            'notificationUrl',
            'currency',
            'amount',
            'couponCode'
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForOtpSubmit($content)
    {
        $fieldsInOrder = array(
            'accessToken',
            'command',
            'comments',
            'notificationUrl',
            'otp',
            'returnUrl',
            'udf',
            'uniqueId',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    public function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, "|");

        $str .= '|' . $this->getSecret();

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        return strtolower(hash('sha512', $str, false));
    }

    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        $url = constant($ns.'\Url::'.$type);

        return strtr($url, [':contact' => $this->input['payment']['contact']]);
    }

    public function checkExistingUser($input)
    {
        ;
    }

    protected function getStandardRequestArray($content = [], $method = 'post')
    {
        $request = array(
            'url' => $this->getUrl(),
            'method' => $method,
            'content' => json_encode($content),
        );

        return $request;
    }
}
