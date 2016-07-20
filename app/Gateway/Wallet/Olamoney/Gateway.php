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
use RZP\Constants\HashAlgo;
use Carbon\Carbon;
use RZP\Gateway\Wallet\Olamoney\RequestFields as RequestFields;
use RZP\Gateway\Wallet\Olamoney\ResponseFields as ResponseFields;

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

    protected function callbackRedirectFlow($input)
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
            ResponseFields::TYPE,
            ResponseFields::STATUS,
            ResponseFields::MERCHANT_BILL_ID,
            ResponseFields::TRANSACTION_ID,
            ResponseFields::AMOUNT,
            ResponseFields::COMMENTS,
            ResponseFields::UDF,
            ResponseFields::TIMESTAMP,
        );

        $hash = $content[ResponseFields::HASH];

        $content = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        $generatedHash = $this->getHashOfArray($content);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    /**
     * This method will be used when power-wallet is enabled for Olamoney.
     * It is currently implemented using redirect-flow and not as a power-wallet.
     */
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
                $errorCode = ResponseCode::getApiErrorCode($content['errorCode']);
            }

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['status'],
                $content['message']);
        }
    }

    /**
     * This method will be used when power-wallet is enabled for Olamoney.
     * It is currently implemented using redirect-flow and not as a power-wallet.
     */
    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $request = $this->getOtpSubmitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        if ($content[ResponseFields::STATUS] !== Status::SUCCESS)
        {
            $errorCode = ResponseCode::getApiErrorCode($content[ResponseFields::ERROR_CODE]);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content[ResponseFields::STATUS],
                $content[ResponseFields::MESSAGE]);
        }
    }

    protected function getBillGeneratorRequest($input)
    {
        $content = $this->getOtpGenerateAttributes($input);

        $queryArray = array(
            RequestFields::BILL   => base64_encode(json_encode($content)),
            RequestFields::PHONE  => $input['payment']['contact'],
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
            RequestFields::COMMAND          => Command::DEBIT,
            RequestFields::ACCESS_TOKEN     => $this->getAccessToken($input['terminal']),
            RequestFields::UNIQUE_ID        => $input['payment']['id'],
            RequestFields::COMMENTS         => 'Razorpay_payment',
            RequestFields::UDF              => $input['payment']['public_id'],
            RequestFields::RETURN_URL       => $input['callbackUrl'],
            RequestFields::NOTIFICATION_URL => '',
            RequestFields::AMOUNT           => $amount,
            RequestFields::CURRENCY         => $input['payment']['currency'],
            RequestFields::COUPON_CODE      => 'NA',
        );

        $content[RequestFields::HASH] = $this->getHashForOtpGenerate($content);

        return $content;
    }

    protected function getOtpGenerateRequestArray($input)
    {
        $content = $this->getOtpGenerateAttributes($input);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders();

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getOtpSubmitRequestArray($input)
    {
        $payment = $input['payment'];

        $content = array(
            RequestFields::COMMAND           => Command::CAPTURE,
            RequestFields::ACCESS_TOKEN       => $this->getAccessToken($input['terminal']),
            RequestFields::UNIQUE_ID          => $payment['id'],
            RequestFields::COMMENTS          => 'Razorpay payment',
            RequestFields::UDF               => $payment['public_id'],
            RequestFields::RETURN_URL         => $input['callbackUrl'],
            RequestFields::NOTIFICATION_URL   => '',
            RequestFields::AMOUNT            => (string) ($payment['amount'] / 100),
            RequestFields::CURRENCY          => $payment['currency'],
            RequestFields::COUPON_CODE        => 'NA',
            RequestFields::OTP               => $input['gateway']['otp'],
        );

        $content[RequestFields::HASH] = $this->getHashForOtpSubmit($content);

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

        if ($verifyResponse[ResponseFields::STATUS] === Status::COMPLETED)
        {
            $this->checkVerifyStatusOnGatewaySuccess($walletPayment, $input, $verify);
        }
        else
        {
            $this->checkVerifyStatusOnGatewayFail($walletPayment, $input, $verify);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        if ($verify->match === false)
        {
            $verify->payment = $this->saveVerifyContent($walletPayment,
                                                        $verifyResponse);
        }

        return $verify->status;
    }

    protected function checkVerifyStatusOnGatewayFail($walletPayment, $input, $verify)
    {
        $verify->gatewaySuccess = false;

        $verifyResponse = $verify->verifyResponseContent;

        if (in_array($verifyResponse[ResponseFields::STATUS],
                array(Status::INITIATED, Status::FAILED)))
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

    protected function checkVerifyStatusOnGatewaySuccess($walletPayment, $input, $verify)
    {
        $verify->gatewaySuccess = true;

        // $input['payment'] is api payment entity
        if (($input['payment']['status'] !== PaymentStatus::CREATED) and
            ($input['payment']['status'] !== PaymentStatus::FAILED) and
            ($walletPayment !== null) and
            $walletPayment['status_code'] === Status::SUCCESS)
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

        if (isset($verifyResponse[ResponseFields::STATUS]) and
            ($verifyResponse[ResponseFields::STATUS] === Status::COMPLETED))
        {
            $walletAttributes = $this->getVerifyWalletCreateAttributes($verifyResponse);

            if ($walletPayment === null)
            {
                $walletPayment = $this->createGatewayPaymentEntity($walletAttributes);
            }
            else if (($walletPayment['received'] === false) or
                ($walletPayment['status'] !== Status::SUCCESS))
            {
                $walletPayment->fill($walletAttributes);
                $walletPayment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $walletPayment;
    }

    protected function getVerifyWalletCreateAttributes($verifyResponse)
    {
        $payment = $this->input['payment'];

        $contentToSave = array(
            'amount'                => $payment['amount'],
            'received'              => true,
            'email'                 => $payment['email'],
            'contact'               => $this->getFormattedContact($payment['contact']),
            'gateway_merchant_id'   => $this->getMerchantId($this->input['terminal']),
            'status'                => Status::SUCCESS,
            'transactionId'         => $verifyResponse['uniqueBillId'],
        );

        return $contentToSave;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        // $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $content,
                'gateway' => 'wallet_olamoney',
                'payment_id' => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $response;

        $verify->verifyResponseBody = $response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getVerifyRequestArray($input)
    {
        $content = array(
            RequestFields::UNIQUE_BILL_ID   => $input['payment']['id'],
            RequestFields::ACCESS_TOKEN     => $this->getAccessToken($input['terminal']),
            RequestFields::TIMESTAMP        => Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s'),
            );

        $content[RequestFields::HASH] = $this->getHashForVerifyRequest($content);

        $request = $this->getStandardRequestArray($content, 'GET');

        return $request;
    }

    protected function getHashForVerifyRequest($content)
    {
        $str = $content[RequestFields::ACCESS_TOKEN] . '|';
        $str .= $content[RequestFields::UNIQUE_BILL_ID] . '||';
        $str .= $content[RequestFields::TIMESTAMP] . '|||';
        $str .= $this->getSecret();

        return $this->getHashOfString($str);
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
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

        if ($content[ResponseFields::STATUS] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $content[ResponseFields::STATUS],
                $content[ResponseFields::MESSAGE]);
        }
    }

    protected function createWalletRefundEntity($content, $input)
    {
        $refundAttributes = $this->getRefundEntityAttributesFromRefundResponse($content, $input);

        return $this->createGatewayRefundEntity($refundAttributes);
    }

    protected function getRefundEntityAttributesFromRefundResponse($content, $input)
    {
        $gateway_refund_id = isset($content[ResponseFields::TRANSACTION_ID]) ? $content[ResponseFields::TRANSACTION_ID] : null;

        $response_code = isset($content[ResponseFields::ERROR_CODE]) ? $content[ResponseFields::ERROR_CODE] : null;

        $error_message = isset($content[ResponseFields::MESSAGE]) ? $content[ResponseFields::MESSAGE] : null;

        $refundAttributes = array(
            'payment_id'            => $input['payment']['id'],
            'action'                => $this->action,
            'amount'                => $input['payment']['amount'],
            'received'              => 1,
            'wallet'                => $input['payment']['wallet'],
            'email'                 => $input['payment']['email'],
            'contact'               => $input['payment']['contact'],
            'gateway_merchant_id'   => $this->getMerchantId($input['terminal']),
            'gateway_refund_id'     => $gateway_refund_id,
            'refund_id'             => $input['refund']['id'],
            'response_code'         => $response_code,
            'status_code'           => $content[ResponseFields::STATUS],
            'error_message'         => $error_message,
        );

        return $refundAttributes;
    }

    protected function getRefundRequest($input)
    {
        $content = array(
            RequestFields::COMMAND          => Command::REFUND,
            RequestFields::ACCESS_TOKEN     => $this->getAccessToken($input['terminal']),
            RequestFields::UNIQUE_ID        => $input['refund']['id'],
            RequestFields::COMMENTS         => 'Razorpay_refund',
            RequestFields::UDF              => $input['payment']['public_id'],
            RequestFields::RETURN_URL       => '',
            RequestFields::NOTIFICATION_URL => '',
            RequestFields::AMOUNT           => (string) ($input['refund']['amount'] / 100),
            RequestFields::BALANCE_TYPE     => 'cash',
            RequestFields::BALANCE_NAME     => 'cash',
            RequestFields::SALE_ID          => $input['payment']['id'],
            RequestFields::CURRENCY         => $input['payment']['currency'],
        );

        $content[RequestFields::HASH] = $this->getHashForRefundRequest($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders();

        return $request;
    }

    protected function getHashForRefundRequest(array $content)
    {
        $fieldsInOrder = array(
            RequestFields::ACCESS_TOKEN,
            RequestFields::UNIQUE_ID,
            RequestFields::COMMENTS,
            RequestFields::UDF,
            RequestFields::RETURN_URL,
            RequestFields::NOTIFICATION_URL,
            RequestFields::CURRENCY,
            RequestFields::AMOUNT,
            RequestFields::BALANCE_TYPE,
            RequestFields::BALANCE_NAME,
            RequestFields::SALE_ID,
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

        return $terminal['gateway_access_code'];
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
            RequestFields::ACCESS_TOKEN,
            RequestFields::UNIQUE_ID,
            RequestFields::COMMENTS,
            RequestFields::UDF,
            RequestFields::RETURN_URL,
            RequestFields::NOTIFICATION_URL,
            RequestFields::CURRENCY,
            RequestFields::AMOUNT,
            RequestFields::COUPON_CODE,
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForOtpSubmit($content)
    {
        $fieldsInOrder = array(
            RequestFields::ACCESS_TOKEN,
            RequestFields::COMMAND,
            RequestFields::COMMENTS,
            RequestFields::NOTIFICATION_URL,
            RequestFields::OTP,
            RequestFields::RETURN_URL,
            RequestFields::UDF,
            RequestFields::UNIQUE_ID,
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
        return strtolower(hash(HashAlgo::SHA512, $str));
    }

    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        $url = constant($ns.'\Url::'.$type);

        $contact = $this->input['payment']['contact'];

        return strtr($url, [':contact' => $this->getFormattedContact($contact)]);
    }

    protected function getRequestHeaders()
    {
        return ['Content-Type' => 'application/json'];
    }
}
