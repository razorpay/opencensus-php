<?php

namespace RZP\Gateway\Hdfc\Payment;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Hdfc\Payment;
use RZP\Models\Payment as PaymentModel;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

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
     * @param $enrollStatus
     * @return mixed
     * @throws Exception\LogicException
     */
    protected function decideAuthStepAfterEnroll($enrollStatus)
    {
        switch ($enrollStatus)
        {
            case Payment\Result::ENROLLED:
                return $this->getFieldsForFormSubmitToBankACS();

            case Payment\Result::NOT_ENROLLED:
                $this->validateMerchantInternationalEnabled();
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
        $content['PaymentID'] = $this->enrollResponse['data']['paymentid'];

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

        assertTrue((int) $this->model->enroll_result === Payment\Result::ENROLLED);

        if ($this->model->status !== Status::ENROLLED)
        {
            throw new Exception\InvalidArgumentException(
                'Gateway Exception: Status not valid. Status: ' . $this->model->status);
        }

        $this->trace(
            Trace::DEBUG,
            TraceCode::GATEWAY_ENROLLED_AUTH_REQUEST,
            $this->authEnrolledRequest);

        $this->runRequestResponseFlow(
            $this->authEnrolledRequest,
            $this->authEnrolledResponse);

        $this->verifyAuthResponse($this->authEnrolledResponse);
    }

    protected function verifyAuthResponse(array & $authResponse)
    {
        $this->isAuthSuccess($authResponse);

        $this->traceAuthEnrolledResponse($authResponse);

        if (($this->error === true) and
            ($this->callbackAlreadyProcessed($authResponse) === true))
        {
            // We don't want to silently return here because that would mean
            // that it is considered as authorized and will end up notifying and
            // triggering a webhook if present.

            // We are throwing an error here itself because we don't want to persist this data.
            // The second callback should have never come in the first place and hence not storing
            // this data in the gateway entity. It was a mistake.

            $this->throwException($authResponse['error']);
        }

        $this->persistAfterAuthEnrolled($authResponse);

        if ($this->error)
        {
            $this->throwException($authResponse['error']);
        }
    }

    protected function callbackAlreadyProcessed($authResponse)
    {
        // This function is called only if $this->error is set.
        // Hence, it is okay to reload here, since it will be done
        // only in case of an error in the authorize flow.
        $this->repo->reload($this->model);

        // HDFC throws CM90004 when the authorize request has already been
        // sent for this payment.
        if (($this->model->getStatus() === Status::AUTHORIZED) and
            ($authResponse['error']['code'] === Hdfc\ErrorCode::CM90004))
        {
            return true;
        }

        return false;
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

        $result = '';
        $errorCode = null;

        if (isset($authResponse['data']['result']) === true)
        {
            Result::modifySpecificResultValueIfRequired($authResponse['data']['result']);

            $result = $authResponse['data']['result'];
        }
        else if (isset($authResponse['data']['Error']) === true)
        {
            // This caps 'Error' only comes in case of Rupay
            $result = $authResponse['data']['Error'];
        }

        //
        // Check enroll result code.
        //
        switch ($result)
        {
            case Payment\Result::APPROVED:
                break;

            case Payment\Result::CAPTURED:
                break;

            case Payment\Result::NOT_APPROVED:
                $errorCode = Hdfc\ErrorCode::RP00006;
                break;

            case Payment\Result::NOT_CAPTURED:
                $errorCode = Hdfc\ErrorCode::RP00007;
                break;

            case Payment\Result::HOST_TIMEOUT:
                $errorCode = Hdfc\ErrorCode::RP00004;
                break;

            case Payment\Result::DENIED_BY_RISK:
                $errorCode = Hdfc\ErrorCode::RP00005;
                break;

            case Payment\Result::AUTH_ERROR:
                $errorCode = Hdfc\ErrorCode::RP00010;
                break;

            case Payment\Result::CANCELED:
                $errorCode = Hdfc\ErrorCode::RP00011;
                break;

            case Hdfc\ErrorCode::PY20085:
                $errorCode = Hdfc\ErrorCode::PY20085;
                break;

            default:
                $errorCode = Hdfc\ErrorCode::RP00002;
                break;
        }

        if ($errorCode !== null)
        {
            Hdfc\ErrorHandler::setErrorInResponse($authResponse, $errorCode);
            $this->error = true;
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

        $this->authNotEnrolledRequest['url'] = Hdfc\Urls::AUTH_NOT_ENROLLED_URL;
        $this->authNotEnrolledRequest['data'] = $data;

        unset($this->authNotEnrolledRequest['content']);

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

    protected function traceAuthEnrolledResponse($authResponse)
    {
        if ($this->error)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_ENROLLED_AUTH_ERROR,
                $authResponse);
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_ENROLLED_AUTH_RESPONSE,
                $authResponse);
        }
    }

    protected function persistAfterAuthNotEnrolled()
    {
        if ($this->error)
        {
            $this->repo->persistAfterAuthNotEnrolledError(
                $this->model,
                $this->authNotEnrolledResponse);
        }
        else
        {
            $this->repo->persistAfterAuthNotEnrolled(
                $this->model,
                $this->authNotEnrolledResponse['data']);
        }
    }

    protected function persistAfterAuthEnrolled($authEnrolledResponse)
    {
        if ($this->error)
        {
            $this->repo->persistAfterAuthEnrolledError(
                $this->model,
                $authEnrolledResponse);
        }
        else
        {
            $this->repo->persistAfterAuthEnrolled(
                $this->model,
                $authEnrolledResponse['data']);
        }
    }

    protected function createAuthEnrolledRequestFields($input)
    {
        $this->authEnrolledRequest['url'] = Hdfc\Urls::AUTH_ENROLLED_URL;
        $this->authEnrolledRequest['data']['paymentid'] = $input['gateway']['MD'];

        $this->authEnrolledRequest['data']['PaRes'] = $input['gateway']['PaRes'];
    }

    protected function validateMerchantInternationalEnabled()
    {
        $input = $this->input;

        if ($input['merchant']['international'] === false)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::PAYMENT_CARD_NOT_ENROLLED,
                ['payment_id' => $input['payment']['id']]);

            // throw new Exception\BadRequestException(
            //     ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED);
        }
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
