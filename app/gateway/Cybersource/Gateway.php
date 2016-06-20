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
use Models\Card;
use EE\Error\ErrorCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'cybersource';

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

        $gateway = $this->getRepo()->retrieveByPaymentId($input['payment']['id']);

        $gateway->fill(array('received' => 1));

        $gateway->saveOrFail();

        $repo = $this->getRepo();

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
        if ($response->reasonCode !== Payment\Result::SUCCESS)
        {
            $this->trace->error(
                TraceCode::GATEWAY_VALIDATE_ERROR,
                (array) $response);

            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

            $attributes = array('error_code' => $response->reasonCode);
            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
        else
        {
            $this->trace->info(
                TraceCode::GATEWAY_VALIDATE_RESPONSE,
                (array) $response);

            if ($response->reasonCode !== Payment\Result::SUCCESS)
            {
                throw new Exception\LogicException('Should not rech here.');
            }
            
            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

            $attributes = array(
                'eci' => $response->payerAuthValidateReply->eciRaw,
                'commerce_indicator' => $response->payerAuthValidateReply->commerceIndicator,
                'xid' => $response->payerAuthValidateReply->xid,
                'pares_status' => $response->payerAuthValidateReply->paresStatus);
            
            $network = $input['card']['network'];

            switch ($network)
            {
                case Card\Network::getFullName('VISA'):
                    $attributes['cavv'] = $response->payerAuthValidateReply->cavv;
                    break;

                case Card\Network::getFullName('MC'):
                    $attributes['auth_data'] = $response->payerAuthValidateReply->ucafAuthenticationData;
                    $attributes['collection_indicator'] = $response->payerAuthValidateReply->ucafCollectionIndicator;
                    break;
                
                default:
                    throw new Exception\LogicException('Should not rech here.');
                    break;
            }
            
            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
    }

    protected function postAuthEnrolledRequest($input)
    {
        $request = $this->createAuthEnrolledRequestFields($input);

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterValidate($input, $reply, $request);

            return $reply->reasonCode;

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

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterAuthorize($input, $reply, $request);

            if ($reply->reasonCode !== Payment\Result::SUCCESS)
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

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterNotEnrolledAuthorize($input, $reply, $request);

            if ($reply->reasonCode !== Payment\Result::SUCCESS)
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
        if ($response->reasonCode !== Payment\Result::SUCCESS)
        {
            $this->trace->error(
                TraceCode::GATEWAY_AUTHORIZE_ERROR,
                (array) $response);

            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);
                
            $attributes = array(
                'status' => Payment\Status::AUTHORIZE_FAILED,
                'error_code' => $response->reasonCode);

            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
        else
        {
            $this->trace->info(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
                (array) $response);

            if ($response->reasonCode === Payment\Result::SUCCESS)
            {
                $status = Payment\Status::AUTHORIZED;
            }
            else
            {
                throw new Exception\LogicException('Should not rech here.');
            }

            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

            $attributes = array(
                'ref' => $response->requestID,
                'status' => $status);

            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
    }

    protected function persistAfterAuthorize($input, $response, $request)
    {
        if ($response->reasonCode !== Payment\Result::SUCCESS)
        {
            $this->trace->error(
                TraceCode::GATEWAY_AUTHORIZE_ERROR,
                (array) $response);

            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

            $attributes = array(
                'status' => Payment\Status::AUTHORIZE_FAILED,
                'error_code' => $response->reasonCode);
            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
        else
        {
            $this->trace->info(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
                (array) $response);

            if ($response->reasonCode === Payment\Result::SUCCESS)
            {
                $status = Payment\Status::AUTHORIZED;
            }
            else
            {
                throw new Exception\LogicException('Should not rech here.');
            }

            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

            $attributes = array(
                'ref' => $response->requestID,
                'status' => $status);

            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
    }

    public function createAuthEnrolledRequestFields($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $payerAuthValidateService = new \stdClass();
        $payerAuthValidateService->run = 'true';
        $payerAuthValidateService->signedPARes = $input['gateway']['PaRes'];
        $request->payerAuthValidateService = $payerAuthValidateService;

        $request = $this->setBillingInfo($request, $input);

        $card = new \stdClass();
        $card->accountNumber = Card\Tokenex::getCardNumber($input['card']['vault_token']);
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
        $ccAuthService->run = 'true';

        $repo = $this->getRepo();
        $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

        $ccAuthService->paresStatus = $gateway->pares_status;
        $ccAuthService->xid = $gateway->xid;
        $ccAuthService->commerceIndicator = $gateway->commerce_indicator;
        $ccAuthService->eciRaw = $gateway->eci;
        $ccAuthService->reconciliationID = $input['payment']['id'];
        $network = $input['card']['network'];

        switch ($network)
        {
            case Card\Network::getFullName('VISA'):
                $ccAuthService->cavv = $gateway->cavv;
                break;

            case Card\Network::getFullName('MC'):
                $ucaf = new \stdClass();
                $ucaf->authenticationData = $gateway->auth_data;
                $ucaf->collectionIndicator = $gateway->collection_indicator;

                $request->ucaf = $ucaf;
                break;
            
            default:
                throw new Exception\LogicException('Should not rech here.');
                break;
        }

        $request->ccAuthService = $ccAuthService;

        $request = $this->setBillingInfo($request, $input);

        $card = new \stdClass();
        $card->accountNumber = Card\Tokenex::getCardNumber($input['card']['vault_token']);
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
        $ccAuthService->run = 'true';

        $network = $input['card']['network'];

        switch ($network)
        {
            case Card\Network::getFullName('VISA'):
                $ccAuthService->eci = $enrollResponse->payerAuthEnrollReply->eci;
                break;

            case Card\Network::getFullName('MC'):
                $ucaf = new \stdClass();
                $ucaf->collectionIndicator = $enrollResponse->payerAuthEnrollReply->ucafCollectionIndicator;
                $ccAuthService->ucaf = $ucaf;
                break;
            
            default:
                throw new Exception\LogicException('Should not rech here.');
                break;
        }
        
        $ccAuthService->commerceIndicator = $enrollResponse->payerAuthEnrollReply->commerceIndicator;
        $ccAuthService->veresEnrolled = $enrollResponse->payerAuthEnrollReply->veresEnrolled;
        $ccAuthService->reconciliationID = $input['payment']['id'];

        $request->ccAuthService = $ccAuthService;

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

    public function capture(array $input)
    {
        $request = $this->createCaptureRequestFields($input);

        $this->trace->info(
                TraceCode::GATEWAY_CAPTURE_REQUEST,
                (array) $request);

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterCapture($input, $reply, $request);

            if ($reply->reasonCode != Payment\Result::SUCCESS)
            {
                throw new Exception\BadRequestException(ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED);
            }

            return $reply->reasonCode;
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Capture request failed.', null, $exception);
        }
    }

    public function createCaptureRequestFields($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = 'true';

        $repo = $this->getRepo();
        $gateway = $repo->retrieveByPaymentId($input['payment']['id']);
        $ccCaptureService->authRequestID = $gateway->ref;
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

        try
        {
            $reply = $this->postGatewayRequest($request, $input);

            $this->persistAfterRefund($input, $reply, $request);

            return $reply->reasonCode;
        }
        catch (SoapFault $exception)
        {
            throw new Exception\RuntimeException(
                'Refund request failed.', null, $exception);
        }
    }

    public function createRefundRequestFields($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $ccCreditService = new \stdClass();
        $ccCreditService->run = 'true';

        $repo = $this->getRepo();
        $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

        $ccCreditService->captureRequestID = $gateway->capture_ref;
        $request->ccCreditService = $ccCreditService;

        $request = $this->setPurchaseDetail($request, $input);

        $request = $this->setItemDetail($request, $input);

        return $request;
    }

    public function getSoapClientObject($input)
    {
        $url = $this->getWsdlFile();

        $auth = array(
            'username' => $input['terminal']['gateway_terminal_id'],
            'password' => $input['terminal']['gateway_terminal_password']);
        $soapClient = new ExtendedClient($url, array(), $auth);

        return $soapClient;
    }

    public function getWsdlFile()
    {
        return dirname(__FILE__) .'/cybs.wsdl.xml';
    }

    public function enroll($input)
    {
        $request = $this->getEnrollRequestObject($input);

        $this->trace->error(
                TraceCode::GATEWAY_ENROLL_REQUEST,
                (array) $request);

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
        if (($response->reasonCode !== Payment\Result::ENROLLED) and 
            ($response->reasonCode !== Payment\Result::SUCCESS))
        {
            $this->trace->error(
                TraceCode::GATEWAY_ENROLL_ERROR,
                (array) $response);

            $attributes = array(
                'payment_id'            => $input['payment']['id'],
                'amount'                => $request->item[0]->unitPrice,
                'error_code'            => $response->reasonCode,
                'status'                => Payment\Status::CREATED);

            $this->getRepo()->createOrFail($attributes);
        }
        else
        {
            $this->trace->info(
                TraceCode::GATEWAY_ENROLL_RESPONSE,
                (array) $response);

            $attributes = array(
                'payment_id'                => $input['payment']['id'],
                'amount'                    => $request->item[0]->unitPrice,
                'status'                    => Payment\Status::CREATED,
                'ref'                       => $response->requestID);

            $this->getRepo()->createOrFail($attributes);
        }
    }

    protected function persistAfterCapture($input, $response, $request)
    {
        if ($response->reasonCode !== Payment\Result::SUCCESS)
        {
            $this->trace->error(
                TraceCode::GATEWAY_CAPTURE_ERROR,
                (array) $response);

            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

            $attributes = array(
                'status' => Payment\Status::CAPTURE_FAILED,
                'error_code' => $response->reasonCode,
                'action' => Base\Action::CAPTURE);
            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
        else
        {
            $this->trace->info(
                TraceCode::GATEWAY_CAPTURE_RESPONSE,
                (array) $response);

            if ($response->reasonCode === Payment\Result::SUCCESS)
            {
                $status = Payment\Status::CAPTURED;
            }
            else
            {
                throw new Exception\LogicException('Should not rech here.');
            }

            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

            $attributes = array(
                'capture_ref' => $response->requestID,
                'status' => $status,
                'action' => Base\Action::CAPTURE);
            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
    }

    protected function persistAfterRefund($input, $response, $request)
    {
        if ($response->reasonCode !== Payment\Result::SUCCESS)
        {
            $this->trace->error(
                TraceCode::GATEWAY_REFUND_ERROR,
                (array) $response);

            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

            $attributes = array(
                'error_code' => $response->reasonCode,
                'action' => Base\Action::REFUND);

            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
        else
        {
            $this->trace->info(
                TraceCode::GATEWAY_REFUND_RESPONSE,
                (array) $response);

            if ($response->reasonCode === Payment\Result::SUCCESS)
            {
                $status = Payment\Status::REFUNDED;
            }
            else
            {
                throw new Exception\LogicException('Should not rech here.');
            }

            $repo = $this->getRepo();

            $gateway = $repo->retrieveByPaymentId($input['payment']['id']);

            $attributes = array(
                'refund_id' => $input['refund']['id'],
                'status' => $status,
                'action' => Base\Action::REFUND);
            $gateway->fill($attributes);
            $gateway->saveOrFail();
        }
    }

    public function setMerchantDetailInRequest($request, $input)
    {
        $request->merchantID = $this->getMerchantID($input['terminal']);

        $request->merchantReferenceCode = $input['payment']['id'];

        return $request;
    }

    public function getMerchantID($terminal)
    {
        $mid = $terminal['gateway_merchant_id'];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->config['test_merchant_id'];
        }

        return $mid;
    }

    public function setDebugDetail($request)
    {
        $request->clientLibrary = 'PHP';
        $request->clientLibraryVersion = phpversion();
        $request->clientEnvironment = php_uname();

        return $request;
    }

    public function setBillingInfo($request, $input)
    {
        $billTo = new \stdClass();
        $billTo->firstName = $input['card']['name'];
        $billTo->lastName = 'a';
        $billTo->street1 = 'a' ;
        $billTo->city = 'a';
        $billTo->state = 'a';
        $billTo->postalCode = '5';
        $billTo->country = 'India';
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
        $item0->id = '1';
        $request->item = array($item0);

        return $request;
    }

    public function getEnrollRequestObject($input)
    {
        $request = new \stdClass();

        $request = $this->setMerchantDetailInRequest($request, $input);

        $request = $this->setDebugDetail($request);

        $payerAuthEnrollService = new \stdClass();
        $payerAuthEnrollService->run = 'true';
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
                    $payerAuth = $enrollResponse->payerAuthEnrollReply;

                    $eci = property_exists($payerAuth, 'eci') ? 
                        $payerAuth->eci : '';

                    $ucaf = property_exists($payerAuth, 'ucafCollectionIndicator') ?
                        $payerAuth->ucafCollectionIndicator : '';

                    $network = $input['card']['network'];
                    if (($network === Card\Network::getFullName('VISA')) and ($eci === '7'))
                    {
                        throw new Exception\BadRequestException(ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                    }
                    if (($network === Card\Network::getFullName('MC')) and (($ucaf === '00') or ($ucaf === '7')))
                    {
                        throw new Exception\BadRequestException(ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
                    }

                    return $this->postNotEnrolledAuthorize($input, $enrollResponse);
                }

            default:
                throw new Exception\LogicException('Should not have reached here');
        }
    }

    protected function getFieldsForFormSubmitToBankACS($enrollResponse, $input)
    {
        $content['TermUrl'] = $input['callbackUrl'];
        $content['MD'] = $input['payment']['id'];
        $content['PaReq'] = $enrollResponse->payerAuthEnrollReply->paReq;

        $request['content'] = $content;
        $request['url'] = $enrollResponse->payerAuthEnrollReply->acsURL;
        $request['method'] = 'post';

        return $request;
    }

    public function postGatewayRequest($request, $input)
    {
        $soapClient = $this->getSoapClientObject($input);

        $reply = $soapClient->runTransaction($request);

        return $reply;
    }
}
