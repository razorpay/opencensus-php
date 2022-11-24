<?php

namespace RZP\Gateway\Paysecure;


use View;
use Cache;

use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends Base\Gateway
{
    use Base\CardCacheTrait;
    use RequestHandlerTrait;
    use Base\AuthorizeFailed;

    protected $gateway = 'paysecure';

    const CACHE_KEY = 'paysecure_%s_card_details';

    const MIGRATION_TIMESTAMP = 1575912600;

    protected $secureCacheDriver;

    const GATEWAY_PAYSECURE_STAN = 'gateway_paysecure_stan';

    protected $gatewayPayment = null;

    protected $wsdlDetails = [];

    protected $map = [
        Fields::ERROR_CODE          => Entity::ERROR_CODE,
        Fields::ERROR_MESSAGE       => Entity::ERROR_MESSAGE,
        Fields::STATUS              => Entity::STATUS,
        Fields::APPRCODE            => Entity::APPRCODE,
        Fields::TRAN_ID             => Entity::GATEWAY_TRANSACTION_ID,
        Entity::FLOW                => Entity::FLOW,
        Fields::ACCU_RESPONSE_CODE  => Entity::ERROR_CODE,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->wsdlDetails =  [
            'header' => [
                'namespace' => 'https://paysecure/merchant.soap.header/',
                'key'       => 'RequestorCredentials',
            ],
            'body' => [
                'namespace' => 'https://paysecure/merchant.soap/',
                'key'       => 'CallPaySecure',
            ],
        ];
    }

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        if ($input['payment']['created_at'] > self::MIGRATION_TIMESTAMP)
        {
            $this->wsdlDetails['wsdl_file'] = dirname(__FILE__) . '/rupay_new.wsdl';
        }
        else
        {
            $this->wsdlDetails['wsdl_file'] = dirname(__FILE__) . '/rupay.wsdl';
        }

        $this->secureCacheDriver = $this->getDriver($input);
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\GatewayErrorException
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $checkBin2Response = $this->checkBin2();

        $this->handleFailure($checkBin2Response, 'checkbin2');

        //For Rupay transactions, store it ONLY if we're not storing card in vault
        if (empty($input['card']['vault_token']) === true)
        {
            $this->persistCardDetailsTemporarily($input, false);
        }

        // Redirect flow
        if ($checkBin2Response[Fields::IMPLEMENTS_REDIRECT] === Constants::VALUE_TRUE)
        {
            list($gatewayPayment, $response) = $this->initiate2();

            $this->updateGatewayPaymentFromInitiate2Response($gatewayPayment, $response);

            $this->handleFailure($response, 'initiate2');

            $request = $this->getRedirectRequest($response);

            $this->traceGatewayPaymentRequest($request, $input);
        }
        // Iframe flow
        else
        {
            list($gatewayPayment, $response) = $this->initiate();

            $this->updateGatewayPaymentEntity($gatewayPayment, $response);

            $this->handleFailure($response, 'initiate');

            $request = [
                'method' => 'direct',
            ];

            $this->traceGatewayPaymentRequest($request, $input);

            $request['content'] = View::make('gateway.paysecurePinpadForm')
                                      ->with('data', $this->getPinpadData($response))
                                      ->render();
        }

        return $request;
    }

    /**
     * @param array $input
     * @return array|null
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function callback(array $input)
    {
        parent::callback($input);

        if ((isset($input['gateway'][Fields::SESSION]) === true) and
            ($input['payment']['id'] !== $input['gateway'][Fields::SESSION]))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_DATA_MISMATCH,
                null,
                null,
                [
                    'response'   => $input['gateway'],
                    'gateway'    => $this->gateway,
                    'payment_id' => $input['payment']['id'],
                ]
            );
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionGetLastOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $input['gateway']);

        // Check payment status
        if (in_array($input['gateway'][Fields::ACCU_RESPONSE_CODE],
                [StatusCode::CALLBACK_SUCCESS, StatusCode::IFRAME_CALLBACK_SUCCESS]) === false)
        {
            $traceData = [
                'gateway'    => $this->gateway,
                'response'   => $input['gateway'],
                'payment_id' => $input['payment']['id'],
            ];

            $internalErrorCode = ErrorCodes\ErrorCodes::getInternalErrorCode($input['gateway']);

            throw new Exception\GatewayErrorException(
                $internalErrorCode,
                $input['gateway'][Fields::ACCU_RESPONSE_CODE],
                ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($input['gateway']),
                $traceData,
                null,
                Action::AUTHENTICATE
            );
        }

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_PROCESSED,
            $input);


        // Guid would be sent back only for the redirect flow and not for the iframe flow
        if (isset($input['gateway'][Fields::ACCU_GUID]) === true)
        {
            // Validates the request by checking hash
            $this->validateRequestId($gatewayPayment);
        }

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHORIZATION_INITIATED,
            $input);

        $response = $this->authorizeTransaction($gatewayPayment);

        $attributes = $this->getMappedAttributes($response);

        $attributes[Entity::RECEIVED] = 1;

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        if ($response[Fields::STATUS] !== StatusCode::SUCCESS)
        {
            $traceData = [
                'gateway'    => $this->gateway,
                'response'   => $response,
                'payment_id' => $input['payment']['id'],
            ];

            $internalErrorCode = ErrorCodes\ErrorCodes::getInternalErrorCode($response);

            throw new Exception\GatewayErrorException(
                $internalErrorCode,
                $response[Fields::ERROR_CODE],
                $response[Fields::ERROR_MESSAGE] ?? ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($response),
                $traceData
            );
        }

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment->toArray());

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        try
        {
            return $this->runPaymentVerifyFlow($verify);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            // If a terminal mode is purchase, we send the advice message during the processing of callback
            // But, in the case of late authorized payments, this callback would not be processed and hence
            // advice messages would not be sent
            // Hence, in this case(ie, gatewaySuccess is true, but apiSuccess is false), during verify
            // we need to send an advice message separately before throwing the exception to the api side
            $verify = $e->getVerifyObject();

            if (($verify->gatewaySuccess === true) and
                ($verify->apiSuccess === false) and
                ($this->input['terminal']['mode'] === Terminal\Mode::PURCHASE)
            )
            {
                $this->app['gateway']->call(
                    Payment\Gateway::HITACHI,
                    Base\Action::ADVICE,
                    $input,
                    $this->mode);
            }

            throw $e;
        }

    }

    protected function getPaymentToVerify(Verify $verify)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionGetLast(
            $verify->input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $gatewayPayment;

        return $gatewayPayment;
    }

    public function capture(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYSECURE_CAPTURE_INITIATED,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        return $this->app['gateway']->call(
            Payment\Gateway::HITACHI,
            Action::CAPTURE,
            $input,
            $this->mode,
            $this->terminal);
    }

    // Refund is handled by Hitachi as of now
    public function refund(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYSECURE_REFUND_INITIATED,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        return $this->app['gateway']->call(
            Payment\Gateway::HITACHI,
            Action::REFUND,
            $input,
            $this->mode,
            $this->terminal);
    }

    // Since refunds are via hitachi, verification should go via hitachi
    public function verifyRefund(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYSECURE_VERIFY_REFUND_INITIATED,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        return $this->app['gateway']->call(
            Payment\Gateway::HITACHI,
            Action::VERIFY_REFUND,
            $input,
            $this->mode);
    }

    // ------------ Auth request helpers -----------------
    protected function updateGatewayPaymentFromInitiate2Response($gatewayPayment, $response)
    {

        $content = $this->getMappedAttributes($response);

        if (isset($response[Fields::REDIRECT_URL]) === true)
        {
            $redirectUrl = $response[Fields::REDIRECT_URL];

            $parsed = parse_url($redirectUrl);

            if (isset($parsed['query']) === true && $parsed['query'] !== "" )
            {
                parse_str($parsed['query'], $parsed);

                $hkey = $parsed[Fields::ACCU_HKEY];

                $content[Entity::HKEY] = $hkey;
            }
        }

        $this->updateGatewayPaymentEntity($gatewayPayment, $content, false);
    }

    protected function getRedirectRequest($response)
    {
        $redirectUrl = $response[Fields::REDIRECT_URL];

        $parsed = parse_url($redirectUrl);

        parse_str($parsed['query'], $parsed);

        $hkey = $parsed[Fields::ACCU_HKEY];

        $cardholderId = $parsed[ Fields::ACCU_CARDHOLDER_ID ];
        $guid         = $parsed[ Fields::ACCU_GUID ];
        $redirectUrl  = strtok($redirectUrl, '?');
        $session      = $this->input['payment']['id'];

        $dataToHash = [
            $response[Fields::TRAN_ID],
            $cardholderId,
            $guid,
            $session,
        ];

        $hash = $this->generateHashOfData($dataToHash, $hkey);

        $hash = base64_encode($hash);

        $requestContent = [
            Fields::ACCU_CARDHOLDER_ID => $cardholderId,
            Fields::ACCU_GUID          => $guid,
            Fields::ACCU_RETURN_URL    => $this->input['callbackUrl'],
            Fields::SESSION            => $session,
            Fields::ACCU_REQUEST_ID    => $hash,
        ];

        $redirectArray = [
            'url'     => $redirectUrl,
            'method'  => 'post',
            'content' => $requestContent,
        ];

        return $redirectArray;
    }

    protected function getPinpadData($response)
    {
        $cardNumber = $this->input['card']['number'];

        $length = strlen($cardNumber);

        $lastFourDigits = substr($cardNumber, ($length - 4), $length);

        return [
            'merchantJsScript' => $this->getJsFile(),
            'guid'             => $response[ Fields::GUID ],
            'modulus'          => $response[ Fields::MODULUS ],
            'exponent'         => $response[ Fields::EXPONENT ],
            'lastFourDigits'   => $lastFourDigits,
            'callbackUrl'      => $this->input['callbackUrl'],
        ];
    }

    protected function getJsFile()
    {
        if ($this->mode === Mode::TEST)
        {
            return 'https://cert.mwsrec.npci.org.in/MWS/Scripts/MerchantScript_v1.0.js';
        }

        return 'https://mwsrec.npci.org.in/MWS/Scripts/MerchantScript_v1.0.js';
    }
    // ------------ Auth request helpers end -----------------

    // ------------ Callback request helpers -----------------
    /**
     * @param Entity $gatewayPayment
     * @throws Exception\RuntimeException
     * @throws Exception\GatewayErrorException
     */
    protected function validateRequestId(Entity $gatewayPayment)
    {
        if (isset($this->input['gateway'][Fields::ACCU_REQUEST_ID]) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_HASH_GENERATION_ERROR,
                null,
                null,
                [
                    'request'    => $this->input['gateway'],
                    'payment_id' => $this->input['payment']['id'],
                    'gateway'    => $this->gateway,
                ]
            );
        }

        $dataToHash = [
            $gatewayPayment[Entity::GATEWAY_TRANSACTION_ID],
            $this->input['gateway'][Fields::ACCU_GUID],
            $this->input['payment']['id'],
            $this->input['gateway'][Fields::ACCU_RESPONSE_CODE],
        ];

        $hash = $this->generateHashOfData($dataToHash, $gatewayPayment[Entity::HKEY]);

        $hash = base64_encode($hash);

        $this->compareHashes($this->input['gateway'][Fields::ACCU_REQUEST_ID], $hash);
    }
    // ------------ Callback request helpers end -------------

    // ------------ Verify request helpers -------------------
    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        if (empty($verify->payment[Entity::GATEWAY_TRANSACTION_ID]) === true)
        {
            // Ideally verify fail cron wont pick up paysecure payments, because gateway error exception
            // is thrown with action "authenticate". But, in case some error happens before the gateway error is thrown
            // these payments would be moved to verify failed bucket.
            // Here, it calls verify and if trans id is not set we send a payment verify exception with action finish
            // so that these payments won't again be picked up for verify.
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify,
                Payment\Verify\Action::FINISH);
        }

        $response = $this->transactionStatus($verify->payment);

        $verify->setVerifyResponseContent($response);

        $this->traceGatewayPaymentResponse(
            $response,
            $input,
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);
    }

    protected function verifyPayment(Base\Verify $verify)
    {
        $verify->status = $this->getVerifyMatchStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContentIfNeeded($verify);
    }

    protected function getVerifyMatchStatus(Base\Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkGatewaySuccess(Base\Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[Fields::HISTORY][Fields::TRANSACTION][Fields::STATUS]) === true) and
            ($content[Fields::HISTORY][Fields::TRANSACTION][Fields::STATUS] === StatusCode::TRANSACTION_STATUS_AUTHORIZED))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function saveVerifyContentIfNeeded($verify)
    {
        $gatewayPayment = $verify->payment;

        $response = $verify->verifyResponseContent;

        $attributes = [];

        // Sample response content:
        // {
        //
        //    "status":"success",
        //    "errorcode":"00",
        //    "errormsg":"",
        //    "history":
        //    {
        //        "transaction":
        //        {
        //            "status":"I",
        //            "tran_id":"200000000000000000000999999999",
        //            "apprcode":"",
        //            "datetime":"09/17/2019 21:02:28",
        //            "amount":"100"
        //        }
        //    }
        //
        //}

        // If gateway payment does not contain apprcode and if apprcode
        // is present in verify response, update it.
        if ((empty($gatewayPayment[Entity::APPRCODE]) === true) and
            (empty($response[Fields::HISTORY][Fields::TRANSACTION][Fields::APPRCODE]) === false)
        )
        {
            $attributes[Entity::APPRCODE] = $response[Fields::HISTORY][Fields::TRANSACTION][Fields::APPRCODE];
        }

        if (empty($response[Fields::HISTORY][Fields::TRANSACTION][Fields::STATUS]) === false)
        {
            $attributes[Entity::STATUS] = $response[Fields::HISTORY][Fields::TRANSACTION][Fields::STATUS];
        }

        if (empty($attributes) === false)
        {
            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);
        }

        return $gatewayPayment;
    }
    // ------------ Verify request helpers end ---------------

    // ------------ General helpers --------------------------
    protected function getSoapClientObject($request)
    {
        $soapClient = new SoapClient($request['wsdl'], $request['options']);

        $headers = $this->getRequestHeaders();

        $soapClient->__setLocation($this->getUrl());

        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }

    protected function createGatewayPaymentEntity(array $content, $flow = 'redirect')
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->fill($content);

        $gatewayPayment->setFlow($flow);

        $gatewayPayment->setPaymentId($this->input['payment']['id']);

        $gatewayPayment->setAction($this->action);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function generateHashOfData($dataToHash, $key)
    {
        $str = implode('&', $dataToHash);

        return hash_hmac(HashAlgo::SHA256, $str, $key);
    }

    /**
     * @param $response
     * @param $action
     * @throws Exception\GatewayErrorException
     */
    protected function handleFailure($response, $action)
    {
        if ($response[Fields::STATUS] !== StatusCode::SUCCESS)
        {
            $errorCode = ErrorCodes\ErrorCodes::getInternalErrorCode($response);

            $safeRetry = true;

            if (($errorCode === ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE) and
                ($response[Fields::ERROR_CODE] === ErrorCodes\ErrorCodes::EC_412))
            {
                $safeRetry = false;

                $errorCode = ErrorCode::GATEWAY_ERROR_CARD_NOT_ENROLLED;
            }

            // If the request fails in any of the s2s requests with error code
            // we should not add these payments in verify cron, since the transaction
            // status api only works
            throw new Exception\GatewayErrorException(
                $errorCode,
                $response[Fields::ERROR_CODE],
                $response[Fields::ERROR_MESSAGE] ?? ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($response),
                [
                    'gateway'    => $this->gateway,
                    'payment_id' => $this->input['payment']['id'],
                    'command'    => $action,
                ],
                null,
                Action::AUTHENTICATE,
                $safeRetry);
        }
    }

    protected function callRefundGateway(array $input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionGetLastOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $input['paysecure'] = $gatewayPayment->toArray();

        return $this->app['gateway']->call(
            Payment\Gateway::HITACHI,
            Action::REFUND,
            $input,
            $this->mode);
    }

    protected function getRepository()
    {
        $gateway = $this->gateway;

        return $this->app['repo']->$gateway;
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        if (isset($request['command']) === true)
        {
            if ($request['command'] === Command::INITIATE or $request['command'] === Command::INITIATE_2)
            {
                $toRemove = [
                    'card_no',
                    'card_exp_date',
                    'cvd2',
                ];

                foreach ($toRemove as $field)
                {
                    unset($request['parameters'][$field]);
                }
            }

            unset($request['parameters']['partner_id']);
            unset($request['parameters']['merchant_password']);
        }

        $this->trace->info(
            $traceCode,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);
    }

    protected function getUrl($type = null)
    {
        $input = $this->input;

        $urlClass = $this->getGatewayNamespace() . '\Url';

        if (($input['payment']['created_at'] > self::MIGRATION_TIMESTAMP) and
            (strtoupper($this->mode) === 'LIVE'))
        {
            return constant($urlClass . '::' . 'NEW_' .strtoupper($this->mode));
        }

        return constant($urlClass . '::' .strtoupper($this->mode));
    }

    protected function getAcquirerData($input, $gatewayPayment)
    {
        $acquirer['acquirer'] = [
            Payment\Entity::REFERENCE2 => $gatewayPayment[Entity::APPRCODE],
        ];

        return $acquirer;
    }

    protected function getCacheKey($input)
    {
        return sprintf(self::CACHE_KEY, $input['payment']['id']);
    }

    protected function getCardCacheTtl($input)
    {
        return 60 * 24 * 10;
    }
}
