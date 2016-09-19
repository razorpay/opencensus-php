<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Trace\Trace;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Customer\Token;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Gateway\Wallet\Base\Entity;
use RZP\Http\Route;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Constants\HashAlgo;
use Carbon\Carbon;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_olamoney';

    protected $topup = true;

    protected $walletAccessTokenExpiry = 28800; // 8 hours - 8 * 60 * 60

    protected $map = array(
        Entity::EMAIL                   => Entity::EMAIL,
        Entity::CONTACT                 => Entity::CONTACT,
        ResponseFields::STATUS          => Entity::STATUS_CODE,
        ResponseFields::AMOUNT          => Entity::AMOUNT,
        Entity::RECEIVED                => Entity::RECEIVED,
        ResponseFields::TRANSACTION_ID  => Entity::GATEWAY_PAYMENT_ID,
    );

    // Not used in power-wallet flow
    // Used only for topup which is via a redirect flow
    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getBillGeneratorRequest($input);

        return $request;
    }

    // Not used in power-wallet flow
    // Used only for topup which is via a redirect flow
    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callbackTopupFlow($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequest($input);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, $content);

        $this->createWalletRefundEntity($content, $input);

        if ($content[ResponseFields::STATUS] !== ResponseFields::REFUND_SUCCESS_STATUS)
        {
            $message = isset($content[ResponseFields::MESSAGE]) ? $content[ResponseFields::MESSAGE] : null;

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $content[ResponseFields::STATUS],
                $message);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function getPaymentIdFromServerCallback($input)
    {
        return $input['merchantBillId'];
    }

    public function otpGenerate($input)
    {
        $this->action($input, Action::OTP_GENERATE);

        $request = $this->getOtpGenerateRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        // In 1 minute you can hit the RE-SEND-OTP API 4 times.
        // From 5th time within that 1 minute, API will return 429 status code with no content.
        // User can try again, from next minute.
        if ($response->status_code === 429)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED);
        }

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $code = $content['status'];

        // Payment fails, throw exception
        if ($code !== Status::SUCCESS)
        {
            $message = isset($content[ResponseFields::MESSAGE]) ? $content[ResponseFields::MESSAGE] : null;

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $code,
                $message);
        }
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $request = $this->getOtpSubmitRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        if (isset($content[ResponseFields::ACCESS_TOKEN]))
        {
            $data['token'] = $this->getTokenAttributes($content);

            $this->accessToken = $content[ResponseFields::ACCESS_TOKEN];

            $content[ResponseFields::ACCESS_TOKEN] = '';
            $content[ResponseFields::REFRESH_TOKEN] = '';
        }

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        // Payment fails, throw exception
        if (($content[ResponseFields::STATUS] !== Status::SUCCESS) or
            (isset($content[ResponseFields::ACCESS_TOKEN]) === false))
        {
            $message = isset($content[ResponseFields::MESSAGE]) ? $content[ResponseFields::MESSAGE] : null;

            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

            if (isset($message))
            {
                $errorCode = ResponseCode::getApiErrorCode($message);
            }

            throw new Exception\GatewayErrorException(
                $errorCode,
                $content[ResponseFields::STATUS],
                $message);
        }

        return $data;
    }

    public function checkBalance(array $input)
    {
        $this->action($input, Action::GET_BALANCE);

        $content[RequestFields::USER_ACCESS_TOKEN] = $this->accessToken;

        $request = [
            'url'       => $this->getUrl(),
            'method'    => 'post',
            'headers'   => $this->getRequestHeaders(),
        ];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $request['content'] = json_encode($content);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $userBalance = 0;

        if (($content[ResponseFields::STATUS] === Status::SUCCESS) and
            isset($content[ResponseFields::AMOUNT]))
        {
            $userBalance = ((int) ($content[ResponseFields::AMOUNT])) * 100;
        }

        if ($input['payment']['amount'] > $userBalance)
        {
            $input['payment']['amount'] = $input['payment']['amount'] - $userBalance;

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE);
        }
    }

    public function topup($input)
    {
        return $this->authorize($input);
    }

    public function debit(array $input)
    {
        $this->action($input, Action::DEBIT_WALLET);

        $request = $this->getDebitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        if (isset($content[ResponseFields::STATUS]) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        if ($content[ResponseFields::STATUS] !== Status::SUCCESS)
        {
            $code = null;

            if (isset($content[ResponseFields::MESSAGE]))
            {
                $code = $content[ResponseFields::MESSAGE];
            }
            else if (isset($content[ResponseFields::COMMENTS]))
            {
                $code = $content[ResponseFields::COMMENTS];
            }

            $errorCode = ResponseCode::getApiErrorCode($code);

            throw new Exception\GatewayErrorException($errorCode);
        }

        $this->verifySecureHash($content);

        $gatewayPaymentAttrs = $this->getCreateWalletAttributes($input, $content);

        $this->action = Action::AUTHORIZE;

        $this->createGatewayPaymentEntity($gatewayPaymentAttrs);

        $this->action = Action::DEBIT_WALLET;
    }

    protected function getDebitRequestArray($input)
    {
        $content = $this->getDebitRequestAttributes($input);
        $content = json_encode($content);

        $request = $this->getStandardRequestArray();

        $request['headers'] = $this->getRequestHeaders();

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $request['content'] = $content;

        return $request;
    }

    protected function getDebitRequestAttributes($input)
    {
        $amount = (string) number_format($input['payment']['amount'] / 100, 2, '.', '');

        $udf = [RequestFields::MERCHANT_DISPLAY_NAME => $input['merchant']->getBillingLabelElseName()];
        $udf = json_encode($udf);

        $notificationUrl = Route::getUrlWithPublicAuth('gateway_payment_callback_post',
                                            ['gateway' => 'wallet_olamoney']);

        $content = array(
            RequestFields::COMMAND              => Command::DEBIT,
            RequestFields::ACCESS_TOKEN         => $this->getAccessToken($input['terminal']),
            RequestFields::UNIQUE_ID            => $input['payment']['id'],
            RequestFields::COMMENTS             => 'Razorpay_payment',
            RequestFields::UDF                  => $udf,
            RequestFields::RETURN_URL           => 'NA',
            RequestFields::NOTIFICATION_URL     => $notificationUrl,
            RequestFields::AMOUNT               => $amount,
            RequestFields::CURRENCY             => $input['payment']['currency'],
            RequestFields::COUPON_CODE          => 'NA',
            RequestFields::USER_ACCESS_TOKEN    => $this->accessToken,
        );

        $content[RequestFields::HASH] = $this->getHashForDebit($content);

        return $content;
    }

    protected function callbackTopupFlow($input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'request'       => $input['gateway'],
                'gateway'       => $this->gateway,
                'payment_id'    => $input['payment']['id'],
            ]);

        $content = $input['gateway'];

        if ((isset($content['status']) === false) or
            ($content['status'] !== Status::SUCCESS))
        {
            throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            $content['status']);
        }

        // verify hash - when ola starts sending hash value

        $token = $this->getValidWalletToken($input);

        if ($token === null)
        {
            throw new Exception\BaseException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        $this->accessToken = $token->getGatewayToken();
    }

    protected function getCreateWalletAttributes($input, $content)
    {
        $contentToSave = array(
            ResponseFields::AMOUNT          => $input['payment']['amount'],
            Entity::RECEIVED                => true,
            Entity::EMAIL                   => $input['payment']['email'],
            Entity::CONTACT                 => $this->getFormattedContact($input['payment']['contact']),
            ResponseFields::STATUS          => $content[ResponseFields::STATUS],
            ResponseFields::TRANSACTION_ID  => $content[ResponseFields::TRANSACTION_ID],
        );

        return $contentToSave;
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];

        if ($content[ResponseFields::STATUS] !== Status::SUCCESS)
        {
            $message = isset($content[ResponseFields::MESSAGE]) ? $content[ResponseFields::MESSAGE] : null;

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content[ResponseFields::STATUS],
                $message);
        }
    }

    protected function verifySecureHash($content)
    {
        $fieldsInOrder = array(
            ResponseFields::TYPE,
            ResponseFields::STATUS,
            ResponseFields::MERCHANT_BILL_ID,
            ResponseFields::TRANSACTION_ID,
            ResponseFields::AMOUNT,
            ResponseFields::COMMENTS,
            ResponseFields::UDF,
            ResponseFields::IS_CASHBACK_ATTEMPTED,
            ResponseFields::IS_CASHBACK_SUCCESSFUL,
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

    protected function getBillGeneratorRequest($input)
    {
        $content = $this->getBillGeneratorAttributes($input);

        $queryArray[RequestFields::BILL] = base64_encode(json_encode($content));

        $query = http_build_query($queryArray);

        $request = [
            'method'  => 'get',
            'url'     => $this->getUrl(). '?' . $query,
            'content' => [],
        ];

        // find a good way to trace this

        return $request;
    }

    protected function getBillGeneratorAttributes($input)
    {
        $token = $this->getValidWalletToken($input);

        if ($token === null)
        {
            throw new Exception\BaseException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        $this->accessToken = $token->getGatewayToken();

        $amount = (string) number_format($input['payment']['amount'] / 100, 2, '.', '');

        $udf = [RequestFields::MERCHANT_DISPLAY_NAME => $input['merchant']->getBillingLabelElseName()];
        $udf = json_encode($udf);

        $content = array(
            RequestFields::COMMAND                  => Command::CREDIT,
            RequestFields::ACCESS_TOKEN             => $this->getAccessToken($input['terminal']),
            RequestFields::MERCHANT_REFERENCE_ID    => $input['payment']['id'],
            RequestFields::COMMENTS                 => 'Razorpay_payment',
            RequestFields::UDF                      => $udf,
            RequestFields::RETURN_URL               => $input['callbackUrl'],
            RequestFields::NOTIFICATION_URL         => 'NA',
            RequestFields::AMOUNT                   => $amount,
            RequestFields::USER_ACCESS_TOKEN        => $this->accessToken,
            RequestFields::CURRENCY                 => $input['payment']['currency'],
            RequestFields::BALANCE_TYPE             => 'cash',
            RequestFields::BALANCE_NAME             => 'cash',
        );

        $content[RequestFields::HASH] = $this->getHashForBill($content);

        return $content;
    }

    protected function getOtpGenerateRequestArray($input)
    {
        $payment = $input['payment'];

        $queryArray = array(
            RequestFields::PHONE    => $this->getFormattedContact($payment['contact']),
            RequestFields::EMAIL    => $payment['email'],
        );

        $query = http_build_query($queryArray);

        $url = $this->getUrl();

        $request = [
            'method'  => 'post',
            'content' => $queryArray,
            'url'     => $url. '?' . $query,
            'headers' => $this->getRequestHeaders(),
        ];

        return $request;
    }

    protected function getOtpSubmitRequestArray($input)
    {
        $payment = $input['payment'];

        $queryArray = array(
            RequestFields::PHONE    => $this->getFormattedContact($payment['contact']),
            RequestFields::OTP      => $input['gateway']['otp'],
        );

        $query = http_build_query($queryArray);

        $url = $this->getUrl();

        $request = [
            'method'  => 'post',
            'content' => $queryArray,
            'url'     => $url. '?' . $query,
            'headers' => $this->getRequestHeaders(),
        ];

        return $request;
    }

    protected function verifyPayment($verify)
    {
        // api wallet gateway entity
        $gatewayPayment = $verify->payment;

        $input = $verify->input;

        // Gateway response from verify_payment
        // Possible $verifyResponse status values - completed, failed, initialized, error
        $verifyResponse = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($verifyResponse[ResponseFields::STATUS] === Status::COMPLETED)
        {
            $this->checkVerifyStatusOnGatewaySuccess($gatewayPayment, $input, $verify);
        }
        else
        {
            $this->checkVerifyStatusOnGatewayFail($gatewayPayment, $input, $verify);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        if ($verify->match === false)
        {
            $verify->payment = $this->saveVerifyContent($gatewayPayment,
                                                        $verify);
        }

        return $verify->status;
    }

    protected function checkVerifyStatusOnGatewayFail($gatewayPayment, $input, $verify)
    {
        $verify->gatewaySuccess = false;

        $verifyResponse = $verify->verifyResponseContent;

        if (in_array($verifyResponse[ResponseFields::STATUS], ResponseFields::VERIFY_FAILED_STATUS))
        {
            // If payment is marked as success in api or in gateway entity,
            // but gateway's verify response returned false. This is an issue
            // and should ideally never happen.
            if ((($input['payment']['status'] !== PaymentStatus::FAILED) and
                 ($input['payment']['status'] !== PaymentStatus::CREATED)) or
                (($gatewayPayment !== null) and ($gatewayPayment['status_code'] === Status::SUCCESS)))
            {
                // Ideally both api payment entity status and gateway payment
                // entity status should be true, to reach this block. In case
                // even if one of them is not true, we log it.
                if (($input['payment']['status'] === PaymentStatus::FAILED) or
                    ($input['payment']['status'] === PaymentStatus::CREATED) or
                    (($gatewayPayment === null) or ($gatewayPayment['status_code'] !== Status::SUCCESS)))
                {
                    $this->trace->info(
                        TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                        [
                            'api_payment_status'      => $input['payment']['status'],
                            'gateway_verify_response' => $verifyResponse,
                            'payment_id'              => $input['payment']['id'],
                            'gateway_payment_status'  => $gatewayPayment['status_code'],
                        ]);
                }

                $verify->apiSuccess = true;

                $verify->status = VerifyResult::STATUS_MISMATCH;
            }
            else
            {
                $verify->apiSuccess = false;
            }
        }
    }

    protected function checkVerifyStatusOnGatewaySuccess($gatewayPayment, $input, $verify)
    {
        $verify->gatewaySuccess = true;

        $verifyResponse = $verify->verifyResponseContent;

        // $input['payment'] is api payment entity
        if (($input['payment']['status'] !== PaymentStatus::CREATED) and
            ($input['payment']['status'] !== PaymentStatus::FAILED) and
            ($gatewayPayment !== null) and
            ($gatewayPayment['status_code'] === Status::SUCCESS))
        {
            $verify->apiSuccess = true;
        }
        // api's payment entity could be in either authorized or captured state
        // and gateway's payment entity is either null or its status is failed.
        // This is an issue, and should ideally never happen.
        else if (($input['payment']['status'] !== PaymentStatus::CREATED) and
                 ($input['payment']['status'] !== PaymentStatus::FAILED) and
                 (($gatewayPayment === null) or
                  ($gatewayPayment['status_code'] !== Status::SUCCESS)))
        {
            $gatewayPaymentStatus = isset($walletPayment) ? $gatewayPayment['status_code'] : '';

            $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'api_payment_status'      => $input['payment']['status'],
                        'gateway_verify_response' => $verifyResponse[ResponseFields::STATUS],
                        'payment_id'              => $input['payment']['id'],
                        'gateway_payment_status'  => $gatewayPaymentStatus,
                    ]);

            $verify->apiSuccess = true;
        }
        else
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;

            $verify->apiSuccess = false;
        }
    }

    protected function saveVerifyContent($gatewayPayment, $verify)
    {
        $this->action = Action::AUTHORIZE;

        $verifyResponse = $verify->verifyResponseContent;

        if ($verify->gatewaySuccess === true)
        {
            $walletAttributes = $this->getVerifyWalletCreateAttributes($verifyResponse);

            if ($gatewayPayment === null)
            {
                $gatewayPayment = $this->createGatewayPaymentEntity($walletAttributes);
            }
            else if (($gatewayPayment['received'] === false) or
                     ($gatewayPayment['status_code'] !== Status::SUCCESS))
            {
                $gatewayPayment->fill($walletAttributes);
                $gatewayPayment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $gatewayPayment;
    }

    protected function getVerifyWalletCreateAttributes($verifyResponse)
    {
        $payment = $this->input['payment'];

        $contentToSave = array(
            ResponseFields::AMOUNT          => $payment['amount'],
            Entity::RECEIVED                => true,
            Entity::EMAIL                   => $payment['email'],
            Entity::CONTACT                 => $this->getFormattedContact($payment['contact']),
            ResponseFields::STATUS          => Status::SUCCESS,
            ResponseFields::TRANSACTION_ID  => $verifyResponse[ResponseFields::UNIQUE_BILL_ID],
        );

        return $contentToSave;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
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

    protected function createWalletRefundEntity($content, $input)
    {
        $refundAttributes = $this->getRefundEntityAttributesFromRefundResponse($content, $input);

        return $this->createGatewayRefundEntity($refundAttributes);
    }

    protected function getRefundEntityAttributesFromRefundResponse($content, $input)
    {
        $gatewayRefundId = null;

        if (isset($content[ResponseFields::TRANSACTION_ID]) === true)
        {
            $gatewayRefundId = $content[ResponseFields::TRANSACTION_ID];
        }

        $responseCode = isset($content[ResponseFields::ERROR_CODE]) ? $content[ResponseFields::ERROR_CODE] : null;

        $errorMessage = isset($content[ResponseFields::MESSAGE]) ? $content[ResponseFields::MESSAGE] : null;

        $refundAttributes = array(
            Entity::PAYMENT_ID              => $input['payment']['id'],
            Entity::ACTION                  => $this->action,
            Entity::AMOUNT                  => $input['payment']['amount'],
            Entity::RECEIVED                => 1,
            Entity::WALLET                  => $input['payment']['wallet'],
            Entity::EMAIL                   => $input['payment']['email'],
            Entity::CONTACT                 => $input['payment']['contact'],
            Entity::GATEWAY_REFUND_ID       => $gatewayRefundId,
            Entity::REFUND_ID               => $input['refund']['id'],
            Entity::RESPONSE_CODE           => $responseCode,
            Entity::STATUS_CODE             => $content[ResponseFields::STATUS],
            Entity::ERROR_MESSAGE           => $errorMessage,
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
            RequestFields::RETURN_URL       => 'NA',
            RequestFields::NOTIFICATION_URL => 'NA',
            RequestFields::AMOUNT           => (string) number_format($input['refund']['amount'] / 100, 2, '.', ''),
            RequestFields::BALANCE_TYPE     => 'cash',
            RequestFields::BALANCE_NAME     => 'cash',
            RequestFields::SALE_ID          => $input['payment']['id'],
            RequestFields::CURRENCY         => $input['payment']['currency'],
        );

        $content[RequestFields::HASH] = $this->getHashForRefundRequest($content);

        $request = $this->getStandardRequestArray(json_encode($content));

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

    protected function getHashForDebit($content)
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
            RequestFields::USER_ACCESS_TOKEN,
            RequestFields::COUPON_CODE,
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForBill($content)
    {
        $fieldsInOrder = array(
            RequestFields::ACCESS_TOKEN,
            RequestFields::MERCHANT_REFERENCE_ID,
            RequestFields::COMMENTS,
            RequestFields::UDF,
            RequestFields::RETURN_URL,
            RequestFields::NOTIFICATION_URL,
            RequestFields::CURRENCY,
            RequestFields::AMOUNT,
            RequestFields::USER_ACCESS_TOKEN,
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
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

    protected function getHashOfArray($content)
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
        return [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic '. $this->getBasicAuthToken()
                ];
    }

    protected function getBasicAuthToken()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getValidWalletToken($input)
    {
        $token = (new Token\Repository)
                        ->getByWalletTerminalAndCustomerId(
                            $input['payment']['wallet'],
                            $input['terminal']['id'],
                            $input['customer']['id']);

        if ($token !== null and $token->getExpiredAt() > time())
        {
            return $token;
        }
    }

    protected function getTokenAttributes($content)
    {
        $input = $this->input;

        $attributes = array(
            Token\Entity::METHOD           => 'wallet',
            Token\Entity::WALLET           => $input['payment']['wallet'],
            Token\Entity::TERMINAL_ID      => $input['terminal']['id'],
            Token\Entity::GATEWAY_TOKEN    => $content[ResponseFields::ACCESS_TOKEN],
            Token\Entity::GATEWAY_TOKEN2   => $content[ResponseFields::REFRESH_TOKEN],
            Token\Entity::EXPIRED_AT       => time() + $this->walletAccessTokenExpiry,
        );

        return $attributes;
    }
}
