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
use ExtendedClient;
use Session;

class Gateway extends Base\Gateway
{
    protected $gateway = 'cybersource';

    protected $repo;

    protected $enrollRequest;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository();
    }

    public function authorize(array $input)
    {
        \Session::forget('card');
        \Session::forget('cvv');
        \Session::push('card', $input['card']['number']);
        \Session::push('cvv', $input['card']['cvv']);

        $reply = $this->enroll($input);

        
        
        return $this->decideAuthStepAfterEnroll($reply, $input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->id = $input['payment']['id'];

        $status = $this->postAuthEnrolledRequest($input);

        if($status === 100)
        {
            $this->postEnrollAuthorize($input);
        }
        else
        {
            throw new Exception\InvalidArgumentException(
                'Auth Validation failed, try another card or payment option. Reason code: '.$status);
        }
    }

    public function persistAfterValidate($input, $response, $request)
    {
        if ($response->reasonCode !== 100)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_VALIDATE_ERROR,
                (array) $response);

            $this->model = $this->repo->persistAfterValidateError(
                $input['payment']['id'],
                $response,
                $request, 
                $input['card']['network']);
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_VALIDATE_RESPONSE,
                (array) $response);

            $this->model = $this->repo->persistAfterValidate(
                $input['payment']['id'],
                $request,
                $response,
                $input['card']['network']);

        }
    }

    public function postAuthEnrolledRequest($input)
    {
        $request = $this->createAuthEnrolledRequestFields($input);

        try {
            $soapClient = $this->getSoapClientObject();

            $reply = $soapClient->runTransaction($request);

            $this->persistAfterValidate($input, $reply, $request);

            return $reply->reasonCode;

        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

    public function postEnrollAuthorize($input)
    {
        $request = $this->createAuthorizeRequestFields($input);

        try {
            $soapClient = $this->getSoapClientObject();

            $reply = $soapClient->runTransaction($request);

            
            $this->persistAfterAuthorize($input, $reply, $request);

            if($reply->reasonCode !== 100)
            {
                throw new Exception\InvalidArgumentException(
                    'Authorization failed. Reason code: '.$reply->reasonCode);
            }
        } catch (SoapFault $exception) {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_AUTHORIZE_ERROR,
                (array) $exception);
        }
    }

    public function postNotEnrolledAuthorize($input, $enrollResponse)
    {
        $request = $this->createNotEnrolledAuthorizeRequestFields($input, $enrollResponse);

        try {
            $soapClient = $this->getSoapClientObject();

            $reply = $soapClient->runTransaction($request);

            $this->persistAfterNotEnrolledAuthorize($input, $reply, $request);

            if($reply->reasonCode !== 100)
            {
                throw new Exception\InvalidArgumentException(
                    'Authorization failed. Reason code: '.$reply->reasonCode);
            }
        } catch (SoapFault $exception) {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_AUTHORIZE_ERROR,
                (array) $exception);
        }
    }

    public function persistAfterNotEnrolledAuthorize($input, $response, $request)
    {
        if ($response->reasonCode !== 100)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_AUTHORIZE_ERROR,
                (array) $response);

            $this->model = $this->repo->persistAfterNotEnrolledAuthorizeError(
                $input['payment']['id'],
                $response,
                $request);
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
                (array) $response);

            $this->model = $this->repo->persistAfterNotEnrolledAuthorize(
                $input['payment']['id'],
                $request,
                $response);

        }
    }

    public function persistAfterAuthorize($input, $response, $request)
    {
        if ($response->reasonCode !== 100)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_AUTHORIZE_ERROR,
                (array) $response);

            $this->model = $this->repo->persistAfterAuthorizeError(
                $input['payment']['id'],
                $response,
                $request);
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
                (array) $response);

            $this->model = $this->repo->persistAfterAuthorize(
                $input['payment']['id'],
                $request,
                $response);

        }
    }

    public function createAuthEnrolledRequestFields($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $payerAuthValidateService = new \stdClass();
        $payerAuthValidateService->run = "true";
        $payerAuthValidateService->signedPARes = $input['gateway']['PaRes'];
        $request->payerAuthValidateService = $payerAuthValidateService;
        
        $request = $this->setBillingInfo($request, $input);

        $card = new \stdClass();
        $cards = \Session::get('card');
        $card->accountNumber = $cards[0];
        $card->expirationMonth = $input['card']['expiry_month'];
        $card->expirationYear = $input['card']['expiry_year'];
        $request->card = $card;

        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);

        return $request;
    }

    public function createAuthorizeRequestFields($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $ccAuthService = new \stdClass();
        $ccAuthService->run = "true";

        $this->model = $this->repo->retrieveByPaymentIdAndStatus(
                                            $input['payment']['id'], 'enrolled');

        $ccAuthService->paresStatus = $this->model->pares_status;
        $ccAuthService->xid = $this->model->xid;
        $ccAuthService->commerceIndicator = $this->model->commerce_indicator;
        $ccAuthService->eciRaw = $this->model->eci_raw;
        $ccAuthService->reconciliationID = $this->merchantReferenceCode();
        if($input['card']['network'] === 'Visa')
        {
            $ccAuthService->cavv = $this->model->cavv;
        }
        // if($input['card']['network'] === 'Mastercard')
        // {

        // }
        $request->ccAuthService = $ccAuthService;

        $request = $this->setBillingInfo($request, $input);

        $card = new \stdClass();
        $cards = \Session::pull('card');
        $cvv = \Session::pull('cvv');
        $card->accountNumber = $cards[0];
        $card->cvNumber = $cvv[0];
        $card->expirationMonth = $input['card']['expiry_month'];
        $card->expirationYear = $input['card']['expiry_year'];
        $request->card = $card;

        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);

        return $request;
    }

    public function createNotEnrolledAuthorizeRequestFields($input, $enrollResponse)
    {

        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $ccAuthService = new \stdClass();
        $ccAuthService->run = "true";
        if($input['card']['network'] === 'Visa')
        {
            $ccAuthService->eci = $enrollResponse->payerAuthEnrollReply->eci;
        }
        if($input['card']['network'] === 'MasterCard')
        {
            $ucaf = new \stdClass();
            $ucaf->collectionIndicator = $enrollResponse->payerAuthEnrollReply->ucafCollectionIndicator;
            $ccAuthService->ucaf = $ucaf;
        }
        $ccAuthService->commerceIndicator = $enrollResponse->payerAuthEnrollReply->commerceIndicator;
        $ccAuthService->veresEnrolled = $enrollResponse->payerAuthEnrollReply->veresEnrolled;
        $ccAuthService->reconciliationID = $this->merchantReferenceCode();
        
        $request->ccAuthService = $ccAuthService;

        $request = $this->setBillingInfo($request, $input);

        $card = new \stdClass();
        $cards = \Session::pull('card');
        $cvv = \Session::pull('cvv');
        $card->accountNumber = $cards[0];
        $card->cvNumber = $cvv[0];
        $card->expirationMonth = $input['card']['expiry_month'];
        $card->expirationYear = $input['card']['expiry_year'];
        $request->card = $card;

        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);

        return $request;
    }

    public function capture(array $input)
    {
        $request = $this->createCaptureRequestFields($input);

        try {
            $soapClient = $this->getSoapClientObject();

            $reply = $soapClient->runTransaction($request);

            $this->persistAfterCapture($input, $reply, $request);

            if($reply->reasonCode != Payment\Result::CAPTURED)
            {
                throw new Exception\LogicException("Capture failed! Reason code: ".$reply->reasonCode, 1);
                
            }

            return $reply->reasonCode;

        } catch (SoapFault $exception) {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_CAPTURE_ERROR,
                (array) $exception);
        }
    }

    public function createCaptureRequestFields($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);
        
        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";
        $this->model = $this->repo->retrieveByPaymentIdAndStatus(
                                            $input['payment']['id'], 'authorized');
        $ccCaptureService->authRequestID = $this->model->ref;
        $request->ccCaptureService = $ccCaptureService;

        $card = new \stdClass();
        $card->expirationMonth = $input['card']['expiry_month'];
        $card->expirationYear = $input['card']['expiry_year'];
        $request->card = $card;
        
        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);

        return $request;
    }

    public function refund(array $input)
    {
        $request = $this->createRefundRequestFields($input);

        try {
            $soapClient = $this->getSoapClientObject();

            $reply = $soapClient->runTransaction($request);

            return $reply->reasonCode;

        } catch (SoapFault $exception) {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_REFUND_ERROR,
                (array) $exception);
        }
    }

    public function createRefundRequestFields($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $ccCreditService = new \stdClass();
        $ccCreditService->run = "true";
        $this->model = $this->repo->retrieveByPaymentIdAndStatus(
                                            $input['payment']['id'], 'captured');
        $ccCreditService->captureRequestID = $this->model->capture_ref;
        $request->ccCreditService = $ccCreditService;

        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);

        return $request;
    }

    public function getSoapClientObject()
    {
        $url = $_ENV['CYBERSOURCE_GATEWAY_TEST_WSDL_URL'];
        if($this->mode === 'live')
        {
            $url = $_ENV['CYBERSOURCE_GATEWAY_LIVE_WSDL_URL'];
        }

        $soapClient = new ExtendedClient($url, array());

        return $soapClient;
    } 

    public function verify(array $input)
    {
        $request = new \stdClass();

        $request->type = 'transaction';
        $request->subtype = 'transactionDetail';
        $request->merchantID = $this->getMerchantID();
        $request->requestID = '4649570076396291201016';

        try {
            $soapClient = new ExtendedClient('https://ebctest.cybersource.com/ebctest/Query', array());

            $reply = $soapClient->runTransaction($request);

            return $reply->reasonCode;

        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

    public function enroll($input)
    {
        $this->callbackUrl = $input['callbackUrl'];

        $request = $this->getEnrollRequestObject($input);

        try {
            $soapClient = $this->getSoapClientObject();

            $reply = $soapClient->runTransaction($request);

            $this->persistAfterEnroll($input, $reply, $request);

            return $reply;

        } catch (SoapFault $exception) {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_ENROLL_ERROR,
                (array) $exception);
        }
    }

    protected function persistAfterEnroll($input, $response, $request)
    {
        if (($response->reasonCode !== 475) && ($response->reasonCode !== 100))
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_ENROLL_ERROR,
                (array) $response);

            $this->model = $this->repo->persistAfterEnrollError(
                $input['payment']['id'],
                $response,
                $request);
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_ENROLL_RESPONSE,
                (array) $response);

            $this->model = $this->repo->persistAfterEnroll(
                $input['payment']['id'],
                $request,
                $response);
        }
    }

    protected function persistAfterCapture($input, $response, $request)
    {
        if ($response->reasonCode !== 100)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_CAPTURE_ERROR,
                (array) $response);

            $this->model = $this->repo->persistAfterCaptureError(
                $input['payment']['id'],
                $response,
                $request);

        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_CAPTURE_RESPONSE,
                (array) $response);

            $this->model = $this->repo->persistAfterCapture(
                $input['payment']['id'],
                $request,
                $response);
        }
    }

    protected function trace($level, $message, array $context)
    {
        $this->trace->addRecord($level, $message, $context);
    }

    
    public function setMerchantDetailInRequest($request, $input)
    {
        $request->merchantID = $this->getMerchantID();

        $request->merchantReferenceCode = $this->merchantReferenceCode();

        return $request;
    }

    public function getMerchantID()
    {
        $mid = $_ENV['CYBERSOURCE_GATEWAY_TEST_MERCHANT_ID'];

        if($this->mode === 'live')
        {
            $mid = $_ENV['CYBERSOURCE_GATEWAY_LIVE_MERCHANT_ID'];
        }

        return $mid;
    }

    public function merchantReferenceCode()
    {
        $mid = $_ENV['CYBERSOURCE_GATEWAY_TEST_MERCHANT_REFERENCE_CODE'];

        if($this->mode === 'live')
        {
            $mid = $_ENV['CYBERSOURCE_GATEWAY_LIVE_MERCHANT_REFERENCE_CODE'];
        }

        return $mid;
    }

    public function setDebugDetail($request)
    {
        $request->clientLibrary = "PHP";
        $request->clientLibraryVersion = phpversion();
        $request->clientEnvironment = php_uname();

        return $request;
    }

    public function setBillingInfo($request, $input)
    {
        $billTo = new \stdClass();
        $billTo->firstName = $input['card']['name'];
        $billTo->lastName = "a";
        $billTo->street1 = "a" ;
        $billTo->city = "a";
        $billTo->state = "a";
        $billTo->postalCode = "5";
        $billTo->country = "India";
        $billTo->email = $input['payment']['email'];
        $request->billTo = $billTo;

        return $request;
    }

    public function setPurchaseDetail($request, $input)
    {
        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $input['payment']['currency'];
        $request->purchaseTotals = $purchaseTotals;

        return $request;
    }

    public function setItemDetail($request, $input)
    {
        $item0 = new \stdClass();
        $item0->unitPrice = $input['payment']['amount'];
        $item0->id = "1";  
        $request->item = array($item0);

        return $request;
    }

    public function getEnrollRequestObject($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $payerAuthEnrollService = new \stdClass();
        $payerAuthEnrollService->run = "true";
        $request->payerAuthEnrollService = $payerAuthEnrollService;

        $card = new \stdClass();
        $card->accountNumber = $input['card']['number'];
        $card->expirationMonth = $input['card']['expiry_month'];
        $card->expirationYear = $input['card']['expiry_year'];
        $request->card = $card;

        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);

        return $request;
    }

    protected function decideAuthStepAfterEnroll($enrollResponse, $input)
    {
        switch ($enrollResponse->reasonCode)
        {
            case Payment\Result::ENROLLED:
                return $this->getFieldsForFormSubmitToBankACS($enrollResponse, $input);

            case Payment\Result::NOT_ENROLLED:
                {
                    $eci = property_exists($enrollResponse->payerAuthEnrollReply, 'eci') ? $enrollResponse->payerAuthEnrollReply->eci : "";
                    $ucaf = property_exists($enrollResponse->payerAuthEnrollReply, 'ucafCollectionIndicator') ? $enrollResponse->payerAuthEnrollReply->ucafCollectionIndicator : "";
                    if(($input['card']['network'] === 'Visa') AND ($eci === "07")) 
                    {
                        throw new Exception\LogicException('Card cannot be processed, please try another card or payment method.');
                    } 
                    if(($input['card']['network'] === 'MasterCard') AND ($ucaf === "0"))
                    {
                        throw new Exception\LogicException('Card cannot be processed, please try another card or payment method.');
                    } 
                    
                    return $this->postNotEnrolledAuthorize($input, $enrollResponse);
                }

            default:
                throw new Exception\LogicException('Should not have reached here');
        }
    }

    protected function getFieldsForFormSubmitToBankACS($enrollResponse, $input)
    {
        $content['TermUrl'] = $this->callbackUrl;
        $content['MD'] = $input['payment']['id'];
        $content['PaReq'] = $enrollResponse->payerAuthEnrollReply->paReq;

        $request['content'] = $content;
        $request['url'] = $enrollResponse->payerAuthEnrollReply->acsURL;
        $request['method'] = 'post';

        return $request;
    }

}
