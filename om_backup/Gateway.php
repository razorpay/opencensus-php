<?php

namespace RZP\Gateway\Wallet\Olamoney;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Gateway\Wallet\Base\Entity;
use RZP\Constants\Entity as ConstantEntity;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Models\Payment\Processor;
use RZP\Models\Payment as Payment;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\RSA;
use phpseclib\Crypt\AES;


class Gateway extends Base\Gateway
{
    use AuthorizeFailed;


    protected $gateway = 'wallet_olamoney';


    protected $map = [
        Entity::EMAIL                   => Entity::EMAIL,
        Entity::CONTACT                 => Entity::CONTACT,
        ResponseFields::STATUS          => Entity::STATUS_CODE,
        ResponseFields::AMOUNT          => Entity::AMOUNT,
        Entity::RECEIVED                => Entity::RECEIVED,
        ResponseFields::TRANSACTION_ID  => Entity::GATEWAY_PAYMENT_ID,
    ];


    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestData($input);

        $authorizeAttributes = $this->getCreateWalletAttributes($input);

        $this->createGatewayPaymentEntity($authorizeAttributes, Action::AUTHORIZE);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;

    }

    public function callback(array $input)
    {
        parent::callback($input);
        return $this->callbackDebitFlow($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequest($input);

        $this->traceGatewayPaymentRequest(
            $request,
            $input,
            TraceCode::GATEWAY_REFUND_REQUEST);

        $request['headers'] = $this->getRequestHeaders();

        $response = $this->sendGatewayRequest($request);

        $content = $this->parseResponseBody($response);

        $this->traceGatewayPaymentResponse(
            $content,
            $input,
            TraceCode::GATEWAY_REFUND_RESPONSE
        );

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

    public function alreadyRefunded(array $input)
    {
        $paymentId = $input['payment_id'];
        $refundAmount = $input['refund_amount'];
        $refundId = $input['refund_id'];

        $refundedEntities = $this->repo->findSuccessfulRefundByRefundId($refundId, Processor\Wallet::OLAMONEY);

        if ($refundedEntities->count() === 0)
        {
            return false;
        }

        $refundEntity = $refundedEntities->first();

        $refundEntityPaymentId = $refundEntity->getPaymentId();
        $refundEntityRefundAmount = $refundEntity->getAmount();
        $refundEntityStatusCode = $refundEntity->getStatusCode();

        $this->trace->info(
            TraceCode::GATEWAY_ALREADY_REFUNDED_INPUT,
            [
                'input'                 => $input['refund']['id'],
                'refund_payment_id'     => $refundEntityPaymentId,
                'gateway_refund_amount' => $refundEntityRefundAmount,
                'status_code'           => $refundEntityStatusCode,
            ]);

        if (($refundEntityPaymentId !== $paymentId) or
            ($refundEntityRefundAmount !== $refundAmount) or
            ($refundEntityStatusCode !== ResponseFields::REFUND_SUCCESS_STATUS))
        {
            return false;
        }

        return true;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }


    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $content = $this->sendRefundVerifyRequest($input);

        if ($content[ResponseFields::STATUS] === Status::COMPLETED)
        {
            return true;
        }
        else if ($content[ResponseFields::STATUS] === Status::ERROR)
        {
            return false;
        }

        throw new Exception\LogicException(
            'Unrecognized verify refund status',
            null,
            [
                'status'     => $content[ResponseFields::STATUS],
                'payment_id' => $input['refund']['payment_id'],
                'refund_id'  => $input['refund']['id'],
            ]);
    }

    protected function getDebitRequestArray(array $input)
    {
        $content = $this->getDebitRequestAttributes($input);

        $traceContent = $content;

        $request = $this->getStandardRequestArray();

        $traceContent[RequestFields::ACCESS_TOKEN] = '';
        $traceContent[RequestFields::HASH] = '';

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_DEBIT_REQUEST,
            [
                'request' => $request,
                'content' => $traceContent
            ]);

        $request['headers'] = $this->getRequestHeaders();

        $request['content'] = json_encode($content);

        return $request;
    }

    protected function getDebitRequestAttributes(array $input)
    {
        $amount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $udf = [RequestFields::MERCHANT_DISPLAY_NAME => $input['merchant']->getFilteredDba()];
        $udf = json_encode($udf);

        $notificationUrl = $this->route->getUrlWithPublicAuth(
                                'gateway_payment_callback_post',
                                ['gateway' => 'wallet_olamoney']);

        $content = [
            RequestFields::COMMAND              => Command::DEBIT,
            RequestFields::ACCESS_TOKEN         => $this->getAccessToken($input['terminal']),
            RequestFields::UNIQUE_ID            => $input['payment']['id'],
            RequestFields::COMMENTS             => $input['payment']['public_id'],
            RequestFields::UDF                  => $udf,
            RequestFields::RETURN_URL           => 'NA',
            RequestFields::NOTIFICATION_URL     => $notificationUrl,
            RequestFields::AMOUNT               => $amount,
            RequestFields::CURRENCY             => $input['payment']['currency'],
            RequestFields::COUPON_CODE          => 'NA',
            RequestFields::USER_ACCESS_TOKEN    => $input['token']['gateway_token'],
        ];

        $content[RequestFields::HASH] = $this->getHashForDebit($content);

        return $content;
    }

    protected function getCreateWalletAttributes(array $input)
    {
        $attributes = [
            Base\Entity::AMOUNT         => $input[ConstantEntity::PAYMENT][Payment\Entity::AMOUNT],
            Base\Entity::EMAIL          => $input[ConstantEntity::PAYMENT][Payment\Entity::EMAIL],
            Base\Entity::CONTACT        => $input[ConstantEntity::PAYMENT][Payment\Entity::CONTACT],
            Base\Entity::MERCHANT_ID    => $input[ConstantEntity::PAYMENT][Payment\Entity::MERCHANT_ID],
            Base\Entity::PAYMENT_ID     => $input[ConstantEntity::PAYMENT][Payment\Entity::ID],
        ];

        return $attributes;
    }

    protected function callbackAuthSuccessFlow(array $input, array $content)
    {
        $contentToSave = [
            ResponseFields::AMOUNT          => $input['payment']['amount'],
            Entity::RECEIVED                => true,
            Entity::EMAIL                   => $input['payment']['email'],
            Entity::CONTACT                 => $this->getFormattedContact($input['payment']['contact']),
            ResponseFields::STATUS          => $content[ResponseFields::STATUS],
            ResponseFields::TRANSACTION_ID  => $content[ResponseFields::TRANSACTION_ID],
        ];

        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);
    }

    protected function verifyPaymentCallbackResponse(array $input)
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

    protected function verifySecureHash(array $content)
    {
        $fieldsInOrder = [
            ResponseFields::TYPE,
            ResponseFields::STATUS,
            ResponseFields::MERCHANT_BILL_ID,
            ResponseFields::TRANSACTION_ID,
            //ResponseFields::UNIQUE_ID,
            ResponseFields::AMOUNT,
            ResponseFields::COMMENTS,
            ResponseFields::UDF,
            ResponseFields::IS_CASHBACK_ATTEMPTED,
            ResponseFields::IS_CASHBACK_SUCCESSFUL,
            ResponseFields::TIMESTAMP,
            ResponseFields::SALT,
        ];

        $actual = $content[ResponseFields::HASH];

        $content = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        $generated = $this->getHashOfArray($content);

        $this->compareHashes($actual, $generated);
    }

    protected function getAuthorizeRequestData(array $input)
    {
        $udf = [RequestFields::MERCHANT_DISPLAY_NAME => $input['merchant']->getFilteredDba()];
        $udf = json_encode($udf);

        $content = [
            RequestFields::ACCESS_TOKEN             => $this->getAccessToken($input['terminal']),
            RequestFields::UNIQUE_ID                => $input[ConstantEntity::PAYMENT]['id'],
            RequestFields::COMMENTS                 => 'Razorpay_payment',
            RequestFields::UDF                      => $udf,
            RequestFields::RETURN_URL               => $input['callbackUrl'],
            RequestFields::NOTIFICATION_URL         => '',
            RequestFields::CURRENCY                 => $input['payment']['currency'],
            RequestFields::AMOUNT                   => $this->getFormattedAmount($input[ConstantEntity::PAYMENT]['amount']),
            RequestFields::COUPON_CODE              => 'NA',
            RequestFields::BALANCE_PREFERENCE       =>'preferConfig',
        ];

        $content[RequestFields::HASH] = $this->getHashForDebit($content);


            $content[RequestFields::COMMAND]                  = Command::DEBIT;
            $content[RequestFields::USER_ACCESS_TOKEN]        = $input['token']['gateway_token'];
            $content[RequestFields::MOBILE]                   = $this->getFormattedContact($input['payment']['contact']);
            $content[RequestFields::EMAIL]                    = $this->getFormattedContact($input['payment']['email']);
            $content[RequestFields::LINK_NOTIFICATION_URL]    = $input['callbackUrl'];
            $content[RequestFields::SIGNATURE]                = $this->getSignature($input[ConstantEntity::PAYMENT]['id']);

        return $content;
    }

    public function getSignature($id) {

        $private_key = $this->getPrivateKey();

        $rsa = new RSA();
        extract($rsa->createKey());

        $rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS8);
        $rsa->loadKey(base64_decode($private_key), RSA::PRIVATE_FORMAT_PKCS8);

        $rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);

        return base64_encode($rsa->sign(base64_decode($id)));

    }

    public function validateSignature($plaintext, $signature) {
        //xtenantKey, xauthKey
        $ola_public_key = $this->getOlaPublicKey();

         $rsa = new RSA();
        extract($rsa->createKey());

        $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_PKCS8);
        $rsa->loadKey(base64_decode($ola_public_key));

        $rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);

        return $rsa->verify(base64_decode($plaintext),base64_decode($signature));

    }

    public function callbackDebitFlow(array $input)
    {
        $gatewayData = $input['gateway'];

        if( isset($gatewayData['errorCode'] )) {
            $code = $gatewayData['errorCode'];
            $errorCode = ResponseCode::getApiErrorCode($code);

            throw new Exception\GatewayErrorException($errorCode);

        }

        $body = $gatewayData['body'];
        $encryptedTenantKey = $gatewayData['xtenantKey'];
        $xauthKey = $gatewayData['xauthKey'];

        //Signature Verification
        if($this->validateSignature($encryptedTenantKey,$xauthKey) === false) {
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED);
        }

        $tenantKey = $this->decryptTenantKey($encryptedTenantKey);
        list($uuid, $timestamp) = explode(":",$tenantKey);

        //Check for timestamp is less than current timestamp
        if((intdiv($timestamp, 1000000)) > time() ) {
            throw new Exception\GatewayErrorException(ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT);
        }

        $response = $this->decryptResponseBody($body, $uuid);

        $content = $this->jsonToArray($response);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_DEBIT_RESPONSE,
            [
                'response' => $content,
               'payment_id' => $content['merchantBillId']
            ]);

        if ((isset($content[ResponseFields::STATUS]) === false) or
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $code = null;

            if (isset($content[ResponseFields::MESSAGE]) === true)
            {
                $code = $content[ResponseFields::MESSAGE];
            }
            else if (isset($content[ResponseFields::COMMENTS]) === true)
            {
                $code = $content[ResponseFields::COMMENTS];
            }

            $errorCode = ResponseCode::getApiErrorCode($code);

            throw new Exception\GatewayErrorException($errorCode);
        }

        $this->verifySecureHash($content);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount = number_format((float) $content[ResponseFields::AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->callbackAuthSuccessFlow($input,$content);

        return $this->getCallbackResponseData($input);
    }

    protected function decryptTenantKey($encryptedTenantKey)
    {
        $private_key = $this->getPrivateKey();
        $rsa = new RSA();

        $rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS8);

        $rsa->loadKey(base64_decode($private_key), RSA::PRIVATE_FORMAT_PKCS8);

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa->decrypt(base64_decode($encryptedTenantKey));
    }

    protected function decryptResponseBody($body, $uuid) {

        $getIvParamFromConfig = $this->getIV();

        $cipher = new AES();

        $cipher->setKey($uuid);

        $cipher->setIV($getIvParamFromConfig);

        return ($cipher->decrypt(base64_decode($body)));

    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
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

    protected function checkVerifyStatusOnGatewayFail($gatewayPayment, array $input, $verify)
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

    protected function checkVerifyStatusOnGatewaySuccess($gatewayPayment, array $input, $verify)
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
            $gatewayPaymentStatus = isset($gatewayPayment) ? $gatewayPayment['status_code'] : '';

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

    protected function getVerifyWalletCreateAttributes(array $verifyResponse)
    {
        $payment = $this->input['payment'];

        $contentToSave = [
            ResponseFields::AMOUNT          => $payment['amount'],
            Entity::RECEIVED                => true,
            Entity::EMAIL                   => $payment['email'],
            Entity::CONTACT                 => $this->getFormattedContact($payment['contact']),
            ResponseFields::STATUS          => Status::SUCCESS,
            ResponseFields::TRANSACTION_ID  => $verifyResponse[ResponseFields::UNIQUE_BILL_ID],
        ];

        return $contentToSave;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input, 'payment');

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->parseResponseBody($response);

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

    protected function sendRefundVerifyRequest(array $input): array
    {
        $request = $this->getVerifyRequestArray($input, 'refund');

        $this->traceGatewayPaymentRequest(
            $request,
            $input,
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $content = $this->parseResponseBody($response);

        $this->traceGatewayPaymentResponse(
            $content,
            $input,
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE);

        return $content;
    }

    protected function getVerifyRequestArray(array $input, string $entity)
    {
        $content = [
            RequestFields::UNIQUE_BILL_ID   => $input[$entity]['id'],
            RequestFields::ACCESS_TOKEN     => $this->getAccessToken($input['terminal']),
            RequestFields::TIMESTAMP        => Carbon::now(Timezone::IST)->format('Y-m-d H:i:s'),
        ];

        $content[RequestFields::HASH] = $this->getHashForVerifyRequest($content);

        $request = $this->getStandardRequestArray($content, 'GET');

        return $request;
    }

    protected function getHashForVerifyRequest(array $content)
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

    protected function createWalletRefundEntity(array $content, array $input)
    {
        $refundAttributes = $this->getRefundEntityAttributesFromRefundResponse($content, $input);

        return $this->createGatewayRefundEntity($refundAttributes);
    }

    protected function getRefundEntityAttributesFromRefundResponse(array $content, array $input)
    {
        $gatewayRefundId = null;

        if (isset($content[ResponseFields::TRANSACTION_ID]) === true)
        {
            $gatewayRefundId = $content[ResponseFields::TRANSACTION_ID];
        }

        $responseCode = isset($content[ResponseFields::ERROR_CODE]) ? $content[ResponseFields::ERROR_CODE] : null;

        $errorMessage = isset($content[ResponseFields::MESSAGE]) ? $content[ResponseFields::MESSAGE] : null;

        $refundAttributes = [
            Entity::PAYMENT_ID              => $input['payment']['id'],
            Entity::ACTION                  => $this->action,
            Entity::AMOUNT                  => $input['refund']['amount'],
            Entity::RECEIVED                => 1,
            Entity::WALLET                  => $input['payment']['wallet'],
            Entity::EMAIL                   => $input['payment']['email'],
            Entity::CONTACT                 => $input['payment']['contact'],
            Entity::GATEWAY_REFUND_ID       => $gatewayRefundId,
            Entity::REFUND_ID               => $input['refund']['id'],
            Entity::RESPONSE_CODE           => $responseCode,
            Entity::STATUS_CODE             => $content[ResponseFields::STATUS],
            Entity::ERROR_MESSAGE           => $errorMessage,
        ];

        return $refundAttributes;
    }

    protected function getRefundRequest($input)
    {
        $content = [
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
        ];

        $content[RequestFields::HASH] = $this->getHashForRefundRequest($content);

        $request = $this->getStandardRequestArray(json_encode($content));

        return $request;
    }

    protected function getHashForRefundRequest(array $content)
    {
        $content[RequestFields::SALT] = $this->getSecret();
        $fieldsInOrder = [
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
            RequestFields::SALT,
        ];

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

    protected function getHashForDebit(array $content)
    {
        $fieldsInOrder = [
            RequestFields::ACCESS_TOKEN,
            RequestFields::UNIQUE_ID,
            RequestFields::COMMENTS,
            RequestFields::UDF,
            RequestFields::RETURN_URL,
            RequestFields::NOTIFICATION_URL,
            RequestFields::CURRENCY,
            RequestFields::AMOUNT,
            RequestFields::COUPON_CODE,
            RequestFields::BALANCE_PREFERENCE,

        ];

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

//    protected function getRelativeUrl($type)
//    {
//        $ns = $this->getGatewayNamespace();
//
//        $url = constant($ns.'\Url::'.$type);
//
//        $contact = $this->input['payment']['contact'];
//
//        return strtr($url, [':contact' => $this->getFormattedContact($contact)]);
//    }

    protected function getRequestHeaders()
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic '. $this->getBasicAuthToken()
        ];
    }

    protected function getBasicAuthToken()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->input['terminal']['gateway_terminal_password'];
    }

    public function getPrivateKey()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_private_key'];
        }

        return $this->config['live_private_key'];
    }

    public function getOlaPublicKey()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_ola_public_key'];
        }

        return $this->config['live_ola_public_key'];
    }
    public function getPublicKey()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_public_key'];
        }

        return $this->config['live_public_key'];
    }

    public function getIV()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_iv'];
        }

        return $this->config['live_iv'];
    }

    protected function parseResponseBody(\Requests_Response $response)
    {
        if ($response === '')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                '',
                'Invalid JSON in Response Body');
        }

        $content = $this->jsonToArray($response->body);

        return $content;
    }

}


