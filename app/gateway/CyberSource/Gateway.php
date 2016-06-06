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
        \Session::push('card', $input['card']['number']);

        $status = $this->enroll($input);
        
        return $this->decideAuthStepAfterEnroll($status);
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

    public function postAuthEnrolledRequest($input)
    {
        $request = $this->createAuthEnrolledRequestFields($input);

        try {
            $soapClient = new ExtendedClient($_ENV['CYBERSOUREC_GATEWAY_TEST_WSDL_URL'], array());

            $reply = $soapClient->runTransaction($request);

            $this->authValidateResponse = $reply;

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
            $soapClient = new ExtendedClient($_ENV['CYBERSOUREC_GATEWAY_TEST_WSDL_URL'], array());

            $reply = $soapClient->runTransaction($request);

            $this->authorizeResponse = $reply;

            if($reply->reasonCode === 100)
            {
                return true;
            }
            else
            {
                throw new Exception\InvalidArgumentException(
                    'Authorization failed. Reason code: '.$reply->reasonCode);
            }

        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

    public function createAuthEnrolledRequestFields($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $ccAuthService = new \stdClass();
        $ccAuthService->run = "true";
        $request->ccAuthService = $ccAuthService;

        $payerAuthValidateService = new \stdClass();
        $payerAuthValidateService->run = "true";
        $payerAuthValidateService->signedPARes = $input['gateway']['PaRes'];
        
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
        $request->ccAuthService = $ccAuthService;

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

    public function capture(array $input)
    {
        $this->setId($input['payment']['id']);

        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);
        
        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";
        $this->model = $this->repo->retrieveByPaymentIdAndStatus(
                                            $input['payment']['id'], 'enrolled');
        $ccCaptureService->authRequestID = $this->model->ref;
        $request->ccCaptureService = $ccCaptureService;

        // $payerAuthEnrollService = new \stdClass();
        // $payerAuthEnrollService->run = "true";
        // $request->payerAuthEnrollService = $payerAuthEnrollService;

        $card = new \stdClass();
        $cards = \Session::get('card');
        $card->accountNumber = $cards[0];
        $card->expirationMonth = $input['card']['expiry_month'];
        $card->expirationYear = $input['card']['expiry_year'];
        $request->card = $card;
        
        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);

        $this->captureRequest = $request;

        try {
            $soapClient = new ExtendedClient($_ENV['CYBERSOUREC_GATEWAY_TEST_WSDL_URL'], array());

            $reply = $soapClient->runTransaction($request);

            $this->captureResponse = $reply;
            
            $this->persistAfterCapture();

            return $reply->reasonCode;

        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

    public function refund(array $input)
    {
        $this->input = $input;
        $this->action = Action::REFUND;

        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $ccCreditService = new stdClass();
        $ccCreditService->run = "true";
        $this->model = $this->repo->retrieveByPaymentIdAndStatus(
                                            $input['payment']['id'], 'captured');
        $ccCreditService->captureRequestID = $this->model->capture_ref;
        $request->ccCreditService = $ccCreditService;

        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);

        try {
            $soapClient = new ExtendedClient($_ENV['CYBERSOUREC_GATEWAY_TEST_WSDL_URL'], array());

            $reply = $soapClient->runTransaction($request);

            $this->refundResponse = $reply;

            return $reply->reasonCode;

        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

    public function verify(array $input)
    {
        $this->input = $input;
        $this->action = Action::VERIFY;

        $request = new \stdClass();

        $request->type = 'transaction';
        $request->subtype = 'transactionDetail';
        $request->merchantID = $_ENV['CYBERSOURCE_GATEWAY_TEST_MERCHANT_ID'];
        $request->requestID = '4649570076396291201016';

        try {
            $soapClient = new ExtendedClient('https://ebctest.cybersource.com/ebctest/Query', array());

            $reply = $soapClient->runTransaction($request);

            var_dump($reply);die;

            return $reply->reasonCode;

        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

    public function enroll($input)
    {
        $this->setId($input['payment']['id']);

        $this->callbackUrl = $input['callbackUrl'];

        $request = $this->getEnrollRequestObject($input);

        $this->enrollRequest = $request;

        try {
            $soapClient = new ExtendedClient($_ENV['CYBERSOUREC_GATEWAY_TEST_WSDL_URL'], array());

            $reply = $soapClient->runTransaction($request);

            $this->enrollResponse = $reply;

            $this->persistAfterEnroll();

            return $reply->reasonCode;

        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

    protected function persistAfterEnroll()
    {
        if (($this->enrollResponse->reasonCode !== 475) && ($this->enrollResponse->reasonCode !== 100))
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_ENROLL_ERROR,
                (array) $this->enrollResponse);

            $this->model = $this->repo->persistAfterEnrollError(
                            $this->id,
                            $this->enrollResponse,
                            $this->enrollRequest);

            $this->id = $this->model->id;
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_ENROLL_RESPONSE,
                (array) $this->enrollResponse);

            $this->model = $this->repo->persistAfterEnroll($this->id,
                    $this->enrollRequest,
                    $this->enrollResponse);
        }
    }

    protected function persistAfterCapture()
    {
        if ($this->captureResponse->reasonCode !== 100)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_CAPTURE_ERROR,
                (array) $this->captureResponse);

            $this->model = $this->repo->persistAfterCaptureError(
                            $this->id,
                            $this->captureResponse,
                            $this->captureRequest);

            $this->id = $this->model->id;
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_CAPTURE_RESPONSE,
                (array) $this->captureResponse);

            $this->model = $this->repo->persistAfterCapture($this->id,
                    $this->captureRequest,
                    $this->captureResponse);
        }
    }

    protected function trace($level, $message, array $context)
    {
        $this->trace->addRecord($level, $message, $context);
    }

    protected function setId($id)
    {
        $this->id = $id;
    }

    public function setMerchantDetailInRequest($request, $input)
    {
        $request->merchantID = $_ENV['CYBERSOURCE_GATEWAY_TEST_MERCHANT_ID'];

        $request->merchantReferenceCode = $input['card']['merchant_id'];

        return $request;
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

        $ccAuthService = new \stdClass();
        $ccAuthService->run = "true";
        $request->ccAuthService = $ccAuthService;

        $payerAuthEnrollService = new \stdClass();
        $payerAuthEnrollService->run = "true";
        $request->payerAuthEnrollService = $payerAuthEnrollService;

        $request = $this->setBillingInfo($request, $input);
        
        $card = new \stdClass();
        $card->accountNumber = $input['card']['number'];
        $card->expirationMonth = $input['card']['expiry_month'];
        $card->expirationYear = $input['card']['expiry_year'];
        $request->card = $card;

        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);
        
        return $request;
    }

    protected function decideAuthStepAfterEnroll($enrollStatus)
    {
        switch ($enrollStatus)
        {
            case Payment\Result::ENROLLED:
                return $this->getFieldsForFormSubmitToBankACS();

            case Payment\Result::NOT_ENROLLED:
                return;

            default:
                throw new Exception\LogicException('Should not have reached here');
        }
    }

    protected function getFieldsForFormSubmitToBankACS()
    {
        $enrollResponse = $this->enrollResponse;

        $content['TermUrl'] = $this->callbackUrl;
        $content['MD'] = $this->id;
        $content['PaReq'] = $this->enrollResponse->payerAuthEnrollReply->paReq;

        $request['content'] = $content;
        $request['url'] = $this->enrollResponse->payerAuthEnrollReply->acsURL;
        $request['method'] = 'post';

        return $request;
    }

}
