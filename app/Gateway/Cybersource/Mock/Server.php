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

    public function getGatewayResponse($request)
    {
        if (isset($request['payerAuthEnrollService']))
        {
            $this->validateEnrollInput(json_decode(json_encode($request), true));

            return $this->getEnrollResponse($request);
        }

        if (isset($request['payerAuthValidateService']))
        {
            return $this->postAuthEnrolledRequest($request);
        }

        if (isset($request['ccAuthService']))
        {
            $this->validateAuthorizeInput(json_decode(json_encode($request), true));

            return $this->postEnrollAuthorize($request);
        }

        if (isset($request['ccCaptureService']))
        {
            return $this->getCaptureResponsse($request);
        }

        if (isset($request['ccCreditService']))
        {
            return $this->getRefundResponse($request);
        }
    }

    public function getRefundResponse($request)
     {
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

    public function getCaptureResponsse($request)
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

     public function postAuthEnrolledRequest($request)
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

    public function postEnrollAuthorize($request)
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

        if ($request['card']['accountNumber'] === '4012001038443335')
        {
            $response['decision'] = 'REJECT';
            $response['reasonCode'] = Cybersource\Result::ENROLLED;

            $params = array('gateway' => 'cybersource');
            $response['payerAuthEnrollReply']['acsURL'] = Http\Route::getUrl('mockcybersource_acs', $params);
            $response['payerAuthEnrollReply']['paReq'] = 'eNpVUttygjAQfc9XMP0AkiAw';
            $response['payerAuthEnrollReply']['xid'] = 'cGdKQXF5STA1TFl3OUtueHJnWDA';
            $response['payerAuthEnrollReply']['veresEnrolled'] = 'Y';
        }
        else if ($request['card']['accountNumber'] === '555555555555558')
        {
            $response['decision'] = 'ACCEPT';
            $response['reasonCode'] = Cybersource\Result::SUCCESS;

            $response['payerAuthEnrollReply']['veresEnrolled'] = 'U';
            $response['payerAuthEnrollReply']['commerceIndicator'] = 'spa';
            $response['payerAuthEnrollReply']['ucafCollectionIndicator'] = '1';
        }
        else
        {
            $response['decision'] = 'ACCEPT';
            $response['reasonCode'] = Cybersource\Result::SUCCESS;

            $response['payerAuthEnrollReply']['commerceIndicator'] = 'internet';
            $response['payerAuthEnrollReply']['veresEnrolled ']= 'U';
            $response['payerAuthEnrollReply']['eci'] = '05';
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