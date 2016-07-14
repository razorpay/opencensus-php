<?php

namespace RZP\Gateway\Cybersource;

use Requests;
use RZP\Error;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Card;
use RZP\Trace\Trace;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
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
    const TEST_ACCESS_CODE            = 'test_access_code';
    const TEST_MERCHANT_ID            = 'test_merchant_id';
    const UCAF                        = 'ucaf';
    const UCAF_AUTHENTICATION_DATA    = 'ucafAuthenticationData';
    const UCAF_COLLECTION_INDICATOR   = 'ucafCollectionIndicator';
    const UNIT_PRICE                  = 'unitPrice';
    const VERES_ENROLLED              = 'veresEnrolled';
    const TEST_WSDL_FILE              = 'cybstest.wsdl.xml';
    const LIVE_WSDL_FILE              = 'cybslive.wsdl.xml';
    const XID                         = 'xid';

    protected $gateway = Constants\Table::CYBERSOURCE;

    protected $repo;

    protected $enrollRequest;

    public function authorize(array $input)
    {
        $response = $this->enroll($input);

        return $this->decideAuthStepAfterEnroll($response, $input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $gateway = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $gateway->fill([Entity::RECEIVED => true]);

        $gateway->saveOrFail();

        $response = $this->postAuthEnrolledRequest($input);

        $this->postEnrollAuthorize($input);
    }

    public function capture(array $input)
    {
        $request = $this->createCaptureRequestFields($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_CAPTURE_REQUEST, $request);

        try
        {
            $response = $this->postGatewayRequest($request, $input);

            $this->persistAfterCapture($input,  $response, $request);
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Capture request failed.', null, $exception);
        }
    }

    public function refund(array $input)
    {
        $request = $this->createRefundRequestFields($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        try
        {
            $response = $this->postGatewayRequest($request, $input);

            $this->persistAfterRefund($input, $response, $request);
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Refund request failed.', null, $exception);
        }
    }

    protected function enroll($input)
    {
        $request = $this->getEnrollRequestObject($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_ENROLL_REQUEST, $request);

        try
        {
            $response = $this->postGatewayRequest($request, $input);

            $this->persistAfterEnroll($input, $response, $request);

            return $response;
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Server error occurred', null, $exception);
        }
    }

    protected function postAuthEnrolledRequest($input)
    {
        $request = $this->createAuthEnrolledRequestFields($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_VALIDATE_REQUEST, $request);

        try
        {
            $response = $this->postGatewayRequest($request, $input);

            $this->persistAfterValidate($input, $response, $request);

            return $response;
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Validation request failed.', null, $exception);
        }
    }

    protected function postEnrollAuthorize($input)
    {
        $request = $this->createAuthorizeRequestFields($input);

        $this->traceGatewayRequest(TraceCode::GATEWAY_AUTHORIZE_REQUEST, $request);

        try
        {
            $response = $this->postGatewayRequest($request, $input);

            $this->persistAfterAuthorize($input, $response, $request);
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Authorization failed.', null, $exception);
        }
    }

    protected function postNotEnrolledAuthorize($input, $enrollResponse)
    {
        $request = $this->createNotEnrolledAuthorizeRequestFields($input, $enrollResponse);

        $this->traceGatewayRequest(TraceCode::GATEWAY_AUTHORIZE_REQUEST, $request);

        try
        {
            $response = $this->postGatewayRequest($request, $input);

            $this->persistAfterNotEnrolledAuthorize($input, $response, $request);
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Authorization failed.', null, $exception);
        }
    }

    protected function persistAfterValidate($input, $response, $request)
    {
        $gateway = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $this->trace->info(TraceCode::GATEWAY_VALIDATE_RESPONSE, $response);

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $attributes = array(
                Entity::REASON_CODE => $response['reasonCode']
            );

            $gateway->fill($attributes);

            $gateway->saveOrFail();

            throw new Exception\BadRequestException(
                            ResponseCodeMap::$map[$response['reasonCode']]);
        }

        $payAuthRep = $response[self::PAYER_AUTH_VALIDATE_REPLY];

        $attributes = array(
            Entity::COMMERCE_INDICATOR => $payAuthRep[self::COMMERCE_INDICATOR],
            Entity::XID                => $payAuthRep[self::XID],
            Entity::PARES_STATUS       => $payAuthRep[self::PARES_STATUS]
        );

        $networkCode = $input['card']['network_code'];

        switch ($networkCode)
        {
            case Card\Network::VISA:
                if (array_key_exists(Entity::ECI, $payAuthRep) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                $attributes[Entity::ECI] = $payAuthRep[Entity::ECI];
                $attributes[Entity::CAVV] = $payAuthRep[self::CAVV];
                break;

            case Card\Network::MC:
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
        $gatewayPayment = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_RESPONSE, $response);

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $attributes = array(
                Entity::STATUS     => Status::AUTHORIZE_FAILED,
                Entity::REASON_CODE => $response['reasonCode']
            );

            $gatewayPayment->fill($attributes);

            $gatewayPayment->saveOrFail();

            throw new Exception\BadRequestException(
                            ResponseCodeMap::$map[$response['reasonCode']]);
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
        $gateway = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_RESPONSE, $response);

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $attributes = array(
                Entity::STATUS      => Status::AUTHORIZE_FAILED,
                Entity::REASON_CODE => $response['reasonCode']
            );

            $gateway->fill($attributes);

            $gateway->saveOrFail();

            throw new Exception\BadRequestException(
                            ResponseCodeMap::$map[$response['reasonCode']]);
        }

        $attributes = array(
            Entity::REF    => $response[self::REQUEST_ID],
            Entity::STATUS => Status::AUTHORIZED
        );

        $gateway->fill($attributes);

        $gateway->saveOrFail();
    }

    protected function persistAfterEnroll($input, $response, $request)
    {
        $this->trace->info(TraceCode::GATEWAY_ENROLL_RESPONSE, $response);

        $reasonCode = null;

        if (($response['reasonCode'] !== Result::ENROLLED) and
            ($response['reasonCode'] !== Result::SUCCESS))
        {
            $reasonCode = $response['reasonCode'];
        }

        $attributes = array(
            Entity::PAYMENT_ID    => $input['payment']['id'],
            Entity::AMOUNT        => $request[self::ITEM][0][self::UNIT_PRICE],
            Entity::REASON_CODE    => $reasonCode,
            Entity::STATUS        => Status::CREATED,
            Entity::REF           => $response[self::REQUEST_ID]
        );

        $this->getRepo()->createOrFail($attributes);

        if (($response['reasonCode'] !== Result::ENROLLED) and
            ($response['reasonCode'] !== Result::SUCCESS))
        {
            throw new Exception\BadRequestException(
                            ResponseCodeMap::$map[$response['reasonCode']]);
        }
    }

    protected function persistAfterCapture($input, $response, $request)
    {
        $gateway = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_RESPONSE, $response);

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $attributes = array(
                Entity::STATUS      => Status::CAPTURE_FAILED,
                Entity::REASON_CODE => $response['reasonCode'],
                Entity::ACTION      => Base\Action::CAPTURE
            );

            $gateway->fill($attributes);

            $gateway->saveOrFail();

            throw new Exception\BadRequestException(
                            ResponseCodeMap::$map[$response['reasonCode']]);
        }

        $attributes = array(
            Entity::CAPTURE_REF => $response[self::REQUEST_ID],
            Entity::STATUS      => Status::CAPTURED,
            Entity::ACTION      => Base\Action::CAPTURE
        );

        $gateway->fill($attributes);

        $gateway->saveOrFail();
    }

    protected function persistAfterRefund($input, $response, $request)
    {
        $gateway = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, $response);

        if ($response['reasonCode'] !== Result::SUCCESS)
        {
            $attributes = array(
                Entity::REASON_CODE => $response['reasonCode'],
                Entity::ACTION     => Base\Action::REFUND
            );

            $gateway->fill($attributes);

            $gateway->saveOrFail();

            throw new Exception\BadRequestException(
                            ResponseCodeMap::$map[$response['reasonCode']]);

        }

        $attributes = array(
            Entity::REFUND_ID => $input['refund']['id'],
            Entity::STATUS    => Status::REFUNDED,
            Entity::ACTION    => Base\Action::REFUND
        );

        $gateway->fill($attributes);

        $gateway->saveOrFail();
    }

    protected function createAuthEnrolledRequestFields($input)
    {
        $request = [];

        $this->setMerchantDetailInRequest($request, $input);

        $this->setDebugDetail($request);

        $request[self::PAYER_AUTH_VALIDATE_SERVICE][self::RUN] = 'true';

        $request[self::PAYER_AUTH_VALIDATE_SERVICE][self::SIGNED_PARES] = $input['gateway'][self::PA_RES];

        $this->setBillingInfo($request, $input);

        $this->setCardInfoFromTokenex($request, $input);

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }

    protected function createAuthorizeRequestFields($input)
    {
        $request = array();

        $this->setMerchantDetailInRequest($request, $input);

        $this->setDebugDetail($request);

        $gateway = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $request['ccAuthService'][self::RUN] = 'true';
        $request['ccAuthService'][self::PARES_STATUS] = $gateway->getParesStatus();
        $request['ccAuthService'][self::XID] = $gateway->getXid();
        $request['ccAuthService'][self::COMMERCE_INDICATOR] = $gateway->getCommerceIndicator();
        $request['ccAuthService'][Entity::ECI] = $gateway->getEci();
        $request['ccAuthService'][self::RECONCILIATION_ID] = $input['payment']['id'];

        $networkCode = $input['card']['network_code'];

        switch ($networkCode)
        {
            case Card\Network::VISA:
                $request['ccAuthService'][Entity::CAVV] = $gateway->getCavv();
                break;

            case Card\Network::MC:
                $request[self::UCAF][self::AUTHENTICATION_DATA] = $gateway->getAuthData();
                $request[self::UCAF][self::COLLECTION_INDICATOR] = $gateway->getCollectionIndicator();
                break;

            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
                break;
        }

        $this->setBillingInfo($request, $input);
        $this->setCardInfoFromTokenex($request, $input);
        $this->setPurchaseDetail($request, $input);
        $this->setItemDetail($request, $input);

        return $request;
    }

    protected function createNotEnrolledAuthorizeRequestFields($input, $enrollResponse)
    {
        $request = array();

        $this->setMerchantDetailInRequest($request, $input);
        $this->setDebugDetail($request);

        $request['ccAuthService'][self::RUN] = true;

        $networkCode = $input['card']['network_code'];

        switch ($networkCode)
        {
            case Card\Network::VISA:

                $eci = (int) $enrollResponse['payerAuthEnrollReply'][self::ECI];

                if (($eci === 7) or ($eci === 0))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                $request['ccAuthService'][self::ECI] = $eci;
                break;

            case Card\Network::MC:

                $colInd = (int) $enrollResponse['payerAuthEnrollReply'][self::UCAF_COLLECTION_INDICATOR];

                if(($colInd === 0) or ($colInd === 7))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                $request[self::UCAF][self::COLLECTION_INDICATOR] = $colInd;
                break;

            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
                break;
        }

        $request['ccAuthService'][self::COMMERCE_INDICATOR] =
            $enrollResponse['payerAuthEnrollReply'][self::COMMERCE_INDICATOR];

        $request['ccAuthService'][self::VERES_ENROLLED] =
            $enrollResponse['payerAuthEnrollReply'][self::VERES_ENROLLED];

        $request['ccAuthService'][self::RECONCILIATION_ID] = $input['payment']['id'];

        $this->setBillingInfo($request, $input);

        $request[self::CARD][self::ACCOUNT_NUMBER] = $input['card']['number'];
        $request[self::CARD][self::EXPIRATION_MONTH] = $input['card']['expiry_month'];
        $request[self::CARD][self::EXPIRATION_YEAR] = $input['card']['expiry_year'];

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }

    protected function createCaptureRequestFields($input)
    {
        $request = array();

        $this->setMerchantDetailInRequest($request, $input);

        $this->setDebugDetail($request);

        $gatewayPayment = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $request['ccCaptureService'][self::RUN] = 'true';

        $request['ccCaptureService'][self::AUTH_REQUEST_ID] = $gatewayPayment->getCaptureRef();

        $request[self::CARD][self::EXPIRATION_MONTH] = $input['card']['expiry_month'];

        $request[self::CARD][self::EXPIRATION_YEAR] = $input['card']['expiry_year'];

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }

    protected function createRefundRequestFields($input)
    {
        $request = array();

        $this->setMerchantDetailInRequest($request, $input);

        $this->setDebugDetail($request);

        $gateway = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $request['ccCreditService'][self::RUN] = 'true';

        $request['ccCreditService'][self::CAPTURE_REQUEST_ID] = $gateway->getCaptureRef();

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }

    protected function getEnrollRequestObject($input)
    {
        $request = [];

        $this->setMerchantDetailInRequest($request, $input);

        $this->setDebugDetail($request);

        $request['payerAuthEnrollService'][self::RUN] = 'true';

        $request[self::CARD][self::ACCOUNT_NUMBER] = $input['card']['number'];

        $request[self::CARD][self::EXPIRATION_MONTH] = $input['card']['expiry_month'];

        $request[self::CARD][self::EXPIRATION_YEAR] = $input['card']['expiry_year'];

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }


    protected function getSoapClientObject($input)
    {
        $url = $this->getWsdlFile();

        $auth = array(
            'username' => $input['terminal']['gateway_terminal_id'],
            'password' => $input['terminal']['gateway_terminal_password']
        );

        if ($this->mode === Mode::TEST)
        {
            $auth = array(
                'username' => $this->config[self::TEST_MERCHANT_ID],
                'password' => $this->config[self::TEST_ACCESS_CODE]
            );
        }

        $soapClient = new CybersourceSoapClient($url, $auth);

        return $soapClient;
    }

    protected function getWsdlFile()
    {
        $file = storage_path('gateway/cybersource/cybslive.wsdl.xml');

        if ($this->mode === Mode::TEST)
        {
            $file = storage_path('gateway/cybersource/cybstest.wsdl.xml');
        }

        return $file;
    }

    protected function setMerchantDetailInRequest(&$request, $input)
    {
        $request['merchantID'] = $this->getMerchantID($input['terminal']);

        $request['merchantReferenceCode'] = $input['payment']['id'];
    }

    protected function getMerchantID($terminal)
    {
        $mid = $terminal['gateway_terminal_id'];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->config[self::TEST_MERCHANT_ID];
        }

        return $mid;
    }

    protected function setDebugDetail(&$request)
    {
        $request['clientLibrary'] = '';

        $request['clientLibraryVersion'] = '';

        $request['clientEnvironment'] = '';
    }

    protected function setBillingInfo(&$request, $input)
    {
        $request['billTo']['firstName'] = $input['card']['name'];

        $request['billTo']['lastName'] = 'a';

        $request['billTo']['street1'] = 'a';

        $request['billTo']['city'] = 'a';

        $request['billTo']['state'] = 'a';

        $request['billTo']['postalCode'] = '5';

        $request['billTo']['country'] = 'India';

        $request['billTo']['email'] = $input['payment']['email'];
    }

    protected function setCardInfoFromTokenex(&$request, $input)
    {
        $request[self::CARD][self::ACCOUNT_NUMBER] = Card\Tokenex::getCardNumber($input['card']['vault_token']);

        $request[self::CARD][self::EXPIRATION_MONTH] = $input['card']['expiry_month'];

        $request[self::CARD][self::EXPIRATION_YEAR] = $input['card']['expiry_year'];
    }

    protected function setPurchaseDetail(&$request, $input)
    {
        $request['purchaseTotals']['currency'] = $input['payment']['currency'];
    }

    protected function setItemDetail(&$request, $input)
    {
        $item = array();
        $item[0][self::UNIT_PRICE] = ($input['payment']['amount']/100);
        $item[0]['id'] = '1';

        $request[self::ITEM] = $item;
    }

    protected function decideAuthStepAfterEnroll($enrollResponse, $input)
    {
        switch ($enrollResponse['reasonCode'])
        {
            case Result::ENROLLED:
                return $this->getFieldsForFormSubmitToBankACS($enrollResponse, $input);

            case Result::NOT_ENROLLED:
                $this->validateEnrollResponseNotEnrolled($enrollResponse, $input);

                return $this->postNotEnrolledAuthorize($input, $enrollResponse);

            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
        }
    }

    protected function getFieldsForFormSubmitToBankACS($enrollResponse, $input)
    {
        $content['TermUrl'] = $input['callbackUrl'];
        $content['MD'] = $input['payment']['id'];
        $content['PaReq'] = $enrollResponse['payerAuthEnrollReply']['paReq'];

        $request['content'] = $content;
        $request['url'] = $enrollResponse['payerAuthEnrollReply']['acsURL'];
        $request['method'] = 'post';

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
                    throw new Exception\BadRequestException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                // NOTE: Make sure PHP return correct int on conversion
                // Example: '012' should be converted to decimal 12 not octal 12
                $eci = (int) $payerAuth[Entity::ECI];

                if (in_array($eci, [0, 7], true) === true)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                break;

            case Card\Network::MC:
                $ucaf = (int) $payerAuth[self::UCAF_COLLECTION_INDICATOR];

                if(($ucaf === 0) or ($ucaf === 7))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                break;
        }
    }

    protected function postGatewayRequest($request, $input)
    {
        $request = json_decode(json_encode($request));

        $soapClient = $this->getSoapClientObject($input);

        $response = $soapClient->runTransaction($request);

        return json_decode(json_encode($response), true);;
    }

    protected function traceGatewayRequest($traceCode, $request)
    {
        unset($request['card']);

        $this->trace->info($traceCode, $request);
    }
}
