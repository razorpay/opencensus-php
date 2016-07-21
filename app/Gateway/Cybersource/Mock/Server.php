<?php

namespace RZP\Gateway\Cybersource\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\App;
use RZP\Http;
use RZP\Gateway\Cybersource;

class Server extends Base\Mock\Server
{
    protected $repo;

    public function authorize($input)
    {
        $this->validateAuthorizeInput($input);

        return $this->getEnrollAuthorizeResponse($input);
    }

    public function capture($input)
    {
        $this->validateActionInput($input, 'capture');

        return $this->getCaptureResponse($input);
    }

    public function enroll($input)
    {
        $this->validateActionInput($input, 'enroll');

        return $this->getEnrollResponse($input);
    }

    public function refund($input)
    {
        $this->validateActionInput($input, 'refund');

        $response = array();

        $response['decision'] = 'ACCEPT';
        $response['reasonCode'] = Cybersource\Result::SUCCESS;
        $response['requestID'] = '4661549029556297301014';
        $response['merchantReferenceCode'] = 'razorpay';

        $ccCreditReply = array();
        $ccCreditReply['reconciliationID'] = 'razorpay';

        $response['ccCreditReply'] = $ccCreditReply;
        $response['ccCreditReply'] = $ccCreditReply;

        return $response;
    }

    public function authValidate($input)
    {
        $this->validateActionInput($input, 'auth_validate');

        return $this->getAuthEnrolledRequest($input);
    }

    public function getCaptureResponse($request)
    {
        $response = array();

        $response['decision'] = 'ACCEPT';
        $response['reasonCode'] = Cybersource\Result::SUCCESS;
        $response['requestID'] = '4661468455476856801016';

        $ccCaptureReply = array();
        $ccCaptureReply['reconciliationID'] = 'razorpay';

        $response['ccCaptureReply'] = $ccCaptureReply;

        return $response;
     }

     protected function getAuthEnrolledRequest($input)
     {
        $response = array();

        $response['decision'] = 'ACCEPT';
        $response['reasonCode'] = Cybersource\Result::SUCCESS;

        $payerAuthValidateReply = array();
        $payerAuthValidateReply['eci'] = '05';
        $payerAuthValidateReply['xid'] = 'TktUb3hwZVp0eTMxcTh5UlZUODA=';
        $payerAuthValidateReply['paresStatus'] = 'Y';
        $payerAuthValidateReply['commerceIndicator'] = 'Internet';
        $payerAuthValidateReply['cavv'] = '1';

        $response['payerAuthValidateReply'] = $payerAuthValidateReply;

        return $response;
     }

    protected function getEnrollAuthorizeResponse($input)
    {
        $response = array();

        $response['decision'] = 'ACCEPT';
        $response['reasonCode'] = Cybersource\Result::SUCCESS;
        $response['requestID'] = '4661454138166750401020';

        $ccAuthReply = array();
        $ccAuthReply['reconciliationID'] = 'razorpay';

        $response['ccAuthReply'] = $ccAuthReply;

        return $response;
    }

    public function getEnrollResponse($request)
    {
        $response = array();

        $payerAuthEnrollReply = array();
        $response['payerAuthEnrollReply'] = $payerAuthEnrollReply;

        $response['merchantReferenceCode'] = 'razorpay';
        $response['requestID'] = 'f32n23ke';

        switch ($request['card']['accountNumber'])
        {
            case '4012001038443335':
                $response['decision'] = 'REJECT';
                $response['reasonCode'] = Cybersource\Result::ENROLLED;

                $params = array('gateway' => 'cybersource');
                $response['payerAuthEnrollReply']['acsURL'] = Http\Route::getUrl('mockcybersource_acs', $params);
                $response['payerAuthEnrollReply']['paReq'] = 'eNpVUttygjAQfc9XMP0AkiAw';
                $response['payerAuthEnrollReply']['xid'] = 'cGdKQXF5STA1TFl3OUtueHJnWDA';
                $response['payerAuthEnrollReply']['veresEnrolled'] = 'Y';
                break;

            case '4280951000002433':
                $response['decision'] = 'REJECT';
                $response['reasonCode'] = 101;
                $response['payerAuthEnrollReply'] = [
                    'reasonCode' => 101
                ];
                $response['missingField'] = 'c:authRequestID';
                $response['requestToken'] = 'AhjjLwSR/H2rNiTcqkX45p6D4dUQCsgfIwdIy6SZbpAeLRGAdmIW';
                break;

            case '4000400000000004':
                $response['decision'] = 'REJECT';
                $response['reasonCode'] = 151;
                $response['payerAuthEnrollReply'] = [
                    'reasonCode' => 151
                ];

                $response['merchantReferenceCode'] = '5vrAvHg6CqQlkS';
                $response['missingField'] = 'c:authRequestID';
                $response['requestID'] = '4690000690226079802108';
                $response['requestToken'] = 'AhjjLwSR/H2rNiTcqkX45p6D4dUQCsgfIwdIy6SZbpAeLRGAdmIW';
                break;

            case '555555555555558':
                $response['decision'] = 'ACCEPT';
                $response['reasonCode'] = Cybersource\Result::SUCCESS;

                $response['payerAuthEnrollReply']['veresEnrolled'] = 'U';
                $response['payerAuthEnrollReply']['commerceIndicator'] = 'spa';
                $response['payerAuthEnrollReply']['ucafCollectionIndicator'] = '1';
                break;

            default:
                $response['decision'] = 'ACCEPT';
                $response['reasonCode'] = Cybersource\Result::SUCCESS;

                $response['payerAuthEnrollReply']['commerceIndicator'] = 'internet';
                $response['payerAuthEnrollReply']['veresEnrolled']= 'U';
                $response['payerAuthEnrollReply']['eci'] = '05';
                break;
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