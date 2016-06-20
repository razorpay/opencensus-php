<?php
 
 namespace Gateway\Cybersource\Mock;
 
 use EE\Exception;
 use Gateway\Base;
 use Models\Card;
 use Models\Payment;
 use App;
 use Http;
 
 
class Server extends Base\Mock\Server
{
    protected $repo;
 
    public function getGatewayResponse($request)
    {
        if (isset($request->payerAuthEnrollService))
        {
            return $this->getEnrollResponse($request);
        }

        if (isset($request->payerAuthValidateService))
        {
            return $this->postAuthEnrolledRequest($request);
        }

        if (isset($request->ccAuthService))
        {
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

    public function postNotEnrolledAuthorize($input, $enrollResponse)
    {
        $response = new \stdClass();

    }

    public function getRefundResponse($request)
     {
        $response = new \stdClass();

        $response->decision = "ACCEPT";
        $response->reasonCode = 100;
        $response->requestID = "4661549029556297301014";
        $response->merchantReferenceCode = "razorpay";

        $ccCreditReply = new \stdClass();
        $ccCreditReply->reconciliationID = "razorpay";

        $response->ccCreditReply = $ccCreditReply;
        $response->ccCreditReply = $ccCreditReply;

        return $response;
     }

    public function getCaptureResponsse($request)
     {
        $response = new \stdClass();

        $response->decision = "ACCEPT";
        $response->reasonCode = 100;
        $response->requestID = "4661468455476856801016";

        $ccCaptureReply = new \stdClass();
        $ccCaptureReply->reconciliationID = "razorpay";

        $response->ccCaptureReply = $ccCaptureReply;

        return $response;
     }

     public function postAuthEnrolledRequest($request)
     {
        $response = new \stdClass();

        $response->decision = "ACCEPT";
        $response->reasonCode = 100;

        $payerAuthValidateReply = new \stdClass();
        $payerAuthValidateReply->eciRaw = "05";
        $payerAuthValidateReply->xid = "TktUb3hwZVp0eTMxcTh5UlZUODA=";
        $payerAuthValidateReply->paresStatus = "Y";
        $payerAuthValidateReply->commerceIndicator = "Internet";
        $payerAuthValidateReply->cavv = "1";

        $response->payerAuthValidateReply = $payerAuthValidateReply;

        return $response;
     }

    public function postEnrollAuthorize($request)
    {
        $response = new \stdClass();

        $response->decision = "ACCEPT";
        $response->reasonCode = 100;
        $response->requestID = "4661454138166750401020";

        $ccAuthReply = new \stdClass();
        $ccAuthReply->reconciliationID = "razorpay";

        $response->ccAuthReply = $ccAuthReply;

        return $response;
    }
 
    public function getEnrollResponse($request)
    {
        $response = new \stdClass();

        $payerAuthEnrollReply = new \stdClass();
        $response->payerAuthEnrollReply = $payerAuthEnrollReply;

        if ($request->card->accountNumber === '4012001038443335')
        {
            $response->merchantReferenceCode = 'razorpay';
            $response->decision = 'REJECT';
            $response->reasonCode = 475;
            $response->requestID = 'f32n23ke';
            
            $params = array('gateway' => 'cybersource');
            $response->payerAuthEnrollReply->acsURL = Http\Route::getUrl('mockcybersource_acs', $params);
            $response->payerAuthEnrollReply->paReq = 'eNpVUttygjAQfc9XMP0AkiAw';
            $response->payerAuthEnrollReply->xid = 'cGdKQXF5STA1TFl3OUtueHJnWDA';
            $response->payerAuthEnrollReply->veresEnrolled = 'Y';

        }
        else if ($request->card->accountNumber === '555555555555558')
        {
            $response->merchantReferenceCode = 'razorpay';
            $response->decision = 'ACCEPT';
            $response->reasonCode = 100;
            $response->requestID = 'f32n23ke';

            $response->payerAuthEnrollReply->veresEnrolled = "U";
            $response->payerAuthEnrollReply->commerceIndicator = "spa";
            $response->payerAuthEnrollReply->ucafCollectionIndicator = "1";
        }
        else
        {
            $response->merchantReferenceCode = 'razorpay';
            $response->decision = 'ACCEPT';
            $response->reasonCode = 100;
            $response->requestID = 'f32n23ke';

            $response->payerAuthEnrollReply->commerceIndicator = 'internet';
            $response->payerAuthEnrollReply->veresEnrolled = 'U';
        }
 
        return $response;
    }

    public function acs($input)
    {
        return array('PaRes' => "eNpVUttygjAQfc9XMP0AkiAw",
                     'MD' => $input['MD'],
                     'TermUrl' => $input['TermUrl']);
    }
}