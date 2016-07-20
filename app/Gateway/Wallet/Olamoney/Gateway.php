<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Trace\Trace;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Olamoney\Action;
use RZP\Models\Payment\Status as PaymentStatus;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_olamoney';

    protected $canRunOtpFlow = false;

    protected $map = array(
        'email'                 => 'email',
        'contact'               => 'contact',
        'status'                => 'status_code',
        'amount'                => 'amount',
        'received'              => 'received',
        'gateway_merchant_id'   => 'gateway_merchant_id',
        'transactionId'         => 'gateway_payment_id',
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

        //  Changing action to AUTHORIZE to keep the action consistent
        $this->action = Action::AUTHORIZE;

        $gatewayPaymentAttrs = $this->getCreateWalletAttributes($input);

        $this->createGatewayPaymentEntity($gatewayPaymentAttrs);

        $this->action = Action::CALLBACK;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'request' => $input['gateway'],
                'gateway' => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        $this->verifyPaymentCallbackResponse($input);
    }

    protected function getCreateWalletAttributes($input)
    {
        $contentToSave = array(
            'amount'                => (string) ($input['payment']['amount']),
            'received'              => true,
            'email'                 => $input['payment']['email'],
            'contact'               => $this->getFormattedContact($input['payment']['contact']),
            'gateway_merchant_id'   => $this->getMerchantId($input['terminal']),
            'status'                => $input['gateway']['status'],
            'transactionId'         => $input['gateway']['transactionId'],
        );

        return $contentToSave;
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

    protected function getBillGeneratorRequest($input)
    {
        $content = $this->getOtpGenerateAttributes($input);

        $queryArray = array(
                        'bill'   => base64_encode(json_encode($content)),
                        'phone'  => $input['payment']['contact'],
                    );

        $query = http_build_query($queryArray);

        $url = $this->getUrl();

        $request = [
            'method'  => 'get',
            'url'     => $url. '?' . $query,
        ];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getOtpGenerateAttributes($input)
    {
        $amount = (string) ($input['payment']['amount'] / 100);

        $content = array(
            'command'           => Command::DEBIT,
            'accessToken'       => $this->getAccessToken($input['terminal']),
            'uniqueId'          => $input['payment']['id'],
            'comments'          => 'Razorpay_payment',
            'udf'               => $input['payment']['public_id'],
            'returnUrl'         => $input['callbackUrl'],
            'notificationUrl'   => '',
            'amount'            => $amount,
            'currency'          => $input['payment']['currency'],
            'couponCode'        => 'NA',
        );

        $content['hash'] = $this->getHashForOtpGenerate($content);

        return $content;
    }

    protected function getOtpGenerateRequestArray($input)
    {
        $content = $this->getOtpGenerateAttributes($input);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = ['Content-Type' => 'application/json'];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getOtpSubmitRequestArray($input)
    {
        $payment = $input['payment'];

        $content = array(
            'command'           => Command::CAPTURE,
            'accessToken'       => $this->getAccessToken($input['terminal']),
            'uniqueId'          => $payment['id'],
            'comments'          => 'Razorpay payment',
            'udf'               => $payment['public_id'],
            'returnUrl'         => $input['callbackUrl'],
            'notificationUrl'   => '',
            'amount'            => (string) ($payment['amount'] / 100),
            'currency'          => $payment['currency'],
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

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyPayment($verify)
    {
        // api wallet gateway entity
        $walletPayment = $verify->payment;

        $input = $verify->input;

        // Response received from wallet gateway
        // Possible $verifyResponse status values - completed, failed, initialized, error
        $verifyResponse = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($verifyResponse['status'] === Status::COMPLETED)
        {
            $this->checkVerifyStatusOnGatewaySuccess($input, $verify);
        }
        else
        {
            $this->checkVerifyStatusOnGatewayFail($walletPayment, $input, $verify);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        if (!$verify->match)
        {
            $verify->payment = $this->saveVerifyContent($walletPayment,
                                                        $verifyResponse);
        }

        return $verify->status;
    }

    protected function checkVerifyStatusOnGatewayFail($walletPayment, $input, $verify)
    {
        $verify->gatewaySuccess = false;

        if (in_array($verifyResponse['status'], array(Status::INITIATED, Status::FAILED)))
        {
            if (($walletPayment === null) and
                (($input['payment']['status'] === PaymentStatus::FAILED) or
                 ($input['payment']['status'] === PaymentStatus::CREATED)))
            {
                $verify->apiSuccess = false;
            }
            else if ($walletPayment !== null)
            {
                if (($walletPayment['received'] === false) and
                ($walletPayment['status_code'] === null or
                    $walletPayment['status_code'] !== Status::SUCCESS))
                {
                    $verify->apiSuccess = false;
                }
                else if ($walletPayment['status'] === Status::SUCCESS)
                {
                    $verify->status = VerifyResult::STATUS_MISMATCH;
                    $verify->apiSuccess = true;
                }
            }
        }
    }

    protected function checkVerifyStatusOnGatewaySuccess($input, $verify)
    {
        $verify->gatewaySuccess = true;

        // $input['payment'] is api payment entity
        if (($input['payment']['status'] !== PaymentStatus::CREATED) and
            ($input['payment']['status'] !== PaymentStatus::FAILED))
        {
            $verify->apiSuccess = true;
        }
        else
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = false;
        }
    }

    protected function saveVerifyContent($walletPayment, $verifyResponse)
    {
        $this->action = Action::AUTHORIZE;

        if (isset($verifyResponse['status']) and $verifyResponse['status'] === Status::COMPLETED)
        {
            $walletAttributes = $this->getVerifyWalletCreateAttributes($walletPayment,
                $verifyResponse);

            if ($walletPayment === null)
            {
                $walletPayment = $this->createGatewayPaymentEntity($walletAttributes);
            }
            else if ($walletPayment['received'] === false or $walletPayment['status'] !== Status::SUCCESS)
            {
                $walletPayment->fill($walletAttributes);
                $walletPayment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $walletPayment;
    }

    protected function getVerifyWalletCreateAttributes($walletPayment, $verifyResponse)
    {
        $payment = $this->input['payment'];

        $contentToSave = array(
            'amount'                => (string) ($payment['amount']),
            'received'              => true,
            'email'                 => $payment['email'],
            'contact'               => $this->getFormattedContact($payment['contact']),
            'gateway_merchant_id'   => $this->getMerchantId($this->input['terminal']),
            'status'                => Status::SUCCESS,
            'transactionId'         => $verifyResponse['uniqueBillId'],
        );

        return $contentToSave;
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $content,
                'gateway' => 'wallet_olamoney',
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
            'timestamp' => date('Y/m/d h:m:s'),
            );

        $content['hash'] = $this->getHashForVerifyRequest($content);

        $request = $this->getStandardRequestArray($content, 'GET');

        return $request;
    }

    protected function getHashForVerifyRequest($content)
    {
        $str = $content['accessToken'] . '|';
        $str .= $content['uniqueBillId'] . '||';
        $str .= $content['timestamp'] . '|||';
        $str .= $this->getSecret();

        return $this->getHashOfString($str);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequest($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

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
            'gateway_merchant_id'   => $this->getMerchantId($input['terminal']),
            'gateway_refund_id'     => isset($content['transactionId']) ? $content['transactionId'] : null,
            'refund_id'             => $input['refund']['id'],
            'response_code'         => isset($content['errorCode']) ? $content['errorCode'] : null,
            'status_code'           => $content['status'],
            'error_message'         => isset($content['message']) ? $content['message'] : null,
        );

        return $refundAttributes;
    }

    protected function getRefundRequest($input)
    {
        $content = array(
            'command'           => Command::REFUND,
            'accessToken'       => $this->getAccessToken($input['terminal']),
            'uniqueId'          => $input['refund']['id'],
            'comments'          => 'Razorpay_refund',
            'udf'               => $input['payment']['public_id'],
            'returnUrl'         => '',
            'notificationUrl'   => '',
            'amount'            => (string) ($input['refund']['amount'] / 100),
            'balanceType'       => 'cash',
            'balanceName'       => 'cash',
            'saleId'            => $input['payment']['id'],
            'currency'          => $input['payment']['currency'],
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
            return $this->config['test_access_code'];
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
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

        $contact = $this->input['payment']['contact'];

        return strtr($url, [':contact' => $this->getFormattedContact($contact)]);
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }
}
