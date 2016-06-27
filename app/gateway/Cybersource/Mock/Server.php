<?php
 
namespace Gateway\Cybersource\Mock;
 
use EE\Exception;
use Gateway\Base;
use Models\Card;
use Models\Payment;
use App;
use Http;
use Gateway\Cybersource\Payment as Pay;
 
class Server extends Base\Mock\Server
{
    protected $repo;

    public function getGatewayResponse($request)
    {
        if (isset($request->payerAuthEnrollService))
        {
            $this->validateEnrollInput(json_decode(json_encode($request), true));

            return $this->getEnrollResponse($request);
        }

        if (isset($request->payerAuthValidateService))
        {
            return $this->postAuthEnrolledRequest($request);
        }

        if (isset($request->ccAuthService))
        {
            $this->validateAuthorizeInput(json_decode(json_encode($request), true));

            return $this->postEnrollAuthorize($request);
        }

        if (isset($request->ccCaptureService))
        {
            return $this->getCaptureResponsse($request);
        }

        if (isset($request->ccCreditService))
        {
            return $this->getRefundResponse($request);
        }
    }

    public function getRefundResponse($request)
     {
        $response = new \stdClass();

        $response->decision = 'ACCEPT';
        $response->reasonCode = Pay\Result::SUCCESS;
        $response->requestID = '4661549029556297301014';
        $response->merchantReferenceCode = 'razorpay';

        $ccCreditReply = new \stdClass();
        $ccCreditReply->reconciliationID = 'razorpay';

        $response->ccCreditReply = $ccCreditReply;
        $response->ccCreditReply = $ccCreditReply;

        return $response;
     }

    public function getCaptureResponsse($request)
     {
        $response = new \stdClass();

        $response->decision = 'ACCEPT';
        $response->reasonCode = Pay\Result::SUCCESS;
        $response->requestID = '4661468455476856801016';

        $ccCaptureReply = new \stdClass();
        $ccCaptureReply->reconciliationID = 'razorpay';

        $response->ccCaptureReply = $ccCaptureReply;

        return $response;
     }

     public function postAuthEnrolledRequest($request)
     {
        $response = new \stdClass();

        $response->decision = 'ACCEPT';
        $response->reasonCode = Pay\Result::SUCCESS;

        $payerAuthValidateReply = new \stdClass();
        $payerAuthValidateReply->eciRaw = '05';
        $payerAuthValidateReply->xid = 'TktUb3hwZVp0eTMxcTh5UlZUODA=';
        $payerAuthValidateReply->paresStatus = 'Y';
        $payerAuthValidateReply->commerceIndicator = 'Internet';
        $payerAuthValidateReply->cavv = '1';

        $response->payerAuthValidateReply = $payerAuthValidateReply;

        return $response;
     }

    public function postEnrollAuthorize($request)
    {
        $response = new \stdClass();

        $response->decision = 'ACCEPT';
        $response->reasonCode = Pay\Result::SUCCESS;
        $response->requestID = '4661454138166750401020';

        $ccAuthReply = new \stdClass();
        $ccAuthReply->reconciliationID = 'razorpay';

        $response->ccAuthReply = $ccAuthReply;

        return $response;
    }
 
    public function getEnrollResponse($request)
    {
        $response = new \stdClass();

        $payerAuthEnrollReply = new \stdClass();
        $response->payerAuthEnrollReply = $payerAuthEnrollReply;

        $response->merchantReferenceCode = 'razorpay';
        $response->requestID = 'f32n23ke';

        if ($request->card->accountNumber === '4012001038443335')
        {
            $response->decision = 'REJECT';
            $response->reasonCode = Pay\Result::ENROLLED;
            
            $params = array('gateway' => 'cybersource');
            $response->payerAuthEnrollReply->acsURL = Http\Route::getUrl('mockcybersource_acs', $params);
            $response->payerAuthEnrollReply->paReq = 'eNpVUttygjAQfc9XMP0AkiAw';
            $response->payerAuthEnrollReply->xid = 'cGdKQXF5STA1TFl3OUtueHJnWDA';
            $response->payerAuthEnrollReply->veresEnrolled = 'Y';
        }
        else if ($request->card->accountNumber === '555555555555558')
        {
            $response->decision = 'ACCEPT';
            $response->reasonCode = Pay\Result::SUCCESS;

            $response->payerAuthEnrollReply->veresEnrolled = 'U';
            $response->payerAuthEnrollReply->commerceIndicator = 'spa';
            $response->payerAuthEnrollReply->ucafCollectionIndicator = '1';
        }
        else
        {
            $response->decision = 'ACCEPT';
            $response->reasonCode = Pay\Result::SUCCESS;

            $response->payerAuthEnrollReply->commerceIndicator = 'internet';
            $response->payerAuthEnrollReply->veresEnrolled = 'U';
            $response->payerAuthEnrollReply->eci = '05';
        }
 
        return $response;
    }

    public function acs($input)
    {
        $this->validateAuthenticateInput($input);

        return array('PaRes' => 'eNpVUttygjAQfc9XMP0AkiAw',
                     'MD' => $input['MD'],
                     'TermUrl' => $input['TermUrl']);
    }
}