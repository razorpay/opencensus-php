<?php

namespace Gateway\Cybersource;

use Constants\Mode;
use EE\Error;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Gateway\AxisMigs;
use Requests;
use Trace\Trace;
use Trace\TraceCode;
use Models\Card;
use Models\Payment as Pay;
use Models\Terminal;
use EE\Error\ErrorCode;
use Constants;

class Gateway extends Base\Gateway
{
    const REASON_CODE                 = 'reasonCode';
    const ECI_RAW                     = 'eciRaw';
    const COMMERCE_INDICATOR          = 'commerceIndicator';
    const PARES_STATUS                = 'paresStatus';
    const COLLECTION_INDICATOR        = 'collectionIndicator';
    const REQUEST_ID                  = 'requestID';
    const GATEWAY                     = 'gateway';
    const RUN                         = 'run';
    const RECONCILIATION_ID           = 'reconciliationID';
    const ACCOUNT_NUMBER              = 'accountNumber';
    const EXPIRATION_MONTH            = 'expirationMonth';
    const EXPIRATION_YEAR             = 'expirationYear';
    const PAYER_AUTH_VALIDATE_REPLY   = 'payerAuthValidateReply';
    const UCAF_AUTHENTICATION_DATA    = 'ucafAuthenticationData';
    const UCAF_COLLECTION_INDICATOR   = 'ucafCollectionIndicator';
    const PAYER_AUTH_ENROLL_REPLY     = 'payerAuthEnrollReply';
    const AUTHENTICATION_DATA         = 'authenticationData';
    const UCAF                        = 'ucaf';
    const VERES_ENROLLED              = 'veresEnrolled';
    const CC_AUTH_SERVICE             = 'ccAuthService';
    const AUTH_REQUEST_ID             = 'authRequestID';
    const CC_CAPTURE_SERVICE          = 'ccCaptureService';
    const ITEM                        = 'item';
    const UNIT_PRICE                  = 'unitPrice';
    const SIGNED_PARES                = 'signedPARes';
    const PA_RES                      = 'PaRes';
    const PAYER_AUTH_VALIDATE_SERVICE = 'payerAuthValidateService';
    const CAPTURE_REQUEST_ID          = 'captureRequestID';
    const CC_CREDIT_SERVICE           = 'ccCreditService';
    const TERMINAL                    = 'terminal';
    const TEST_MERCHANT_ID            = 'test_merchant_id';
    const TEST_ACCESS_CODE            = 'test_access_code';
    const PAYER_AUTH_ENROLL_SERVICE   = 'payerAuthEnrollService';
    const WSDL_FILE                   = 'cybs.wsdl.xml';


    protected $gateway = Constants\Table::CYBERSOURCE;

    protected $repo;

    protected $enrollRequest;

