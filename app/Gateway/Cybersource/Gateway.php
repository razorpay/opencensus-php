<?php

namespace RZP\Gateway\Cybersource;

use Cache;
use Crypt;
use Config;
use Requests;
use SoapFault;
use RZP\Error;
use RZP\Exception;
use RZP\Constants;
use RZP\Gateway\Utility;
use RZP\Models\Card;
use RZP\Trace\Trace;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;


class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    const ACCOUNT_NUMBER              = 'accountNumber';
    const AUTHENTICATION_DATA         = 'authenticationData';
    const AUTH_REQUEST_ID             = 'authRequestID';
    const CAPTURE_REQUEST_ID          = 'captureRequestID';
    const CARD                        = 'card';
    const CAVV                        = 'cavv';
    const CC_AUTH_SERVICE             = 'ccAuthService';
    const CC_CAPTURE_SERVICE          = 'ccCaptureService';
    const CC_CREDIT_SERVICE           = 'ccCreditService';
    const COLLECTION_INDICATOR        = 'collectionIndicator';
    const COMMERCE_INDICATOR          = 'commerceIndicator';
    const ECI                         = 'eci';
    const EXPIRATION_MONTH            = 'expirationMonth';
    const EXPIRATION_YEAR             = 'expirationYear';
    const GATEWAY                     = 'gateway';
    const ITEM                        = 'item';
    const PARES_STATUS                = 'paresStatus';
    const PAYER_AUTH_ENROLL_REPLY     = 'payerAuthEnrollReply';
    const PAYER_AUTH_ENROLL_SERVICE   = 'payerAuthEnrollService';
    const PAYER_AUTH_VALIDATE_REPLY   = 'payerAuthValidateReply';
    const PAYER_AUTH_VALIDATE_SERVICE = 'payerAuthValidateService';
    const PA_RES                      = 'PaRes';
    const REASON_CODE                 = 'reasonCode';
    const RECONCILIATION_ID           = 'reconciliationID';
    const REQUEST_ID                  = 'requestID';
    const RUN                         = 'run';
    const SIGNED_PARES                = 'signedPARes';
    const TERMINAL                    = 'terminal';
    const TEST_MERCHANT_ID            = 'test_merchant_id';
    const TEST_MERCHANT_SECRET        = 'test_merchant_secret';
    const TEST_USERNAME               = 'test_username';
    const TEST_PASSWORD               = 'test_password';
    const UCAF                        = 'ucaf';
    const UCAF_AUTHENTICATION_DATA    = 'ucafAuthenticationData';
    const UCAF_COLLECTION_INDICATOR   = 'ucafCollectionIndicator';
    const UNIT_PRICE                  = 'unitPrice';
    const VERES_ENROLLED              = 'veresEnrolled';
    const TEST_WSDL_FILE              = 'cybstest.wsdl.xml';
    const LIVE_WSDL_FILE              = 'cybslive.wsdl.xml';
    const XID                         = 'xid';
    //soap client timeout in seconds
    const CONNECTION_TIMEOUT          = 60;

    protected $gateway = Constants\Table::CYBERSOURCE;

    protected $repo;

    protected $model = null;

    protected $enrollRequest;

    public function __construct()
    {
        parent::__construct();

        $this->secureCache = Config::get('cache.secure_default');
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isRecurringPaymentRequest($input) === true)
        {
            return $this->recurring($input);
        }

        $response = $this->enroll($input);

        return $this->decideAuthStepAfterEnroll($response, $input);
    }

    public function recurring(array $input)
    {
        $response = $this->authorizeRecurring($input);

        $this->persistAfterAuthorizeRecurring($input, $response);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $this->setCardNumberAndCvv($input);

        $gatewayPayment = $this->retrieveByPaymentId($input['payment']['id']);
        $gatewayPayment->fill([Entity::RECEIVED => true]);
        $gatewayPayment->saveOrFail();

        $response = $this->postAuthEnrolledRequest($input);

        $this->postEnrollAuthorize($input);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->repo->retrieveCapturedByPaymentId(
            $input['payment']['id']);

        if (($gatewayPayment !== null) and
            ($gatewayPayment['amount'] === $input['payment']['amount']))
        {
            //
            // Looks like the payment has already been captured on gateway,
            // but due to some previous error, this has not been recorded
            // on api.
            //
            // In this case we will silently return implying payment has
            // been captured on gateway
            //

            return;
        }

        $request = $this->createCaptureRequestFields($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_CAPTURE_REQUEST, $request);

        try
        {
            $response = $this->postRequest($request);

            $this->persistAfterCapture($input,  $response, $request);
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Capture request failed");
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->createRefundRequestFields($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        try
        {
            $response = $this->postRequest($request);

            $this->persistAfterRefund($input, $response);
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Refund request failed");
        }
    }

    protected function isRecurringPaymentRequest($input)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['token']->isRecurring() === true))
        {
            return true;
        }

        return false;
    }

    protected function authorizeRecurring($input)
    {
        $request = $this->createRecurringAuthorizeRequestFields($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_AUTHORIZE_REQUEST, $request);

        return $this->postRequest($request);
    }

    protected function createRecurringAuthorizeRequestFields($input)
    {
        $content = [
            'merchantID' => $this->getMerchantID($input['terminal']),
            'merchantReferenceCode' => $input['payment']['id'],
            'purchaseTotals' => [
                'currency' => $input['payment']['currency'],
                'grandTotalAmount' => ($input['payment']['amount'] / 100)
            ],
            'card' => [
                'accountNumber' => $input['card']['number'],
                'expirationMonth' => $input['card']['expiry_month'],
                'expirationYear' => $input['card']['expiry_year']
            ],
            'ccAuthService' => [
                'run' => 'true',
                'commerceIndicator' => 'recurring'
            ]
        ];

        $this->setBillingInfo($content, $input);

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $request = $this->getPaymentVerifyRequestContent($input, $payment);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $this->setCybersourceCredentials($request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $response->body,
                'gateway' => 'cybersource',
                'payment_id' => $input['payment']['id'],
            ]);

        $this->response = $response;

        $content = $this->xmlToArray($response->body);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyPayment($verify)
    {
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        $authReply = $this->fetchAuthorizeReplyFromContent($content);

        // Payment is failed when ics_auth is not present
        if (isset($authReply['RFlag']) === true)
        {
            if ($authReply['RFlag'] !== ReplyFlag::SOK)
            {
                $this->verifyNonExistentCase($verify);
            }
            else if ($authReply['RFlag'] === ReplyFlag::SOK)
            {
                $this->verifyPaymentReconcileWithGatewayResponse($verify);

                $this->getVerifyContentFromResponse($verify);
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        return $verify->status;
    }

    protected function verifyNonExistentCase($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $verify->gatewaySuccess = false;

        if (($payment === null) and
            (($input['payment']['status'] === 'failed') or
             ($input['payment']['status'] === 'created')))
        {
            $verify->apiSuccess = false;
        }
        else if (($payment['received'] === false) and
                 (($payment['status'] === null) or
                  ($payment['status'] !== (string) Status::AUTHORIZED)))
        {
            $verify->apiSuccess = false;
        }
        else if ($payment['status'] === (string) Status::AUTHORIZED)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = true;
        }
    }

    protected function verifyPaymentReconcileWithGatewayResponse($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

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

    protected function setCardNumberAndCvv(&$input)
    {
        $data = $this->getCardDetailsFromCache($input);

        $input['card']['number'] = Card\Tokenex::getCardNumber($data['vault_token']);

        $input['card']['cvv']    = Crypt::decrypt($data['cvv']);
    }

    protected function enroll($input)
    {
        //TODO: add timeout exception handling here
        $request = $this->getEnrollRequestObject($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_ENROLL_REQUEST, $request);

        try
        {
            $response = $this->postRequest($request);

            $this->persistAfterEnroll($input, $response, $request);

            return $response;
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Enroll: Server Error occured", true);
        }
    }

    protected function postAuthEnrolledRequest($input)
    {
        assertTrue($this->model->getReasonCode() === Result::ENROLLED);

        $request = $this->createAuthEnrolledRequestFields($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_VALIDATE_REQUEST, $request);

        try
        {
            $response = $this->postRequest($request);

            $this->persistAfterValidate($input, $response, $request);

            return $response;
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Post Auth Enroll: Validation Request Failed");
        }
    }

    protected function postEnrollAuthorize($input)
    {
        $request = $this->createAuthorizeRequestFields($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_AUTHORIZE_REQUEST, $request);

        try
        {
            $response = $this->postRequest($request);

            $this->persistAfterAuthorize($input, $response, $request);
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Post Enroll Authorize: Authorization Failed");
        }
    }

    protected function postNotEnrolledAuthorize($input, $enrollResponse)
    {
        $request = $this->createNotEnrolledAuthorizeRequestFields($input, $enrollResponse);

        $this->traceGatewayRequest(TraceCode::GATEWAY_AUTHORIZE_REQUEST, $request);

        try
        {
            $response = $this->postRequest($request);

            $this->persistAfterNotEnrolledAuthorize($input, $response, $request);
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Post Not Enrolled Authorize: Authorization failed");
        }
    }

    protected function persistAfterValidate($input, $response, $request)
    {
        $gateway = $this->retrieveByPaymentId($input['payment']['id']);

        $this->trace->info(TraceCode::GATEWAY_VALIDATE_RESPONSE, $response);

        $payAuthRep = $response[self::PAYER_AUTH_VALIDATE_REPLY];

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $attributes = array(
                Entity::REASON_CODE     => $response['reasonCode'],
                Entity::PARES_STATUS    => (isset($payAuthRep['paresStatus']) ? $payAuthRep['paresStatus'] : null),
                Entity::XID             => (isset($payAuthRep['xid']) ? $payAuthRep['xid'] : null)
            );

            $gateway->fill($attributes);

            $gateway->saveOrFail();

            $this->throwException($response);
        }

        $attributes = array(
            Entity::COMMERCE_INDICATOR => $payAuthRep[self::COMMERCE_INDICATOR],
            Entity::XID                => $payAuthRep[self::XID],
            Entity::PARES_STATUS       => $payAuthRep[self::PARES_STATUS]
        );

        $networkCode = $input['card']['network_code'];

        switch ($networkCode)
        {
            case Card\Network::VISA:
                if (isset($payAuthRep[Entity::ECI]) === false)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                $eci = (int) $payAuthRep[self::ECI];

                if (($eci === 7) or ($eci === 0))
                {
                    $message = 'ECI param value is invalid';

                    if (isset($payAuthRep['authenticationStatusMessage']))
                    {
                        $message = $payAuthRep['authenticationStatusMessage'];
                    }

                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED,
                        $response['reasonCode'],
                        $message);
                }

                $attributes[Entity::ECI] = $payAuthRep[Entity::ECI];
                $attributes[Entity::CAVV] = $payAuthRep[self::CAVV];
                break;

            case Card\Network::MC:
                if (isset($payAuthRep[self::UCAF_COLLECTION_INDICATOR]) === false)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                $colInd = (int) $payAuthRep[self::UCAF_COLLECTION_INDICATOR];

                if(($colInd === 0) or ($colInd === 7))
                {
                    $message = 'UCAF param value is invalid';

                    if (isset($payAuthRep['authenticationStatusMessage']))
                    {
                        $message = $payAuthRep['authenticationStatusMessage'];
                    }

                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED,
                        $response['reasonCode'],
                        $message);
                }

                $attributes[Entity::AUTH_DATA] = $payAuthRep[self::UCAF_AUTHENTICATION_DATA];
                $attributes[Entity::COLLECTION_INDICATOR] = $payAuthRep[self::UCAF_COLLECTION_INDICATOR];
                break;

            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
                break;
        }

        $gateway->fill($attributes);
        $gateway->saveOrFail();
    }

    protected function persistAfterNotEnrolledAuthorize($input, $response, $request)
    {
        $gatewayPayment = $this->retrieveByPaymentId($input['payment']['id']);

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_RESPONSE, $response);

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $attributes = array(
                Entity::STATUS     => Status::AUTHORIZE_FAILED,
                Entity::REASON_CODE => $response['reasonCode']
            );

            $gatewayPayment->fill($attributes);

            $gatewayPayment->saveOrFail();

            $this->throwException($response);
        }

        $attributes = array(
            Entity::REF    => $response[self::REQUEST_ID],
            Entity::STATUS => Status::AUTHORIZED
        );

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();
    }

    protected function persistAfterAuthorize($input, $response, $request)
    {
        $gateway = $this->repo->retrieveByPaymentIdOrFail($input['payment']['id']);

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_RESPONSE, $response);

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $attributes = array(
                Entity::STATUS      => Status::AUTHORIZE_FAILED,
                Entity::REASON_CODE => $response['reasonCode']
            );

            if (isset($response[self::REQUEST_ID]) === true)
            {
                $attributes[Entity::REF] = $response[self::REQUEST_ID];
            }

            $gateway->fill($attributes);

            $gateway->saveOrFail();

            $this->throwException($response);
        }

        $attributes = array(
            Entity::REF    => $response[self::REQUEST_ID],
            Entity::STATUS => Status::AUTHORIZED
        );

        $gateway->fill($attributes);

        $gateway->saveOrFail();
    }

    protected function persistAfterAuthorizeRecurring($input, $response)
    {
        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_RESPONSE, $response);

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $attributes = array(
                Entity::STATUS      => Status::AUTHORIZE_FAILED,
                Entity::REASON_CODE => $response['reasonCode'],
                Entity::AMOUNT      => $input['payment']['amount']
            );

            if (isset($response[self::REQUEST_ID]) === true)
            {
                $attributes[Entity::REF] = $response[self::REQUEST_ID];
            }

            $gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input);

            $this->throwException($response);
        }

        $attributes = [
            Entity::REF         => $response[self::REQUEST_ID],
            Entity::REASON_CODE => $response['reasonCode'],
            Entity::AMOUNT      => $input['payment']['amount'],
            Entity::STATUS      => Status::AUTHORIZED
        ];

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input);
    }

    protected function persistAfterEnroll($input, $response, $request)
    {
        $this->trace->info(TraceCode::GATEWAY_ENROLL_RESPONSE, $response);

        $reasonCode = (int) $response['reasonCode'];

        $attributes = array(
            Entity::AMOUNT        => $input['payment']['amount'],
            Entity::REASON_CODE   => $response['reasonCode'],
            Entity::STATUS        => Status::CREATED,
            Entity::REF           => $response[self::REQUEST_ID]
        );

        $this->createGatewayPaymentEntity($attributes, $input);

        if (($reasonCode !== Result::ENROLLED) and
            ($reasonCode !== Result::SUCCESS))
        {
            $this->throwException($response);
        }
    }

    protected function persistAfterCapture($input, $response, $request)
    {
        $this->trace->info(TraceCode::GATEWAY_CAPTURE_RESPONSE, $response);

        $status = Status::CAPTURED;
        $reasonCode = (int) $response['reasonCode'];

        if ($reasonCode !== Result::SUCCESS)
        {
            $status = Status::CAPTURE_FAILED;
            $error  = ResponseCode::$reasonCodes[$response['reasonCode']];
        }

        $attributes = array(
            Entity::AMOUNT      => $input['payment']['amount'],
            Entity::RECEIVED    => true,
            Entity::CAPTURE_REF => $response[self::REQUEST_ID],
            Entity::STATUS      => $status,
            Entity::REASON_CODE => $response['reasonCode'],
        );

        $this->createGatewayPaymentEntity($attributes, $input);

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $this->throwException($response);
        }
    }

    protected function persistAfterRefund($input, $response)
    {
        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, $response);

        $reasonCode = (int) $response['reasonCode'];

        $attributes = array(
            Entity::AMOUNT      => $input['refund']['amount'],
            Entity::REFUND_ID   => $input['refund']['id'],
            Entity::REF         => $response['requestID'],
            Entity::STATUS      => ($reasonCode !== Result::SUCCESS) ? Status::REFUND_FAILED : Status::REFUNDED,
            Entity::REASON_CODE => $reasonCode,
            Entity::ACTION      => Base\Action::REFUND,
            Entity::RECEIVED    => true
        );

        $this->createGatewayPaymentEntity($attributes, $input);

        if ($reasonCode !== Result::SUCCESS)
        {
            $this->throwException($response);
        }
    }

    protected function createAuthEnrolledRequestFields($input)
    {
        $content = $this->getCommonRequestData($input);

        $content[self::PAYER_AUTH_VALIDATE_SERVICE][self::RUN] = 'true';

        $content[self::PAYER_AUTH_VALIDATE_SERVICE][self::SIGNED_PARES] = $input['gateway'][self::PA_RES];

        $this->setBillingInfo($content, $input);
        $this->setCardInfo($content, $input);
        // Unset cvv
        unset($content['card']['cvNumber']);

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function createAuthorizeRequestFields($input)
    {
        $content = $this->getCommonRequestData($input);

        $gateway = $this->retrieveByPaymentId($input['payment']['id']);

        $content['ccAuthService'][self::RUN] = 'true';
        $content['ccAuthService'][self::PARES_STATUS] = $gateway->getParesStatus();
        $content['ccAuthService'][self::XID] = $gateway->getXid();
        $content['ccAuthService'][self::COMMERCE_INDICATOR] = $gateway->getCommerceIndicator();
        $content['ccAuthService'][Entity::ECI] = $gateway->getEci();
        $content['ccAuthService'][self::RECONCILIATION_ID] = $input['payment']['id'];

        $networkCode = $input['card']['network_code'];

        switch ($networkCode)
        {
            case Card\Network::VISA:
                $content['ccAuthService'][Entity::CAVV] = $gateway->getCavv();
                break;

            case Card\Network::MC:
                $content['ucaf'][self::AUTHENTICATION_DATA] = $gateway->getAuthCode();
                $content['ucaf'][self::COLLECTION_INDICATOR] = $gateway->getCollectionIndicator();
                break;

            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
                break;
        }

        $this->setBillingInfo($content, $input);
        $this->setCardInfo($content, $input);

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function createNotEnrolledAuthorizeRequestFields($input, $enrollResponse)
    {
        $content = $this->getCommonRequestData($input);

        $content['ccAuthService'] = [
            'run'               => 'true',
            'commerceIndicator' => $enrollResponse['payerAuthEnrollReply'][self::COMMERCE_INDICATOR],
            'veresEnrolled'     => $enrollResponse['payerAuthEnrollReply'][self::VERES_ENROLLED],
            'reconciliationID'  => $input['payment']['id']
        ];

        $networkCode = $input['card']['network_code'];

        $payerAuthEnrollReply = $enrollResponse['payerAuthEnrollReply'];

        switch ($networkCode)
        {
            case Card\Network::VISA:

                $eci = (int) $payerAuthEnrollReply[self::ECI];

                if (($eci === 7) or ($eci === 0))
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                $content['ccAuthService']['eci'] = $payerAuthEnrollReply['eci'];
                break;

            case Card\Network::MC:

                $colInd = (int) $payerAuthEnrollReply[self::UCAF_COLLECTION_INDICATOR];

                if(($colInd === 0) or ($colInd === 7))
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                $content['ucaf']['collectionIndicator'] = $payerAuthEnrollReply['ucafCollectionIndicator'];
                break;

            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
                break;
        }

        $content['card']['accountNumber']   = $input['card']['number'];
        $content['card']['expirationMonth'] = $input['card']['expiry_month'];
        $content['card']['expirationYear']  = $input['card']['expiry_year'];
        $content['card']['cvNumber']        = $input['card']['cvv'];

        $this->setBillingInfo($content, $input);

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function createCaptureRequestFields($input)
    {
        $content = $this->getCommonRequestData($input);

        $gatewayPayment = $this->repo->retrieveByPaymentIdOrFail($input['payment']['id']);

        $content['ccCaptureService'] = [
            self::RUN => 'true',
            self::AUTH_REQUEST_ID => $gatewayPayment->getRef()
        ];

        $content['card'] = [
            'expirationMonth' => $input['card']['expiry_month'],
            'expirationYear' => $input['card']['expiry_year']
        ];

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function createRefundRequestFields($input)
    {
        $content = $this->getCommonRequestData($input);

        $gateway = $this->repo->retrieveByPaymentIdAndStatus($input['payment']['id'], Status::CAPTURED);

        $content['ccCreditService'][self::RUN] = 'true';
        $content['ccCreditService'][self::CAPTURE_REQUEST_ID] = $gateway->getCaptureRef();

        $content['item'] = [
            [
                'unitPrice' => ($input['refund']['amount']/100),
                'id'        => '1'
            ]
        ];

        $content['purchaseTotals']['grandTotalAmount'] = ($input['refund']['amount']/100);

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function getEnrollRequestObject($input)
    {
        $content = $this->getCommonRequestData($input);

        $content['payerAuthEnrollService'][self::RUN] = 'true';

        $content['card']['accountNumber'] = $input['card']['number'];
        $content['card']['expirationMonth'] = $input['card']['expiry_month'];
        $content['card']['expirationYear'] = $input['card']['expiry_year'];

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function getPaymentVerifyRequestContent($input, $payment)
    {
        $content = [
            'type'          => 'transaction',
            'subtype'       => 'transactionDetail',
            'merchantID'    => $this->getMerchantID($input['terminal']),
            'requestID'     => $payment->getRef(),
            'versionNumber' => '1.90'
        ];

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function getSoapClientObject($request)
    {
        $soapClient = new CybersourceSoapClient($request['url'],
                                                $request['options']['auth'],
                                                $request['connect_options']);

        return $soapClient;
    }

    protected function getWsdlFile()
    {
        $file = __DIR__ . '/Wsdl/cybslive.wsdl.xml';

        if ($this->mode === Mode::TEST)
        {
            $file = __DIR__ . '/Wsdl/cybstest.wsdl.xml';
        }

        return $file;
    }

    protected function getMerchantID($terminal)
    {
        $mid = $terminal['gateway_terminal_id'];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->config[self::TEST_USERNAME];
        }

        return $mid;
    }

    protected function setDebugDetail(&$request)
    {
        $request['clientLibrary'] = '';

        $request['clientLibraryVersion'] = '';

        $request['clientEnvironment'] = '';
    }

    protected function setBillingInfo(&$content, $input)
    {
        $content['billTo'] = [
            'firstName'     => 'noreal',
            'lastName'      => 'name',
            'street1'       => '1295 Charleston Rd',
            'city'          => 'Mountain View',
            'state'         => 'CA',
            'postalCode'    => '94043',
            'country'       => 'US',
            'email'         => $input['payment']['email']
        ];
    }

    protected function getCardDetailsFromCache($input)
    {
        $key = 'cybersource_' . $input['payment']['id'] . '_card_details';

        return Cache::store($this->secureCache)->pull($key);
    }

    protected function setCardInfo(&$request, $input)
    {
        $request['card'] = [
            'accountNumber'     => $input['card']['number'],
            'expirationMonth'   => $input['card']['expiry_month'],
            'expirationYear'    => $input['card']['expiry_year'],
            'cvNumber'          => $input['card']['cvv'],
        ];
    }

    protected function decideAuthStepAfterEnroll($enrollResponse, $input)
    {
        switch ($enrollResponse['reasonCode'])
        {
            case Result::ENROLLED:
                $this->persistCardDetailsTemporarily($input);

                return $this->getFieldsForFormSubmitToBankACS($enrollResponse, $input);

            case Result::NOT_ENROLLED:
                $this->validateEnrollResponseNotEnrolled($enrollResponse, $input);

                return $this->postNotEnrolledAuthorize($input, $enrollResponse);

            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
        }
    }

    protected function persistCardDetailsTemporarily($input)
    {
        $cvv = $input['card']['cvv'];

        $vaultToken = null;

        if (empty($input['card']['vault_token']) === false)
        {
            $vaultToken = $input['card']['vault_token'];
        }
        else
        {
            $vaultToken = Card\Tokenex::getVaultToken($input['card']['number']);
        }

        $key = 'cybersource_' . $input['payment']['id'] . '_card_details';

        $data = [
            'cvv'         => Crypt::encrypt($cvv),
            'vault_token' => $vaultToken
        ];

        Cache::store($this->secureCache)->put($key, $data, 10);
    }

    protected function getFieldsForFormSubmitToBankACS($enrollResponse, $input)
    {
        $content['TermUrl'] = $input['callbackUrl'];
        $content['MD']      = $input['payment']['id'];
        $content['PaReq']   = $enrollResponse['payerAuthEnrollReply']['paReq'];

        $request['content'] = $content;
        $request['url']     = $enrollResponse['payerAuthEnrollReply']['acsURL'];
        $request['method']  = 'post';

        return $request;
    }

    protected function validateEnrollResponseNotEnrolled($enrollResponse, $input)
    {
        $payerAuth = $enrollResponse['payerAuthEnrollReply'];

        $networkCode = $input['card']['network_code'];

        switch ($networkCode)
        {
            case Card\Network::VISA:
                if (isset($payerAuth[Entity::ECI]) === false)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                // NOTE: Make sure PHP return correct int on conversion
                // Example: '012' should be converted to decimal 12 not octal 12
                $eci = (int) $payerAuth[Entity::ECI];

                if (in_array($eci, [0, 7], true) === true)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                break;

            case Card\Network::MC:
                if (isset($payerAuth[self::UCAF_COLLECTION_INDICATOR]) === false)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                $ucaf = (int) $payerAuth[self::UCAF_COLLECTION_INDICATOR];

                if (($ucaf === 0) or ($ucaf === 7))
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                break;
        }
    }

    protected function postRequest($request)
    {
        $soapClient = $this->getSoapClientObject($request);

        $content = json_decode(json_encode($request['content']));

        $response = $soapClient->runTransaction($content);

        return json_decode(json_encode($response), true);;
    }

    protected function traceGatewayRequest($traceCode, $request)
    {
        unset($request['content']['card']);
        unset($request['card']);
        unset($request['options']['auth']);

        $this->trace->info($traceCode, $request);
    }

    protected function getStandardSoapRequest($content = [], $method = 'post')
    {
        $request = [
            'url'     => $this->getWsdlFile(),
            'method'  => $method,
            'content' => $content,
            'options' => [
                'auth' => $this->getCredentials()
            ],
            'connect_options' => [
                'exception' => true,
                'connection_timeout' => self::CONNECTION_TIMEOUT
            ],
        ];

        return $request;
    }

    protected function getCommonRequestData($input)
    {
        $data = [];

        // Merchant data
        $data['merchantID'] = $this->getMerchantID($input['terminal']);
        $data['merchantReferenceCode'] = $input['payment']['id'];

        // Debug data
        $data['clientLibrary'] = '';
        $data['clientLibraryVersion'] = '';
        $data['clientEnvironment'] = '';

        // Purchase info
        $data['purchaseTotals']['currency'] = $input['payment']['currency'];

        // Item info
        $data['item'] = [
            [
                'unitPrice' => ($input['payment']['amount']/100),
                'id'        => '1'
            ]
        ];

        return $data;
    }

    protected function getCredentials()
    {
        $terminal = $this->terminal;

        $auth = array(
            'username' => $terminal['gateway_terminal_id'],
            'password' => $terminal['gateway_terminal_password']
        );

        if ($this->mode === Mode::TEST)
        {
            $auth = array(
                'username' => $this->config[self::TEST_USERNAME],
                'password' => $this->config[self::TEST_PASSWORD]
            );
        }

        return $auth;
    }

    protected function setCybersourceCredentials(&$request)
    {
        $terminal = $this->terminal;

        $auth = array(
            'username' => $terminal['gateway_merchant_id'],
            'password' => $terminal['gateway_secure_secret']
        );

        if ($this->mode === Mode::TEST)
        {
            $auth = array(
                'username' => $this->config[self::TEST_MERCHANT_ID],
                'password' => $this->config[self::TEST_MERCHANT_SECRET]
            );
        }

        $request['options']['auth'] = [$auth['username'], $auth['password']];
    }

    protected function retrieveByPaymentId($paymentId)
    {
        if ($this->model === null)
        {
            $this->model = $this->repo->retrieveByPaymentIdOrFail($paymentId);
        }

        return $this->model;
    }

    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];

        $payment->setPaymentId($paymentId);

        $payment->setAction($this->action);

        $payment->fill($attributes);

        $payment->saveOrFail();

        $this->model = $payment;

        return $payment;
    }

    protected function fetchAuthorizeReplyFromContent($content)
    {
        $applicationReplies = $content['Requests']['Request']['ApplicationReplies']['ApplicationReply'];

        if ($this->isSequentialArray($applicationReplies) === false)
        {
            $applicationReplies = [$applicationReplies];
        }

        foreach($applicationReplies as $applicationReply)
        {
            if ($applicationReply['@attributes']['Name'] === 'ics_auth')
            {
                return $applicationReply;
            }
        }

        return [];
    }


    protected function getVerifyContentFromResponse($verify)
    {
        $content = $verify->verifyResponseContent;

        $paymentData = $content['Requests']['Request']['PaymentData'];

        $paInfo = $paymentData['PayerAuthenticationInfo'];

        $data = [
            'eci' => str_pad($paInfo['ECI'], 2, '0', STR_PAD_LEFT),
            'cavv' => $paInfo['AAV_CAVV'],
            'xid' => $paInfo['XID'],
            'reason_code' => 100,
            'action' => Base\Action::AUTHORIZE,
            'status' => Status::AUTHORIZED
        ];

        $verify->verifyResponseContent = $data;
    }

    protected function xmlToArray($data)
    {
        $xml_values = simplexml_load_string($data);

        return json_decode(json_encode($xml_values), true);
    }

    protected function isSequentialArray($array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    // Exception handling

    /**
     * @param \SoapFault $sf
     * @throws Exception\GatewayTimeoutException
     * @throws Exception\RuntimeException
     */
    protected function handleSoapFault(SoapFault $sf, $errMsg, $safeRetry = false)
    {
        if (Utility::checkSoapTimeout($sf) === true)
        {
            throw new Exception\GatewayTimeoutException(
                                $sf->getMessage(), $sf, $safeRetry);
        }

        throw new Exception\RuntimeException(
            $errMsg, null, $sf);
    }

    /**
     * @param $response
     * @throws Exception\BadRequestException
     * @throws Exception\GatewayErrorException
     */
    protected function throwException($response)
    {
        if (isset($response['reasonCode']) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        $reasonCode = $response['reasonCode'];

        $desc = ResponseCode::$reasonCodes[$reasonCode];

        if (ResponseCode::isValidationError($reasonCode))
        {
            throw new Exception\BadRequestException(
                ResponseCode::getMappedCode($reasonCode),
                $reasonCode);
        }

        throw new Exception\GatewayErrorException(
            ResponseCode::getMappedCode($reasonCode),
            $reasonCode,
            $desc);
    }
}
