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

    protected $gateway = 'wallet_olamoney';

    protected $canRunOtpFlow = true;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getBillGeneratorRequest($input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callRedirectFlow($input);
    }

    public function callRedirectFlow($input)
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

    protected createGatewayPaymentEntity($input)
    {
        $contentToSave = array(
            'payment_id'=> $input['payment']['id'],
            'action'    => $this->action;
            'amount'    => $input['payment']['amount'],
            'wallet'    => self::$gateway,
            'received'  => true,
            'email'     => $input['payment']['email'],
            'contact'   => $this->getFormattedContact($input['payment']['contact']),
            'status_code'    => $content['status'],
            'txnId'     => $content['result'],
            'message'   => $content['error_message'],
        );

        parent::createGatewayPaymentEntity($contentToSave);
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];
        $code = (int) $input['gateway']['statuscode'];

        if ($content['statuscode'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $input['gateway']['statuscode'],
                $input['gateway']['statusmessage']);
        }
    }

    protected function verifySecureHash($content)
    {
        $fieldsInOrder = array(
            'type',
            'status',
            'merchantBillId',
            'transactionId',
            'amount',
            'comments',
            'udf',
            //'timestamp', ?
            //'salt', ?
        );

        $hash = $content['checksum'];

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

        $request = [
            'method'  => 'get',
            'url'     => $request['url'] . '?'. http_build_query([
                'bill'  => base64_encode($request['content']),
                'phone' => $input['payment']['contact'],
            ], null, '&'),
            'content' => []
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
        s($input['callbackUrl']);
        $content = array(
            'command'           => Command::DEBIT,
            'accessToken'       => $this->getAccessToken($input['terminal']),
            'uniqueId'          => $input['payment']['id'],
            'comments'          => 'Razorpay_payment',
            'udf'               => $input['payment']['public_id'],
            'returnUrl'         => $input['callbackUrl'],
            'notificationUrl'   => $input['callbackUrl'],
            'amount'            => number_format($amount, 2, '.', ''),
            'currency'          => 'INR',
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
            'currency'          => 'INR',
            'couponCode'        => 'NA',
            'otp'               => $input['gateway']['otp'],
        );

        $content['hash'] = $this->getHashForOtpSubmit($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
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

    protected function getHashOfArray($content)
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
