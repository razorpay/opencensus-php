<?php

namespace RZP\Gateway\Paysecure;

use View;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends Base\Gateway
{
    use Base\CardCacheTrait;
    use RequestHandlerTrait;
    use Base\AuthorizeFailed;

    protected $gateway = 'paysecure';

    protected $secureCacheDriver;

    const CACHE_KEY = 'paysecure_%s_card_details';
    const CACHE_TTL = 0;

    protected $gatewayPayment = null;

    protected $map = [
        Fields::ERROR_CODE    => Entity::ERROR_CODE,
        Fields::ERROR_MESSAGE => Entity::ERROR_MESSAGE,
        Fields::STATUS        => Entity::STATUS,
        Fields::APPRCODE      => Entity::APPRCODE,
        Fields::TRAN_ID       => Entity::GATEWAY_TRANSACTION_ID,
        Entity::FLOW          => Entity::FLOW,
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

        $this->wsdlDetails['wsdl_file'] = dirname(__FILE__) . '/rupay.wsdl.test';
    }

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

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

        // Redirect flow
        if ($checkBin2Response[Fields::IMPLEMENTS_REDIRECT] === Constants::VALUE_TRUE)
        {
            list($gatewayPayment, $response) = $this->initiate2();

            $this->handleFailure($response, 'initiate2');

            $content = $this->getGatewayPaymentAttributes($response);

            $this->updateGatewayPaymentEntity($gatewayPayment, $content, false);

            $request = $this->getRedirectRequest($response);

            $this->traceGatewayPaymentRequest($request, $input);

            // This will be used in the capture flow, to be passed to Hitachi for advice message call.
            $this->persistCardDetailsTemporarily($input);

            return $request;
        }
        // Iframe flow
        else
        {
            list($gatewayPayment, $response) = $this->initiate();

            $this->handleFailure($response, 'initiate');

            $this->updateGatewayPaymentEntity($gatewayPayment, $response);

            $request = [
                'method' => 'direct',
            ];

            $this->traceGatewayPaymentRequest($request, $input);

            $this->persistCardDetailsTemporarily($input);

            $request['content'] = View::make('gateway.paysecurePinpadForm')
                                      ->with('data', $this->getPinpadData($response))
                                      ->render();

            return $request;
        }
    }

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

        // Check payment status
        if (in_array($input['gateway'][Fields::ACCU_RESPONSE_CODE], [StatusCode::CALLBACK_SUCCESS, StatusCode::IFRAME_CALLBACK_SUCCESS]) === false)
        {
            $traceData = [
                'gateway'    => $this->gateway,
                'response'   => $input['gateway'],
                'payment_id' => $input['payment']['id'],
            ];

            $internalErrorCode = ErrorCodes::getErrorCodeMapped($input['gateway'][Fields::ACCU_RESPONSE_CODE]);

            throw new Exception\GatewayErrorException(
                $internalErrorCode,
                $input['gateway'][Fields::ACCU_RESPONSE_CODE],
                ErrorCodes::getErrorDescription($input['gateway'][Fields::ACCU_RESPONSE_CODE]),
                $traceData
            );
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        // Guid would be sent back only for the redirect flow and not for the iframe flow
        if (isset($input['gateway'][Fields::ACCU_GUID]) === true)
        {
            // Validates the request by checking hash
            $this->validateRequestId($gatewayPayment);
        }

        $response = $this->authorizeTransaction($gatewayPayment);

        if ($response[Fields::STATUS] !== StatusCode::SUCCESS)
        {
            $traceData = [
                'gateway'    => $this->gateway,
                'response'   => $response,
                'payment_id' => $input['payment']['id'],
            ];

            $internalErrorCode = ErrorCodes::getErrorCodeMapped($response[Fields::ERROR_CODE]);

            throw new Exception\GatewayErrorException(
                $internalErrorCode,
                $response[Fields::ERROR_CODE],
                $response[Fields::ERROR_MESSAGE],
                $traceData
            );
        }

        $attributes = $this->getMappedAttributes($response);

        $attributes[Entity::RECEIVED] = 1;

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $this->setCardNumberAndCvv($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $input['paysecure'] = $gatewayPayment->toArray();

        $this->callAdviceGateway($input);

        $gatewayPayment->fill(
            [
                Entity::SETTLED => 1,
            ]
        );

        $this->getRepository()->saveOrFail($gatewayPayment);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        return $this->callRefundGateway($input);
    }

    // ------------ Auth request helpers -----------------
    protected function getGatewayPaymentAttributes($response, $flow = 'redirect')
    {
        $redirectUrl = $response[Fields::REDIRECT_URL];

        $parsed = parse_url($redirectUrl);

        parse_str($parsed['query'], $parsed);

        $hkey = $parsed[Fields::ACCU_HKEY];

        $content = [
            Entity::GATEWAY_TRANSACTION_ID => $response[Fields::TRAN_ID ],
            Entity::HKEY                   => $hkey,
            Entity::FLOW                   => $flow,
        ];

        return $content;
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

    // @codingStandardsIgnoreStart
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
    // @codingStandardsIgnoreEnd

    protected function saveVerifyContentIfNeeded($verify)
    {
        $gatewayPayment = $verify->payment;

        $response = $verify->verifyResponseContent;

        // If gateway payment does not contain apprcode and if apprcode
        // is present in verify response, update it.
        if ((empty($gatewayPayment[Entity::APPRCODE]) === true) and
            (empty($response[Fields::HISTORY][Fields::TRANSACTION][Fields::APPRCODE]) === false)
        )
        {
            $attributes = [
                Entity::APPRCODE => $response[Fields::HISTORY][Fields::TRANSACTION][Fields::APPRCODE]
            ];

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
            $errorCode = ErrorCodes::getErrorCodeMapped($response[Fields::ERROR_CODE]);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response[Fields::ERROR_CODE],
                $response[Fields::ERROR_MESSAGE],
                [
                    'gateway'    => $this->gateway,
                    'payment_id' => $this->input['payment']['id'],
                    'command'    => $action,
                ]
            );
        }
    }

    protected function callAdviceGateway(array $input)
    {
        $this->app['gateway']->call(
            Payment\Gateway::HITACHI,
            Action::ADVICE,
            $input,
            $this->mode);
    }

    protected function callRefundGateway(array $input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
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
                    'retrieval_ref_number',
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
}
