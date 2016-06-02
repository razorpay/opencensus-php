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

class Gateway extends Base\Gateway
{
    protected $gateway = 'cybersource';

    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository();
    }

    public function authorize(array $input)
    {
        $status = $this->enroll($input);
        
        return $this->decideAuthStepAfterEnroll('475');
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $network = $input['card']['network'];

        //$this->validateCallbackGatewayFields($input, $network);

        $this->id = $input['payment']['id'];

        $this->model = $this->repo->findByGatewayTransactionIdOrFail(
            $input['gateway']['MD']);

        $paymentId = $this->model->getPaymentId();

        if ($this->id !== $paymentId)
        {
            throw new Exception\LogicException(
                'app payment '. $this->id . ' should be equal to payment id . '. $paymentId);
        }

        $this->postAuthEnrolledRequest($input);
    }

    public function capture(array $input)
    {
    }

    public function refund(array $input)
    {
        $this->input = $input;
        $this->action = Action::REFUND;
    }

    public function verify(array $input)
    {
        $this->input = $input;
        $this->action = Action::VERIFY;
    }

    public function enroll($input)
    {
        $this->setId($input['payment']['id']);

        $this->callbackUrl = $input['callbackUrl'];

        $request = $this->getEnrollRequestObject($input);

        try {
            $soapClient = new ExtendedClient($_ENV['CYBERSOUREC_GATEWAY_TEST_WSDL_URL'], array());

            $reply = $soapClient->runTransaction($request);

            $this->enrollResponse = $reply;

            return $reply->reasonCode;

        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

    protected function setId($id)
    {
        $this->id = $id;
    }

    public function getEnrollRequestObject($input)
    {
        $request = new \stdClass();

            $request->merchantID = $_ENV['CYBERSOURCE_GATEWAY_TEST_MERCHANT_ID'];

            $request->merchantReferenceCode = $input['card']['merchant_id'];

            $request->clientLibrary = "PHP";
            $request->clientLibraryVersion = phpversion();
            $request->clientEnvironment = php_uname();

            $ccAuthService = new \stdClass();
            $ccAuthService->run = "true";
            $request->ccAuthService = $ccAuthService;

            $payerAuthEnrollService = new \stdClass();
            $payerAuthEnrollService->run = "true";
            $request->payerAuthEnrollService = $payerAuthEnrollService;
            
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

            $card = new \stdClass();
            $card->accountNumber = $input['card']['number'];
            $card->expirationMonth = $input['card']['expiry_month'];
            $card->expirationYear = $input['card']['expiry_year'];
            $request->card = $card;

            $purchaseTotals = new \stdClass();
            $purchaseTotals->currency = $input['payment']['currency'];
            $request->purchaseTotals = $purchaseTotals;

            $item0 = new \stdClass();
            $item0->unitPrice = $input['payment']['amount'];
            $item0->id = "1";

            $request->item = array($item0);

            return $request;
    }

    protected function decideAuthStepAfterEnroll($enrollStatus)
    {
        switch ($enrollStatus)
        {
            case Payment\Result::ENROLLED:
                return $this->getFieldsForFormSubmitToBankACS();

            case Payment\Result::NOT_ENROLLED:
                return $this->postAuthNotEnrolledRequestToBank();

            case Payment\Result::INITIALIZED:
                return $this->getFieldsForFormSubmitForRupay();

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
