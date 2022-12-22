<?php

namespace RZP\Gateway\Wallet\Payzapp;

use View;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Models\Payment\Gateway as PaymentGateway;
use RZP\Diag\EventCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_payzapp';
    protected $pgname  = 'hdfcpg';

    protected $map = [
        'custEmail'         => 'email',
        'custMobile'        => 'contact',
        'merId'             => 'gateway_merchant_id',
        'wibmoTxnId'        => 'gateway_payment_id',
        'pgTxnId'           => 'gateway_payment_id_2',
        'pgVoidTxnId'       => 'gateway_refund_id',
        'resCode'           => 'response_code',
        'resDesc'           => 'response_description',
        'pgStatusCode'      => 'status_code',
        'dataPickUpCode'    => 'reference1',
        'actionCode'        => 'reference2',
        'txnAmount'         => 'amount',
    ];

    protected $performMap = [
        'void'              => 'processMerchantAPI#DirectVoid',
        'refund'            => 'processMerchantAPI#DirectRefund',
        'voidOrRefund'      => 'processMerchantAPI#DirectVoidORRefund',
        'verify'            => 'getPaymentResult',
    ];

    protected $perform;

    protected $acosaActions = [
        Action::VERIFY, Action::REFUND
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthContent($input);

        $contentToSave = [];

        foreach ($content as $category => $info)
        {
            foreach ($info as $key => $value)
            {
                $contentToSave[$key] = $value;
            }
        }

        $contentToSave['supportedPaymentType'] = '*';

        $payment = $this->createGatewayPaymentEntity($contentToSave);

        $content['msgHash'] = $this->getHashForAuthorizeRequest($contentToSave);

        $request = array(
            'url'     => $this->getUrlDomain(),
            'method'  => 'direct',
            'content' => $content,
            'callback_url' => $input['callbackUrl'],
        );

        $this->traceGatewayPaymentRequest($request, $input);

        $request['content'] = View::make('gateway.payzapp')
                                  ->with('request', $request)
                                  ->render();

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->verifyPaymentCallbackResponse($input['gateway']);

        $this->assertPaymentId($input['payment']['id'], $input['gateway']['merTxnId']);

        $payment = $this->repo->findByPaymentIdAndAction(
                    $input['gateway']['merTxnId'], Action::AUTHORIZE);

        $mappedPayment = $this->getReverseMappedAttributes($payment->toArray());

        $this->verifyGatewaySecureHash($input, $mappedPayment);

        $attrs = $this->getMappedAttributes($input['gateway']);
        $attrs['received'] = true;

        $serverData = $this->pickupData($input);

        $this->assertAmount($input['payment']['amount'], $serverData['data']['txnAmt']);

        $this->verifyPaymentCallbackResponse($serverData);

        $attrs['gateway_payment_id_2'] = $serverData['data']['pgTxnId'];

        $payment->fill($attrs);
        $payment->saveOrFail();

        $paymentEntity = $this->app['repo']->payment->findOrFail($input['payment']['id']);

        $properties = [
            'cardMasked' => mask_except_last4($serverData['data']['cardMasked']),
            'bin'        => $serverData['data']['bin'],
            'pgAuthCode' => $serverData['data']['pgAuthCode']
        ];

        $this->app['diag']->trackPaymentEventV2(
            EventCode::PAYMENT_CALLBACK_INITIATED,
            $paymentEntity,
            null,
            [
                'metadata' => [ 'payment' => array_merge([ 'id' => $paymentEntity->getPublicId() ], $properties) ],
                'read_key'  => array('payment.id'),
                'write_key' => 'payment.id',
                'ketan'     => 'ketan'
            ],
            $properties);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'request' => $input['gateway'],
                'gateway' => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'pickedup_data' => $serverData,
            ]);

        return $this->getCallbackResponseData($input);
    }

    protected function getAuthContent($input)
    {
        $amount = $input['payment']['amount'] / 100;

        $content = array(
            'merchantInfo' => array(
                'merId'                 => $input['terminal']['gateway_merchant_id'],
                'merAppId'              => $this->getMerchantAppId($input),
                'merCountryCode'        => 'IN',
                'merName'               => 'RazorPay',
            ),
            'transactionInfo'   => array(
                'txnAmount'             => $input['payment']['amount'],
                'txnCurrency'           => '356',
                'txnDesc'               => 'Transaction for amount: ' . $amount,
                'merTxnId'              => $input['payment']['id'],
                'merAppData'            => '',
                'supportedPaymentType'  => ['*'],
            ),
            'customerInfo' => array(
                'custEmail'             => $input['payment']['email'],
                'custMobile'            => $input['payment']['contact'],
            ),
        );

        $this->addMerchantDetailsInTest($content);

        return $content;
    }

    protected function isRefundSuccessful(array $content, array $input) : bool
    {
        if ((isset($content[ResponseFields::MERCHANT_REF_NO]) === true) and
            ($input['refund']['id'] === $content[ResponseFields::MERCHANT_REF_NO]))
        {
            if (ResponseCode::$statusCodes[$content[ResponseFields::STATUS]] === 'Success')
            {
                return true;
            }

            if (ResponseCode::$statusCodes[$content[ResponseFields::STATUS]] === 'Failed')
            {
                $errorCode   = $content[ResponseFields::ERROR_CODE] ?? '';
                $errorDetail = $content[ResponseFields::ERROR_DETAIL] ?? '';

                if (($errorCode === '10024') and ($errorDetail === 'Void has already been done'))
                {
                    return true;
                }
            }
        }

        return false;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $this->setDomainType();

        $request = $this->getRefundRequestContent($input);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, [
            'gateway'    => $this->gateway,
            'payment_id' => $input['payment']['id'],
            'refund_id'  => $input['refund']['id'],
            'request'    => $request
        ]);

        $response = $this->postRequest($request)['content'];

        $content =  [];

        parse_str($response, $content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, [$request, $content]);

        $refundAttributes = $this->getRefundEntityAttributesFromRefundResponse($input, $content);

        $this->createGatewayRefundEntity($refundAttributes);

        $gatewayDataArray = [
            PaymentGateway::GATEWAY_RESPONSE => json_encode($response),
            PaymentGateway::GATEWAY_KEYS     => $this->getGatewayData($content),
        ];

        if ($this->isRefundSuccessful($content, $input) === true)
        {
            return $gatewayDataArray;
        }

        $this->trace->error(TraceCode::PAYMENT_REFUND_FAILURE, [$request, $content]);

        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_REFUND_FAILED,
            $content[ResponseFields::ERROR_CODE],
            $content[ResponseFields::ERROR_DETAIL],
            $gatewayDataArray
        );
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $this->setDomainType();

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function verifyGateway(array $input)
    {
        parent::verify($input);

        $this->setDomainType();

        $verify = new Verify($this->gateway, $input);

        $verify = $this->sendPaymentVerifyRequestGateway($verify);

        return $verify->getDataToTrace();
    }

    protected function sendPaymentVerifyRequestGateway($verify)
    {
        $input = $verify->input;

        $this->perform  = 'verify';

        $responseContent = '';

        $response = '';

        $txnStatusResults = [];

        $verifyStates = TransactionType::$codes;

        // Don't Check for settle during Verify
        unset($verifyStates['SETTLE']);

        // Since payzapp does not provide state of the payment with payment result api,
        // We will have to check for status of all possible states
        foreach ($verifyStates as $txnType => $txnTypeCode)
        {
            $content =  array(
                'pg_instance_id'                    => $this->config['live_pg_instance_id'],
                'merchant_id'                       => $input['terminal']['gateway_merchant_id2'],
                'perform'                           => $this->performMap[$this->perform],
                'currency_code'                     => '356',
                'transaction_type'                  => $txnTypeCode,
                'amount'                            => $input['payment']['amount'],
                'merchant_reference_no'             => $input['payment']['id'],
            );

            $this->addMerchantDetailsInTest($content);

            $content['message_hash'] = 'CURRENCY:7:'.$this->getHashForVerifyRequest($content);

            $contentLog = $content;

            unset($contentLog['pg_instance_id']);

            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
                [
                    'request'=> $contentLog
                ]);

            $requestResponse = $this->postRequest($content);

            $content = $requestResponse['content'];

            $txnStatus = $this->getTransactionStatusForVerifyFromContent($content);

            //Record the transaction status for sale first and otherlater ones if possible.
            $txnStatusResults[$txnType] = [
                'status'    => $txnStatus,
                'content'   => $content,
                'response'  => $requestResponse['response'],
            ];

            if (($txnType === 'SALE') or
                ($this->isTransactionSuccess($txnStatus)))
            {
                $responseContent = $content;

                $verify->transactionType = $txnType;

                $response = $requestResponse['response'];
            }
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            array('txnStatusResults' => $txnStatusResults));

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $responseContent;
        $verify->verifystatusResults = $txnStatusResults;

        return $verify;
    }

    public function capture(array $input)
    {
        parent::capture($input);
    }

    protected function pickupData($input)
    {
        $content = array(
            'wibmoTxnId'        => $input['gateway']['wibmoTxnId'],
            'dataPickupCode'    => $input['gateway']['dataPickUpCode'],
            'merTxnId'          => $input['payment']['id'],
            'merchantInfo'      => array(
                'merId'                 => $input['terminal']['gateway_merchant_id'],
                'merAppId'              => $input['terminal']['gateway_terminal_id'],
                'merCountryCode'        => 'IN',
            )
        );

        $this->addMerchantDetailsInTest($content);

        $content['msgHash'] = $this->getHashForDataPickupRequest($content);

        $content = $this->makePickUpDataRequest($content);

        return $content;
    }

    protected function getRefundRequestContent($input)
    {
        $wallet = $this->repo->fetchWalletByPaymentId($input['payment']['id']);

        $originalTransactionId = $wallet['gateway_payment_id_2'];

        $perform = $this->getPerformForPayment($input['payment']);

        $content =  array(
            'pg_instance_id'                    => $this->config['live_pg_instance_id'],
            'merchant_id'                       => $input['terminal']['gateway_merchant_id2'],
            'perform'                           => $perform,
            'orginal_transaction_id'            => $originalTransactionId,
            'original_merchant_reference_no'    => $input['payment']['id'],
            'new_merchant_reference_no'         => $input['refund']['id'],
            'login_id'                          => $this->config['pg_merchant_login_id'],
            'pgName'                            => $this->pgname,
            'amount'                            => $input['refund']['amount']
        );

        $this->addMerchantDetailsInTest($content);

        $content['message_hash'] = 'MERCHANT-API-HTTPS:7:'.$this->getHashForRefundRequest($content);

        return $content;
    }

    protected function makePickUpDataRequest($content)
    {
        return $this->postRequest($content, 'pickup_data')['content'];
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        //For some payments the rescode is not present in the response. this should not happen
        if (isset($input['resCode']) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CALLBACK_EMPTY_INPUT,
                null,
                null,
                ['response' => $input]);
        }

        $resCode = (int) $input['resCode'];

        if ($resCode === 0)
        {
            return;
        }

        //trace input
        $this->trace->error(
            TraceCode::PAYMENT_CALLBACK_FAILURE,
            [$input]);

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $input['resCode'],
                $input['resDesc'] ?? '');
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;

        $status = VerifyResult::STATUS_MATCH;

        $txnStatus = $this->getTransactionStatusForVerifyFromContent($content);

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            array('txnStatus' => $txnStatus));

        if ($this->isTransactionSuccess($txnStatus))
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        // If payment status is either failed or created,
        // this is an api failure
        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $responseDescription = $this->getResponseDescription($txnStatus);

        $postVerifyAttributes = array(
            'response_code'         => $txnStatus['pg_error_code'],
            'response_description'  => $responseDescription,
            'status_code'           => $txnStatus['status'],
            'error_message'         => $responseDescription,
        );

        $gateway_payment_id_2 = $verify->verifystatusResults['SALE']['status']['transaction_id'];

        $payment->fill(['gateway_payment_id_2' => $gateway_payment_id_2 ]);

        $payment->saveOrFail();

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            array('postVerifyAttributes' => $postVerifyAttributes));

        $verify->verifyResponseContent = $postVerifyAttributes;

        return $status;
    }

    public function verifyRefund(array $input)
    {
        $scroogeResponse = new ScroogeResponse();

        return $scroogeResponse->setSuccess(false)
                               ->setStatusCode(ErrorCode::GATEWAY_ERROR_VERIFY_REFUND_NOT_SUPPORTED)
                               ->toArray();
    }

    protected function getRefundEntityAttributesFromRefundResponse($input, $content)
    {
        $refundAttributes = array(
            'payment_id'            => $input['payment']['id'],
            'action'                => $this->action,
            'amount'                => $input['refund']['amount'],
            'wallet'                => $input['payment']['wallet'],
            'email'                 => $input['payment']['email'],
            'received'              => 1,
            'contact'               => $input['payment']['contact'],
            'gateway_merchant_id'   => $input['terminal']['gateway_merchant_id2'],
            'refund_id'             => $input['refund']['id'],
            'response_code'         => $content['pg_error_code'],
            'response_description'  => $content['pg_error_detail'],
            'status_code'           => $content['status'],
            'error_message'         => $content['pg_error_detail'],
        );

        if (isset($content['new_transaction_id']))
        {
            $refundAttributes['gateway_payment_id_2'] =  $content['new_transaction_id'];
            $refundAttributes['gateway_refund_id']    =  $content['new_transaction_id'];
        }

        if (isset($content['rrn']))
        {
            $refundAttributes['reference1'] =  $content['rrn'];
        }

        return $refundAttributes;
    }

    protected function getTransactionStatusForVerifyFromContent($content)
    {
        $txnResultStrings = explode('transaction_id=', $content);

        $originalTxnIdRecord = end($txnResultStrings);

        $originalTxnIdRecord = 'transaction_id='.$originalTxnIdRecord;

        parse_str($originalTxnIdRecord, $txnStatus);

        return $txnStatus;
    }

    protected function getActionFromTransactionType($transactionType)
    {
        $map = [
            'SALE'      => 'authorized',
            'VOID'      => 'refunded',
            'REFUND'    => 'refunded',
        ];

        return $map[$transactionType];
    }

    protected function getResponseDescription($txnStatus)
    {
        // In test api Payzapp returns pg_error_detail
        if (isset($txnStatus['pg_error_detail']))
        {
            return $txnStatus['pg_error_detail'];
        }
        // In beta api Payzapp returns pg_error_msg
        else if (isset($txnStatus['pg_error_msg']))
        {
            return $txnStatus['pg_error_msg'];
        }
        // Because Payzapp
        else
        {
            return '';
        }
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $this->perform  = 'verify';

        $responseContent = '';

        $response = '';

        $txnStatusResults = [];

        $verifyStates = TransactionType::$codes;

        // Don't Check for settle during Verify
        unset($verifyStates['SETTLE']);

        // Since payzapp does not provide state of the payment with payment result api,
        // We will have to check for status of all possible states
        foreach ($verifyStates as $txnType => $txnTypeCode)
        {
            $content =  array(
                'pg_instance_id'                    => $this->config['live_pg_instance_id'],
                'merchant_id'                       => $input['terminal']['gateway_merchant_id2'],
                'perform'                           => $this->performMap[$this->perform],
                'currency_code'                     => '356',
                'transaction_type'                  => $txnTypeCode,
                'amount'                            => $input['payment']['amount'],
                'merchant_reference_no'             => $input['payment']['id'],
            );

            $this->addMerchantDetailsInTest($content);

            $content['message_hash'] = 'CURRENCY:7:'.$this->getHashForVerifyRequest($content);

            $contentLog = $content;

            unset($contentLog['pg_instance_id']);

            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
                [
                    'request'=> $contentLog
                ]);

            $requestResponse = $this->postRequest($content);

            $content = $requestResponse['content'];

            $txnStatus = $this->getTransactionStatusForVerifyFromContent($content);

            //Record the transaction status for sale first and otherlater ones if possible.
            $txnStatusResults[$txnType] = [
                'status'    => $txnStatus,
                'content'   => $content,
                'response'  => $requestResponse['response'],
            ];

            if (($txnType === 'SALE') or
                ($this->isTransactionSuccess($txnStatus)))
            {
                $responseContent = $content;

                $verify->transactionType = $txnType;

                $response = $requestResponse['response'];
            }
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            array('txnStatusResults' => $txnStatusResults));

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $responseContent;
        $verify->verifystatusResults = $txnStatusResults;

        return $content;
    }

    protected function isTransactionSuccess($txnStatus)
    {
        return ((empty($txnStatus['status']) === false) and
                (ResponseCode::$statusCodes[$txnStatus['status']] === 'Success'));
    }

    protected function postRequest($content, $type = null)
    {
        $url = $this->getUrl($type);

        if (in_array($this->action, $this->acosaActions))
        {
            $request = array(
                'method'  => 'post',
                'url'     => 'https://' . $url,
                'content' => $content,
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']);

            $response = $this->sendGatewayRequest($request);

            $content = $response->body;
        }
        else
        {
            $request = [
                'method'  => 'post',
                'url'     => 'https://' . $url,
                'content' => json_encode($content),
                'headers' => ['Content-Type' => 'application/json']
            ];

            $response = $this->sendGatewayRequest($request);

            $content = json_decode($response->body, true);
        }

        return [
            'response' => $response,
            'content'  => $content
        ];
    }

    protected function setDomainType()
    {
        $domainTypePrefix = 'acosa_';

        if (in_array($this->action, $this->acosaActions))
        {
            $this->domainType = $domainTypePrefix . $this->mode;
        }
    }

    protected function getTestSecret()
    {
        $secret = parent::getTestSecret();

        if ($this->domainType !== null)
        {
            return $this->config['test_pg_hash_key'];
        }

        return $secret;
    }

    protected function getLiveSecret()
    {
        $secret = parent::getLiveSecret();

        if ($this->domainType !== null)
        {
            return $this->input['terminal']['gateway_terminal_password'];
        }

        return $secret;
    }

    protected function verifyGatewaySecureHash(array $input, $payment)
    {
        $generated = $this->generateCallbackSecureHash($input, $payment);

        if (isset($input['gateway']['msgHash']))
        {
            $actual = $input['gateway']['msgHash'];
        }
        else
        {
            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_CALLBACK,
                ['request' => $input['gateway']]);

            // Error description given by Wibmo
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED
            );
        }

        $this->compareHashes($actual, $generated);
    }

    protected function generateCallbackSecureHash($input, $payment)
    {
        $content = array_merge($payment, $input['gateway']);

        $content['merAppId'] = $this->getMerchantAppId($input);
        $content['merAppData'] = '';
        $content['txnCurrency'] = '356';

        return $this->getHashForAuthorizeResponse($content);
    }

    /**
     * PayZapp transactions are processed
     * at midnight : till then the void api could be used
     *              else use the refund api.
     *
     * @param payment $payment
     * @param boolean $forceRefund
     * @return string
     */
    protected function getPerformForPayment($payment, $forceRefund = false)
    {
        return $this->performMap['voidOrRefund'];
    }

    protected function getHashForDataPickupRequest($content)
    {
        $fieldsInOrder = array(
            'wpay',
            'merId',
            'merAppId',
            'merTxnId',
            'wibmoTxnId',
            'dataPickupCode',
        );

        $content['wpay'] = 'wpay';

        $content = array_merge($content, $content['merchantInfo']);

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        $hash = $this->getHashOfArray($orderedData);

        return $hash;
    }

    protected function getHashForAuthorizeRequest($content)
    {
        $fieldsInOrder = array(
            'wpay',
            'merId',
            'merAppId',
            'merTxnId',
            'merAppData',
            'txnAmount',
            'txnCurrency',
            'supportedPaymentType',
            'merName');

        $content['wpay'] = 'wpay';

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    public function getHashForAuthorizeResponse($content)
    {
        $fieldsInOrder = array(
            'wpay',
            'merId',
            'merAppId',
            'merTxnId',
            'merAppData',
            'txnAmount',
            'txnCurrency',
            'wibmoTxnId',
            'resCode',
            'dataPickUpCode'
        );

        $content['wpay'] = 'wpay';

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForRefundRequest(array $content)
    {
        $fieldsInOrder = array(
            'pg_instance_id',
            'merchant_id',
            'perform',
            'orginal_transaction_id',
            'original_merchant_reference_no',
            'login_id',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForVerifyRequest(array $content)
    {
        $fieldsInOrder = array(
            'pg_instance_id',
            'merchant_id',
            'perform',
            'currency_code',
            'amount',
            'merchant_reference_no',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function addMerchantDetailsInTest(array & $content)
    {
        if ($this->mode === Mode::LIVE)
        {
            return;
        }

        if (in_array($this->action, $this->acosaActions))
        {
            $content['merchant_id'] = $this->config['test_pg_merchant_id'];
            $content['pg_instance_id'] = $this->config['test_pg_instance_id'];
        }
        else
        {
            $content['merchantInfo']['merId'] = $this->config['test_merchant_id'];
            $content['merchantInfo']['merAppId'] = $this->config['test_merchant_app_id'];
        }
    }

    protected function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, '|');

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        $str = $str . '|'.$secret.'|';

        if ($this->domainType !== null)
        {
            $hash =  base64_encode(sha1($str, true)); // nosemgrep :  php.lang.security.weak-crypto.weak-crypto
        }
        else
        {
            $hash =  base64_encode(hash(HashAlgo::SHA256, $str, true));
        }

        return $hash;
    }

    protected function getMerchantAppId($input)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_app_id'];
        }

        return $input['terminal']['gateway_access_code'];
    }

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                ResponseFields::RRN             => $response[ResponseFields::RRN] ?? null,
                ResponseFields::STATUS          => $response[ResponseFields::STATUS] ?? null,
                ResponseFields::ERROR_CODE      => $response[ResponseFields::ERROR_CODE] ?? null,
                ResponseFields::ERROR_DETAIL    => $response[ResponseFields::ERROR_DETAIL] ?? null,
                ResponseFields::TRANSACTION_ID  => $response[ResponseFields::TRANSACTION_ID] ?? null,
                ResponseFields::MERCHANT_REF_NO => $response[ResponseFields::MERCHANT_REF_NO] ?? null,
            ];
        }

        return [];
    }
}
