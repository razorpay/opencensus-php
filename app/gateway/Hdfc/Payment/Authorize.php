<?php

namespace Gateway\Hdfc\Payment;

use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Payment;
use Trace\Trace;
use Trace\TraceCode;

trait Authorize
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

        $fields = array(
            'paymentid',
            'PAReq',
            'url');

        $content['TermUrl'] = $this->callbackUrl;
        $content['MD'] = $this->enrollResponse['data']['paymentid'];
        $content['PaReq'] = $this->enrollResponse['data']['PAReq'];

        $request['content'] = $content;
        $request['url'] = $this->enrollResponse['data']['url'];
        $request['method'] = 'post';

        return $request;
    }

    protected function getFieldsForFormSubmitForRupay()
    {
        $enrollResponse = $this->enrollResponse;

        $fields = array(
            'PaymentID');

        $content['PaymentID'] = $this->enrollResponse['data']['paymentid'];
//        $content['TermUrl'] = $this->callbackUrl;

        $request['content'] = $content;
        $request['url'] = $this->enrollResponse['data']['url'];
        $request['method'] = 'post';

        return $request;
    }


    public function postAuthEnrolledRequest($input)
    {
        $this->createAuthEnrolledRequestFields($input);

        //
        // Verify that the card is already enrolled.
        // Throw exception otherwise.
        //

        assert((int) $this->model->enroll_result === Payment\Result::ENROLLED);

        if ($this->model->status !== Status::ENROLLED)
        {
            throw new Exception\InvalidArgumentException('Gateway Exception: Status not valid');
        }

        $this->trace(
            Trace::DEBUG,
            TraceCode::GATEWAY_ENROLLED_AUTH_REQUEST,
            $this->authEnrolledRequest);

        $data = &$this->authEnrolledRequest['data'];

        $this->runRequestResponseFlow(
            $this->authEnrolledRequest,
            $this->authEnrolledResponse);

        if ($this->isAuthSuccess($this->authEnrolledResponse) === true)
        {
            ; //$this->validateAuthEnrolledResponse($this->authEnrolledResponse);
        }

        $this->traceAuthEnrolledResponse();

        $this->persistAfterAuthEnrolled();

        if ($this->error)
        {
            $this->throwException($this->authEnrolledResponse['error']);
        }
    }

    protected function postAuthNotEnrolledRequestToBank()
    {
        if ($this->model->status !== Status::NOT_ENROLLED)
        {
            throw new Exception\InvalidArgumentException('Gateway Exception: Status not valid');
        }

        $this->createAuthNotEnrolledRequestFields();

        $this->runRequestResponseFlow(
            $this->authNotEnrolledRequest,
            $this->authNotEnrolledResponse);

        if ($this->isAuthSuccess($this->authNotEnrolledResponse) === true)
        {
            $this->validateAuthNotEnrolledResponse();
        }

        $this->traceAuthNotEnrolledResponse();

        $this->persistAfterAuthNotEnrolled();

        if ($this->error)
        {
            $this->throwException($this->authNotEnrolledResponse['error']);
        }
    }

    protected function isAuthSuccess(array & $authResponse)
    {
        if ($this->error)
        {
            return false;
        }

        $result = &$authResponse['data']['result'];

        //
        // Check enroll result code.
        // 'enrollSuccess' variable tells us whether
        // its a success code or failure.
        //
        switch ($result)
        {
            case Payment\Result::APPROVED:
                break;

            case Payment\Result::NOT_APPROVED:
                Hdfc\ErrorHandler::setErrorInResponse(
                    $authResponse,
                    Hdfc\ErrorCode::RP00006);
                $this->error = true;
                break;

            case Payment\Result::HOST_TIMEOUT:
                Hdfc\ErrorHandler::setErrorInResponse(
                    $authResponse,
                    Hdfc\ErrorCode::RP00004);
                $this->error = true;
                break;

            case Payment\Result::DENIED_BY_RISK:
                Hdfc\ErrorHandler::setErrorInResponse(
                    $authResponse,
                    Hdfc\ErrorCode::RP00005);
                $this->error = true;
                break;

            default:
                Hdfc\ErrorHandler::setErrorInResponse(
                    $authResponse,
                    Hdfc\ErrorCode::RP00002);
                $this->error = true;
                break;
        }

        return ! ($this->error);
    }

    protected function createAuthNotEnrolledRequestFields()
    {
        //
        // Only need to add zip and addr fields
        // since other fields have already been added during enroll
        //
        $data = $this->enrollRequest['data'];

        $data['zip'] = '';

        $data['addr'] = '';

        $this->authNotEnrolledRequest['data'] = $data;

        $this->trace(
            Trace::DEBUG,
            TraceCode::GATEWAY_NOT_ENROLLED_REQUEST,
            $this->authNotEnrolledRequest);
    }

    protected function traceAuthNotEnrolledResponse()
    {
        $response = &$this->authNotEnrolledResponse;

        if ($this->error === false)
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
        $this->authEnrolledRequest['data']['paymentid'] = $input['gateway']['MD'];

        $this->authEnrolledRequest['data']['PaRes'] = $input['gateway']['PaRes'];
    }

    protected function validateAuthNotEnrolledResponse()
    {
        $data = $this->authNotEnrolledResponse['data'];

        $this->validatePostDate($data['postdate']);
    }

    protected function validatePostDate($postDate)
    {
        // Postdate that we get back from hdfc gateway as yet is weird
        // It's giving next day date on 5 pm on current day.
        ;
    }
}