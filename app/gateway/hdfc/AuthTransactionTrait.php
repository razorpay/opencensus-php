<?php

namespace Gateway\Hdfc;

use Gateway\Hdfc;
use EE\Exception;
use Trace\Trace;
use Trace\TraceCode;

trait AuthTransactionTrait
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
            case Hdfc\Result::ENROLLED:
                return $this->getFieldsForFormSubmitToBankACS();

            case Hdfc\Result::NOT_ENROLLED:
                return $this->postAuthNotEnrolledRequestToBank();

            default:
                throw new \LogicException('Should not have reached here');
        }
    }

    /**
     * The fields provided here are used for generating
     * the form.
     *
     * The form generated in view is auto-submitted on load
     * with the fields received in response
     * from enrolling the card.
     *
     * @return array Array containing values to post
     *               request to bank ACS.
     */
    protected function getFieldsForFormSubmitToBankACS()
    {
        $enrollResponse = $this->enrollResponse;

        $callbackUrl = $this->callbackUrl;

        $pos = strrpos($callbackUrl, '/');

        $callbackUrl = substr($callbackUrl, 0, $pos);

        $callbackUrl .= '/' . 'txn-' . $this->id;

        return array(
                'data' => $enrollResponse['data'],
                'callbackUrl' => $callbackUrl);
    }


    public function postAuthEnrolledRequest($input)
    {
        $this->createAuthEnrolledRequestFields($input);

        //
        // Verify that the card is already enrolled.
        // Throw exception otherwise.
        //

        Assert((int) $this->model->enroll_result === Hdfc\Result::ENROLLED);

        if ($this->model->status !== Hdfc\Status::ENROLLED)
        {
            throw new Exception\InvalidArgumentException('Gateway Exception: Status not valid');
        }

        $this->trace(
            Trace::DEBUG,
            TraceCode::GATEWAY_ENROLLED_AUTH_REQUEST,
            $this->authEnrolledRequest);

        $data = &$this->authEnrolledRequest['data'];

        list($data['id'], $data['password']) = static::getCredentials();

        $this->runRequestResponseFlow(
            $this->authEnrolledRequest,
            $this->authEnrolledResponse);

        $this->traceAuthEnrolledResponse();

        //
        // Store the response data
        //
        $this->persistAfterAuthEnrolled();

        if ($this->error)
        {
            $this->throwException($this->authEnrolledResponse['error']['code']);
        }
    }

    protected function postAuthNotEnrolledRequestToBank()
    {
        $this->createAuthNotEnrolledRequestFields();

        $this->runRequestResponseFlow(
            $this->authNotEnrolledRequest,
            $this->authNotEnrolledResponse);

        $this->traceAuthNotEnrolledResponse();

        $this->persistAfterAuthNotEnrolled();

        if ($this->error)
        {
            $this->throwException($this->authNotEnrolledResponse['error']['code']);
        }
    }

    protected function createAuthNotEnrolledRequestFields()
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
            TraceCode::GATEWAY_NOT_ENROLLED_REQUEST,
            $this->authNotEnrolledRequest);
    }

    protected function traceAuthNotEnrolledResponse()
    {
        $response = &$this->authNotEnrolledResponse;

        $response['data']['processed'] = ($response['data']['result'] == 'APPROVED') ? 1 : 0;

        if($response['data']['processed'])
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_NOT_ENROLLED_RESPONSE,
                $response);
        }
        else
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_NOT_ENROLLED_ERROR,
                $response);
        }

    }

    protected function traceAuthEnrolledResponse()
    {
        if ($this->error)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_ENROLLED_AUTH_ERROR,
                $this->authEnrolledResponse);
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_ENROLLED_AUTH_RESPONSE,
                $this->authEnrolledResponse);
        }
    }

    protected function persistAfterAuthNotEnrolled()
    {
        if ($this->error)
        {
            $this->repo->persistAfterAuthNotEnrolledError(
                $this->model,
                $this->authNotEnrolledResponse['error']);
        }
        else
        {
            $this->repo->persistAfterAuthNotEnrolled(
                $this->model,
                $this->authNotEnrolledResponse['data']);
        }
    }

    protected function persistAfterAuthEnrolled()
    {
        if ($this->error)
        {
            $this->repo->persistAfterAuthEnrolledError(
                $this->model,
                $this->authEnrolledResponse['error']);
        }
        else
        {
            $this->repo->persistAfterAuthEnrolled(
                $this->model,
                $this->authEnrolledResponse['data']);
        }
    }

    protected function createAuthEnrolledRequestFields($input)
    {
        $this->authEnrolledRequest['data']['paymentid'] = $input['MD'];

        $this->authEnrolledRequest['data']['PaRes'] = $input['PaRes'];
    }

}