    public function authorize(array $input)
    {
        $reply = $this->enroll($input);

        return $this->decideAuthStepAfterEnroll($reply, $input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $gateway = $this->getRepo()->retrieveByPaymentId($input[Constants\Entity::PAYMENT][Entity::ID]);

        $gateway->fill(array(Entity::RECEIVED => 1));

        $gateway->saveOrFail();

        $status = $this->postAuthEnrolledRequest($input);

        if ($status === Payment\Result::SUCCESS)
        {
            $this->postEnrollAuthorize($input);
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }
    }

    protected function persistAfterValidate($input, $response, $request)
    {
        $repo = $this->getRepo();

        $gateway = $repo->retrieveByPaymentId($input[Constants\Entity::PAYMENT][Entity::ID]);

        $this->trace->info(TraceCode::GATEWAY_VALIDATE_RESPONSE, $response);

        if ($response[self::REASON_CODE] !== Payment\Result::SUCCESS)
        {
            $attributes = array(Entity::ERROR_CODE => $response[self::REASON_CODE]);

            $gateway->fill($attributes);
            $gateway->saveOrFail();   

            throw new Exception("Error in Validate: ".ResponseCodeMap::$map[$response[self::REASON_CODE]], 1);
            
        }
        else
        {
            $payAuthRep = $response[self::PAYER_AUTH_VALIDATE_REPLY];
            $attributes = array(
                Entity::ECI                => $payAuthRep[self::ECI_RAW],
                Entity::COMMERCE_INDICATOR => $payAuthRep[self::COMMERCE_INDICATOR],
                Entity::XID                => $payAuthRep[Entity::XID],
                Entity::PARES_STATUS       => $payAuthRep[self::PARES_STATUS]);
            
            $network = $input[Constants\Entity::CARD][Card\Entity::NETWORK];

            switch ($network)
            {
                case Card\Network::getFullName(Card\Network::VISA):
                    $attributes[Entity::CAVV] = $payAuthRep[Entity::CAVV];
                    break;

                case Card\Network::getFullName(Card\Network::MC):
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
    }

    protected function postAuthEnrolledRequest($input)
    {
        $request = $this->createAuthEnrolledRequestFields($input);

        $this->trace->info(TraceCode::GATEWAY_VALIDATE_REQUEST, $request);

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterValidate($input, $reply, $request);

            return $reply[self::REASON_CODE];

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

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_REQUEST, $request);

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterAuthorize($input, $reply, $request);

            if ($reply[self::REASON_CODE] !== Payment\Result::SUCCESS)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_PREVENTED_AUTHORIZATION);
            }
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Authorization failed.', null, $exception);
        }
    }

    public function postNotEnrolledAuthorize($input, $enrollResponse)
    {
        $request = $this->createNotEnrolledAuthorizeRequestFields($input, $enrollResponse);

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_REQUEST, $request);

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterNotEnrolledAuthorize($input, $reply, $request);

            if ($reply[self::REASON_CODE] !== Payment\Result::SUCCESS)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_PREVENTED_AUTHORIZATION);
            }
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Authorization failed.', null, $exception);
        }
    }

    protected function persistAfterNotEnrolledAuthorize($input, $response, $request)
    {
        $repo = $this->getRepo();

        $gateway = $repo->retrieveByPaymentId($input[Constants\Entity::PAYMENT][Entity::ID]);

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_RESPONSE, $response);

        if ($response[self::REASON_CODE] !== Payment\Result::SUCCESS)
        {
            $attributes = array(
                Entity::STATUS     => Payment\Status::AUTHORIZE_FAILED,
                Entity::ERROR_CODE => $response->reasonCode);

            $gateway->fill($attributes);
            $gateway->saveOrFail();

            throw new Exception("Error in Authorize: ".ResponseCodeMap::$map[$response[self::REASON_CODE]], 1);
        }
        else
        {
            $status = Payment\Status::AUTHORIZED;

            
            $attributes = array(
                Entity::REF    => $response[self::REQUEST_ID],
                Entity::STATUS => $status);

            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
    }

    protected function persistAfterAuthorize($input, $response, $request)
    {
        $repo = $this->getRepo();

        $gateway = $repo->retrieveByPaymentId($input[Constants\Entity::PAYMENT][Entity::ID]);

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_RESPONSE, $response);

        if ($response[self::REASON_CODE] !== Payment\Result::SUCCESS)
        {
            $attributes = array(
                Entity::STATUS     => Payment\Status::AUTHORIZE_FAILED,
                Entity::ERROR_CODE => $response[self::REASON_CODE]);

            $gateway->fill($attributes);
            $gateway->saveOrFail();

            throw new Exception("Error in Authorize: ".ResponseCodeMap::$map[$response[self::REASON_CODE]], 1);
        }
        else
        {
            $status = Payment\Status::AUTHORIZED;

            $attributes = array(
                Entity::REF    => $response[self::REQUEST_ID],
                Entity::STATUS => $status);

            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
    }

    public function createAuthEnrolledRequestFields($input)
    {
        $request = array();

        $this->setMerchantDetailInRequest($request, $input);

        $this->setDebugDetail($request);

        $payerAuthValidateService = array();
        $payerAuthValidateService[self::RUN] = 'true';
        $payerAuthValidateService[self::SIGNED_PARES] = $input[self::GATEWAY][self::PA_RES];
        $request[self::PAYER_AUTH_VALIDATE_SERVICE] = $payerAuthValidateService;

        $this->setBillingInfo($request, $input);

        $this->setCardInfoFromTokenex($request, $input);

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }

    public function createAuthorizeRequestFields($input)
    {
        $request = array();
        $this->setMerchantDetailInRequest($request, $input);
        $this->setDebugDetail($request);

        $ccAuthService = array();
        $ccAuthService[self::RUN] = 'true';

        $repo = $this->getRepo();
        $gateway = $repo->retrieveByPaymentId($input[Constants\Entity::PAYMENT][Entity::ID]);

        $ccAuthService[self::PARES_STATUS] = $gateway->pares_status;
        $ccAuthService[Entity::XID] = $gateway->xid;
        $ccAuthService[self::COMMERCE_INDICATOR] = $gateway->commerce_indicator;
        $ccAuthService[self::ECI_RAW] = $gateway->eci;
        $ccAuthService[self::RECONCILIATION_ID] = $input[Constants\Entity::PAYMENT][Entity::ID];

        $network = $input[Constants\Entity::CARD][Card\Entity::NETWORK];
        switch ($network)
        {
            case Card\Network::getFullName(Card\Network::VISA):
                $ccAuthService[Entity::CAVV] = $gateway->cavv;
                break;

            case Card\Network::getFullName(Card\Network::MC):
                $ucaf = array();
                $ucaf[self::AUTHENTICATION_DATA] = $gateway->auth_data;
                $ucaf[self::COLLECTION_INDICATOR] = $gateway->collection_indicator;

                $request[self::UCAF] = $ucaf;
                break;
            
            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
                break;
        }

        $request[self::CC_AUTH_SERVICE] = $ccAuthService;

        $this->setBillingInfo($request, $input);
        $this->setCardInfoFromTokenex($request, $input);
        $this->setPurchaseDetail($request, $input);
        $this->setItemDetail($request, $input);

        return $request;
    }

    public function createNotEnrolledAuthorizeRequestFields($input, $enrollResponse)
    {
        $request = array();

        $this->setMerchantDetailInRequest($request, $input);
        $this->setDebugDetail($request);

        $ccAuthService = array();
        $ccAuthService[self::RUN] = true;

        $network = $input[Constants\Entity::CARD][Card\Entity::NETWORK];

        $payAuthRep = $enrollResponse[self::PAYER_AUTH_ENROLL_REPLY];
        switch ($network)
        {
            case Card\Network::getFullName(Card\Network::VISA):
                $ccAuthService[Entity::ECI] = $payAuthRep[Entity::ECI];
                break;

            case Card\Network::getFullName(Card\Network::MC):
                $ucaf = array();
                $ucaf[self::COLLECTION_INDICATOR] = $payAuthRep[self::UCAF_COLLECTION_INDICATOR];
                $request[self::UCAF] = $ucaf;
                break;
            
            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
                break;
        }
        
        $ccAuthService[self::COMMERCE_INDICATOR] = $payAuthRep[self::COMMERCE_INDICATOR];
        $ccAuthService[self::VERES_ENROLLED] = $payAuthRep[self::VERES_ENROLLED];
        $ccAuthService[self::RECONCILIATION_ID] = $input[Constants\Entity::PAYMENT][Entity::ID];

        $request[self::CC_AUTH_SERVICE] = $ccAuthService;

        $this->setBillingInfo($request, $input);

        $card = array();
        $card[self::ACCOUNT_NUMBER] = $input[Constants\Entity::CARD][Card\Entity::NUMBER];
        $card[self::EXPIRATION_MONTH] = $input[Constants\Entity::CARD][Card\Entity::EXPIRY_MONTH];
        $card[self::EXPIRATION_YEAR] = $input[Constants\Entity::CARD][Card\Entity::EXPIRY_YEAR];
        $request[Constants\Entity::CARD] = $card;

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }

    public function capture(array $input)
    {
        $request = $this->createCaptureRequestFields($input);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_REQUEST, $request);

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterCapture($input, $reply, $request);

            if ($reply[self::REASON_CODE] !== Payment\Result::SUCCESS)
            {
                throw new Exception\BadRequestException(ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED);
            }

            return $reply[self::REASON_CODE];
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Capture request failed.', null, $exception);
        }
    }

    public function createCaptureRequestFields($input)
    {
        $request = array();

        $this->setMerchantDetailInRequest($request, $input);
        $this->setDebugDetail($request);

        $ccCaptureService = array();
        $ccCaptureService[self::RUN] = 'true';

        $repo = $this->getRepo();
        $gateway = $repo->retrieveByPaymentId($input[Constants\Entity::PAYMENT][Entity::ID]);
        $ccCaptureService[self::AUTH_REQUEST_ID] = $gateway->ref;

        $request[self::CC_CAPTURE_SERVICE] = $ccCaptureService;

        $card = array();
        $card[self::EXPIRATION_MONTH] = $input[Constants\Entity::CARD][Card\Entity::EXPIRY_MONTH];
        $card[self::EXPIRATION_YEAR] = $input[Constants\Entity::CARD][Card\Entity::EXPIRY_YEAR];
        $request[Constants\Entity::CARD] = $card;

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }

    public function refund(array $input)
    {
        $request = $this->createRefundRequestFields($input);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterRefund($input, $reply, $request);

            return $reply[self::REASON_CODE];
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Refund request failed.', null, $exception);
        }
    }

    public function createRefundRequestFields($input)
    {
        $request = array();

        $this->setMerchantDetailInRequest($request, $input);
        $this->setDebugDetail($request);

        $ccCreditService = array();
        $ccCreditService[self::RUN] = 'true';

        $repo = $this->getRepo();
        $gateway = $repo->retrieveByPaymentId($input[Constants\Entity::PAYMENT][Entity::ID]);

        $ccCreditService[self::CAPTURE_REQUEST_ID] = $gateway->capture_ref;
        $request[self::CC_CREDIT_SERVICE] = $ccCreditService;

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }

    public function getSoapClientObject($input)
    {
        $url = $this->getWsdlFile();

        $auth = array(
            'username' => $input[self::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_ID],
            'password' => $input[self::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD]);

        if ($this->mode === Mode::TEST)
        {
            $auth = array(
            'username' => $this->config[self::TEST_MERCHANT_ID],
            'password' => $this->config[self::TEST_ACCESS_CODE]);
        }
        
        $soapClient = new CybersourceSoapClient($url, $auth);

        return $soapClient;
    }

    public function getWsdlFile()
    {
        return dirname(__FILE__) .'/'.self::WSDL_FILE;
    }

    public function enroll($input)
    {
        $request = $this->getEnrollRequestObject($input);

        $this->trace->info(TraceCode::GATEWAY_ENROLL_REQUEST, $request);

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterEnroll($input, $reply, $request);

            return $reply;
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Enroll failed.', null, $exception);
        }
    }

    protected function persistAfterEnroll($input, $response, $request)
    {
        $this->trace->info(TraceCode::GATEWAY_ENROLL_RESPONSE, $response);

        if (($response[self::REASON_CODE] !== Payment\Result::ENROLLED) and 
            ($response[self::REASON_CODE] !== Payment\Result::SUCCESS))
        {
            $attributes = array(
                Entity::PAYMENT_ID    => $input[Constants\Entity::PAYMENT][Entity::ID],
                Entity::AMOUNT        => $request[self::ITEM][0][self::UNIT_PRICE],
                Entity::ERROR_CODE    => $response[self::REASON_CODE],
                Entity::STATUS        => Payment\Status::CREATED,
                Entity::REF           => $response[self::REQUEST_ID]);

            $this->getRepo()->createOrFail($attributes);

            throw new Exception('Error in Enroll: '.ResponseCodeMap::$map[$response[self::REASON_CODE]], 1);
            
        }
        else
        {
            $attributes = array(
                Entity::PAYMENT_ID    => $input[Constants\Entity::PAYMENT][Entity::ID],
                Entity::AMOUNT        => $request[self::ITEM][0][self::UNIT_PRICE],
                Entity::STATUS        => Payment\Status::CREATED,
                Entity::REF           => $response[self::REQUEST_ID]);

            $this->getRepo()->createOrFail($attributes);
        }
    }

    protected function persistAfterCapture($input, $response, $request)
    {
        $repo = $this->getRepo();

        $gateway = $repo->retrieveByPaymentId($input[Constants\Entity::PAYMENT][Entity::ID]);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_RESPONSE, $response);

        if ($response[self::REASON_CODE] !== Payment\Result::SUCCESS)
        {
            $attributes = array(
                Entity::STATUS     => Payment\Status::CAPTURE_FAILED,
                Entity::ERROR_CODE => $response[self::REASON_CODE],
                Entity::ACTION     => Base\Action::CAPTURE);

            $gateway->fill($attributes);
            $gateway->saveOrFail();

            throw new Exception("Error in Capture: ".ResponseCodeMap::$map[$response[self::REASON_CODE]], 1);
        }
        else
        {
            $status = Payment\Status::CAPTURED;

            $attributes = array(
                Entity::CAPTURE_REF => $response[self::REQUEST_ID],
                Entity::STATUS      => $status,
                Entity::ACTION      => Base\Action::CAPTURE);

            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
    }

    protected function persistAfterRefund($input, $response, $request)
    {
        $repo = $this->getRepo();

        $gateway = $repo->retrieveByPaymentId($input[Constants\Entity::PAYMENT][Entity::ID]);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, $response);

        if ($response[self::REASON_CODE] !== Payment\Result::SUCCESS)
        {
            $attributes = array(
                Entity::ERROR_CODE => $response[self::REASON_CODE],
                Entity::ACTION     => Base\Action::REFUND);

            $gateway->fill($attributes);
            $gateway->saveOrFail();

            throw new Exception('Error in Refund: '.ResponseCodeMap::$map[$response[self::REASON_CODE]], 1);
            
        }
        else
        {
            $status = Payment\Status::REFUNDED;

            $attributes = array(
                Entity::REFUND_ID => $input[Constants\Entity::REFUND][Entity::ID],
                Entity::STATUS    => $status,
                Entity::ACTION    => Base\Action::REFUND);

            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
    }

    public function setMerchantDetailInRequest(&$request, $input)
    {
        $request['merchantID'] = $this->getMerchantID($input[self::TERMINAL]);

        $request['merchantReferenceCode'] = $input[Constants\Entity::PAYMENT][Entity::ID];
    }

    public function getMerchantID($terminal)
    {
        $mid = $terminal[Terminal\Entity::GATEWAY_TERMINAL_ID];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->config[self::TEST_MERCHANT_ID];
        }

        return $mid;
    }

    public function setDebugDetail(&$request)
    {
        $request['clientLibrary'] = 'PHP';
        $request['clientLibraryVersion'] = phpversion();
        $request['clientEnvironment'] = php_uname();
    }

    public function setBillingInfo(&$request, $input)
    {
        $billTo = array();
        $billTo['firstName'] = $input[Constants\Entity::CARD][Card\Entity::NAME];
        $billTo['lastName'] = 'a';
        $billTo['street1'] = 'a';
        $billTo['city'] = 'a';
        $billTo['state'] = 'a';
        $billTo['postalCode'] = '5';
        $billTo['country'] = 'India';
        $billTo[Pay\Entity::EMAIL] = $input[Constants\Entity::PAYMENT][Pay\Entity::EMAIL];

        $request['billTo'] = $billTo;
    }

    public function setCardInfoFromTokenex(&$request, $input)
    {
        $card = array();
        $card[self::ACCOUNT_NUMBER] = Card\Tokenex::getCardNumber($input[Constants\Entity::CARD][Card\Entity::VAULT_TOKEN]);
        $card[self::EXPIRATION_MONTH] = $input[Constants\Entity::CARD][Card\Entity::EXPIRY_MONTH];;
        $card[self::EXPIRATION_YEAR] = $input[Constants\Entity::CARD][Card\Entity::EXPIRY_YEAR];

        $request[Constants\Entity::CARD] = $card;
    }

    public function setPurchaseDetail(&$request, $input)
    {
        $purchaseTotals = array();
        $purchaseTotals[Pay\Entity::CURRENCY] = $input[Constants\Entity::PAYMENT][Pay\Entity::CURRENCY];

        $request['purchaseTotals'] = $purchaseTotals;
    }

    public function setItemDetail(&$request, $input)
    {
        $item = array();
        $item[0][self::UNIT_PRICE] = $input[Constants\Entity::PAYMENT][Entity::AMOUNT ];
        $item[0][Entity::ID] = '1';

        $request[self::ITEM] = $item;
    }

    public function getEnrollRequestObject($input)
    {
        $request = array();

        $this->setMerchantDetailInRequest($request, $input);

        $this->setDebugDetail($request);

        $payerAuthEnrollService = array();

        $payerAuthEnrollService[self::RUN] = 'true';

        $request[self::PAYER_AUTH_ENROLL_SERVICE] = $payerAuthEnrollService;

        $card = array();

        $card[self::ACCOUNT_NUMBER] = $input[Constants\Entity::CARD][Card\Entity::NUMBER];

        $card[self::EXPIRATION_MONTH] = $input[Constants\Entity::CARD][Card\Entity::EXPIRY_MONTH];

        $card[self::EXPIRATION_YEAR] = $input[Constants\Entity::CARD][Card\Entity::EXPIRY_YEAR];

        $request[Constants\Entity::CARD] = $card;

        $this->setPurchaseDetail($request, $input);

        $this->setItemDetail($request, $input);

        return $request;
    }

    protected function decideAuthStepAfterEnroll($enrollResponse, $input)
    {
        switch ($enrollResponse[self::REASON_CODE])
        {
            case Payment\Result::ENROLLED:
                return $this->getFieldsForFormSubmitToBankACS($enrollResponse, $input);

            case Payment\Result::NOT_ENROLLED:
                $payerAuth = $enrollResponse[self::PAYER_AUTH_ENROLL_REPLY];

                $eci = array_key_exists(Entity::ECI, $payerAuth) ? 
                    $payerAuth[Entity::ECI] : '';

                $ucaf = array_key_exists(self::UCAF_COLLECTION_INDICATOR, $payerAuth) ?
                    $payerAuth[self::UCAF_COLLECTION_INDICATOR] : '';

                $network = $input[Constants\Entity::CARD][Card\Entity::NETWORK];
                if (($network === Card\Network::getFullName(Card\Network::VISA)) and 
                    ($eci === '7'))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }
                if (($network === Card\Network::getFullName(Card\Network::MC)) and 
                    (($ucaf === '00') or ($ucaf === '7')))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                }

                return $this->postNotEnrolledAuthorize($input, $enrollResponse);

            default:
                throw new Exception\LogicException(TraceCode::GATEWAY_UNSUPPORTED_CARD_NETWORK);
        }
    }

    protected function getFieldsForFormSubmitToBankACS($enrollResponse, $input)
    {
        $content['TermUrl'] = $input['callbackUrl'];
        $content['MD'] = $input[Constants\Entity::PAYMENT][Entity::ID];
        $content['PaReq'] = $enrollResponse[self::PAYER_AUTH_ENROLL_REPLY]['paReq'];

        $request['content'] = $content;
        $request['url'] = $enrollResponse[self::PAYER_AUTH_ENROLL_REPLY]['acsURL'];
        $request['method'] = 'post';

        return $request;
    }

    public function postGatewayRequest($request, $input)
    {
        $request = json_decode(json_encode($request));

        $soapClient = $this->getSoapClientObject($input);

        $reply = $soapClient->runTransaction($request);

        return json_decode(json_encode($reply), true);;
    }
}
