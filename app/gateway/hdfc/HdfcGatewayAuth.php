<?php

namespace Gateway\HdfcGateway;

use Trace\Trace;
use Trace\TraceEvent;

trait HdfcGatewayAuth
{
    /**
     * 1.   We reach here after card enroll request has been successful.
     * 2.   In case the card is enrolled, this function returns
     *      an array of data to display a form for submission to
     *      bank ACS where the customer can enter OTP or 3d-secure code.
     * 3.   In case of card not enrolled, this funciton next calls
     *      for submission of request for auth.
     *      
     * @return void
     */
    protected function decideAuthStepAfterEnroll()
    {
        switch ($this->enrollStatus)
        {
            case HdfcGatewayResult::ENROLLED:
                return $this->postPaymentRequestToBankACS();

            case HdfcGatewayResult::NOT_ENROLLED:
                return $this->postAuthNotEnrolledRequestToBank();

            default:
                throw new \LogicException('Should not have reached here');
        }
    }

    /**
     * Generates a form and auto-submits it on load
     * with the fields received in response
     * from enrolling the card.
     *
     * The fields provided here are used for generating
     * the view/form.
     *
     * @return array Array containing values to post
     *               request to bank ACS.
     */
    protected function postPaymentRequestToBankACS()
    {
        $enrollResponse = $this->enrollResponse;

        return array('enrolled', 
                     array(
                        'data' => $enrollResponse['data'], 
                        'callbackUrl' => $this->callbackUrl));
    }
 
    protected function validateBankAcsCallbackFields(array $input)
    {
        $invalid_keys = array_diff_key($input, $this->bankAcsResponseRules);
 
        if (count($invalid_keys) !== 0)
        {
            throw new Exceptions\InvalidKeysException('Gateway Exception: '.$invalid_keys);
        }
 
        $validation = \Validator::make($input, $this->bankAcsResponseRules);
 
        if ($validation->fails()) 
        {
            throw new InvalidArgumentException('Gateway Exception: Invalid Arguements '.$validation->messages()->all());
        }
    }

    public function postAuthEnrolledRequest()
    {
        //
        // Verify that the card is already enrolled.
        // Throw exception otherwise.
        //
        if ((int) $this->model->enroll_result !== HdfcGatewayResult::ENROLLED)
        {
            throw new LogicException('Gateway Exception: Result not valid');
        }
        else if ($this->model->status !== 'VERES Received')
        {
            throw new InvalidArgumentException('Gateway Exception: Status not valid');
        }
 
        $data = &$this->authEnrolledRequest['data'];
 
        list($data['id'], $data['password']) = $this->getCredentials();
        
        $this->trace(
            Trace::DEBUG,
            TraceEvent::GATEWAY_ENROLLED_AUTH_REQUEST,
            $this->authEnrolledRequest);

        $this->runRequestResponseFlow(
            $this->authEnrolledRequest,
            $this->authEnrolledResponse);

        //
        // Store the response data
        //
        $this->persistAfterDCAuth();
    }

    protected function postAuthNotEnrolledRequestToBank()
    {
        //
        // Only need to add zip and addr fields
        // since other fields have already been added during enroll
        // 
        $data = $this->enrollRequest['data'];
 
        $data['zip'] = "";
 
        $data['addr'] = "";

        $this->authNotEnrolledRequest['data'] = $data;

        $this->trace(
            Trace::DEBUG,
            TraceEvent::GATEWAY_NOT_ENROLLED_REQUEST,
            $this->authNotEnrolledRequest);

        $this->runRequestResponseFlow(
            $this->authNotEnrolledRequest,
            $this->authNotEnrolledResponse);


        $this->model->persistAfterCCAuth(
                        $this->authNotEnrolledResponse['data']);

        $notEnrollResponse = $this->authNotEnrolledResponse;

        $notEnrollResponse['data']['processed'] = ($notEnrollResponse['data']['result'] == 'APPROVED') ? 1 : 0;
        
        if($notEnrollResponse['data']['processed'])
        {
            $this->trace(
                Trace::INFO,
                TraceEvent::GATEWAY_NOT_ENROLLED_RESPONSE,
                $notEnrollResponse);
        }
        else
        {
            $this->trace(
                Trace::ERROR,
                TraceEvent::GATEWAY_NOT_ENROLLED_ERROR,
                $notEnrollResponse);
        }

        return array('not enrolled',
                     array(
                        'data' => $notEnrollResponse['data']));
    }

    protected function persistAfterDCAuth()
    {
        if (isset($this->authEnrolledResponse['error']['code']))
        {
            $this->model->persistAfterDCAuthError($this->authEnrolledResponse['error']);

            $this->trace(
                Trace::ERROR,
                TraceEvent::GATEWAY_ENROLLED_AUTH_ERROR,
                $this->authEnrolledResponse);

            return false;
        }
        else
        {
            $this->model->persistAfterDCAuth($this->authEnrolledResponse['data']);

            $this->trace(
                Trace::INFO,
                TraceEvent::GATEWAY_ENROLLED_AUTH_RESPONSE,
                $this->authEnrolledResponse);

            return true;
        }
    }

}