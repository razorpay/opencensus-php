<?php

namespace RZP\Gateway\Hdfc\Payment;

use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Hdfc\Payment;
use RZP\Models\Currency\Currency;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Card;
use Razorpay\Trace\Logger as Trace;

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
                $this->trace->warning(
                    TraceCode::GATEWAY_ERROR_ISSUER_AUTHENTICATION_NOT_AVAILABLE,
                    [
                        'enroll_status' => $enrollStatus,
                    ]);

                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
                    'enrollment_status:' . $enrollStatus,
                    'Unexpected response',
                    [
                        'enroll_status' => $enrollStatus,
                    ],
                    null,
                    Base\Action::AUTHENTICATE,
                    true);
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

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHORIZATION_INITIATED,
            $input);

        $this->runRequestResponseFlow(
            $this->authEnrolledRequest,
            $this->authEnrolledResponse);

        $this->verifyAuthResponse($this->authEnrolledResponse);
    }

    protected function verifyAuthResponse(array & $authResponse)
    {
        $this->traceAuthEnrolledResponse($authResponse);

        $this->isAuthSuccess($authResponse);

        if (isset($authResponse['data']['trackid']) === true)
        {
            $this->assertPaymentId($this->input['payment']['id'], $authResponse['data']['trackid']);
        }

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

    protected function verifyDebitPinAuthResponse(array & $authResponse)
    {
        $this->isAuthSuccess($authResponse);

        if (isset($authResponse['data']['trackid']) === true)
        {
            $this->assertPaymentId($this->input['payment']['id'], $authResponse['data']['trackid']);
        }

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

        $this->persistAfterDebitPinAuthorize($authResponse);

        if ($this->error === true)
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
            ($authResponse['error']['code'] === Hdfc\ErrorCodes\ErrorCodes::CM90004))
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

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHORIZATION_INITIATED,
            $this->input);

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

        if ((isset($authResponse['data']['Error']) === true) and
            (isset($authResponse['data']['result']) === true) and
            ($authResponse['data']['Error'] !== '') and
            ($result === Payment\Result::AUTH_ERROR_IPAY))
        {
            //
            // If Error is set then it is given higher priority than result for failure cases only
            // if result is AUTH+ERROR
            //
            $result = $authResponse['data']['Error'];
        }

        if (($result === Payment\Result::APPROVED) or ($result === Payment\Result::CAPTURED))
        {
            return true;
        }

        //
        // Check enroll result code.
        //
        $errorCode = $this->getErrorCodeFromResult($result);

        Hdfc\ErrorHandler::setErrorInResponse($authResponse, $errorCode);

        if (isset($authResponse['data']['authRespCode']))
        {
            $authResponse['error']['authRespCode'] = $authResponse['data']['authRespCode'];
        }

        $this->error = true;

        return false;
    }

    protected function createAuthNotEnrolledRequestFields()
    {
        $this->createAuthNotEnrolledRequestFieldsFromEnrollData();

        $this->trace(
            Trace::DEBUG,
            TraceCode::GATEWAY_NOT_ENROLLED_REQUEST,
            $this->authNotEnrolledRequest);
    }

    protected function createAuthNotEnrolledRequestFieldsFromEnrollData()
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
        $this->trace(
            Trace::INFO,
            TraceCode::GATEWAY_ENROLLED_AUTH_RESPONSE,
            $authResponse);
    }

    protected function tracePreAuthResponse($authResponse)
    {
        $this->trace(
            Trace::INFO,
            TraceCode::GATEWAY_PRE_AUTH_RESPONSE,
            $authResponse);
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

    protected function persistAfterDebitPinAuthorize($authResponse)
    {
        if ($this->error === true)
        {
            $this->repo->persistAfterDebitPinAuthorizeError(
                $this->model,
                $authResponse);
        }
        else
        {
            $this->repo->persistAfterDebitPinAuthorize(
                $this->model,
                $authResponse['data']);
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

    protected function createAuthRecurringRequestFields(array $input)
    {
        $payment = $input['payment'];

        $card = $input['card'];

        // set the iso numeric currency code
        $currency = $payment['currency'];

        $data = [
            'trackid'      => $payment['id'],
            'amt'          => $payment['amount'] / 100,
            'udf1'         => 'test',
            'udf2'         => $payment['email'],
            'udf3'         => $payment['contact'],
            'udf4'         => 'test',
            'udf5'         => 'test',
            'currencycode' => Currency::ISO_NUMERIC_CODES[$currency],
            'action'       => Action::AUTHORIZE,
        ];

        $this->setDebitSecondRecurringPayment($input);

        if ($this->secondDebitRecurringFlag === true)
        {
            $data['expmonth'] = $card['expiry_month'];

            $data['expyear'] = $card['expiry_year'];

            $data['cavv'] = Hdfc\Constants::DEBIT_SECOND_RECURRING_PAYMENT_CAVV;

            $data['xid'] = Hdfc\Constants::DEBIT_SECOND_RECURRING_PAYMENT_XID;

            $data['enrollmentflag'] = Hdfc\Constants::DEBIT_SECOND_RECURRING_PAYMENT_ENROLLMENT_FLAG;

            $data['authenticationflag'] = Hdfc\Constants::DEBIT_SECOND_RECURRING_PAYMENT_AUTHENTICATION_FLAG;

            $data['eci'] = $this->getEci($input);

            $data['type'] = Hdfc\Constants::PRE_AUTH_TYPE;

            $this->authSecondRecurringRequest['url'] = Hdfc\Urls::PRE_AUTH_URL;
        }

        // Collect udf fields
        // Only visa/master are supported for recurring
        $this->populateRiskUdfIfApplicable($data, $input);

        $this->udfCheckAndMeetHdfcRequirements($data);

        $this->udfRemoveHackCharacters($data);

        // Collect fields related to the card
        $this->mapKeys($card, $this->cardKeyMappings, $data);

        $this->authSecondRecurringRequest['data'] = $data;

        // Fields not required for authorizeRecurring.
        unset($this->authSecondRecurringRequest['content']);

        unset($this->authSecondRecurringRequest['data']['cvv2']);
    }

    protected function createPreAuthRequestFields(array $input)
    {
        $payment = $input['payment'];

        $card = $input['card'];

        // set the iso numeric currency code
        $currency = $payment['currency'];

        $data = [
            'trackid'            => $payment['id'],
            'amt'                => $payment['amount'] / 100,
            'udf1'               => 'test',
            'udf2'               => $payment['email'],
            'udf3'               => $payment['contact'],
            'udf4'               => 'test',
            'udf5'               => 'test',
            'currencycode'       => Currency::ISO_NUMERIC_CODES[$currency],
            'action'             => Action::AUTHORIZE,
            'cavv'               => $input['authentication']['cavv'],
            'xid'                => $input['authentication']['xid'],
            'enrollmentflag'     => $input['authentication']['enrolled'],
            'authenticationflag' => $input['authentication']['status'],
            'eci'                => $input['authentication']['eci'],
            'type'               => Hdfc\Constants::PRE_AUTH_TYPE,
        ];

        // Collect udf fields
        // Only visa/master are supported for recurring
        $this->populateRiskUdfIfApplicable($data, $input);

        $this->populateUdf1IfApplicable($data, $input);

        $this->udfCheckAndMeetHdfcRequirements($data);

        $this->udfRemoveHackCharacters($data);

        // Collect fields related to the card
        $this->mapKeys($card, $this->cardKeyMappings, $data);

        $this->preAuthorizeRequest['data'] = $data;
    }

    protected function getEci($input)
    {
        $network = $input['card']['network_code'];

        $eci = ($network === Card\Network::VISA) ? '05' : '02';

        return $eci;
    }

    protected function postPreAuthRequest($input)
    {
        $this->createPreAuthRequestFields($input);

        $this->trace(
           Trace::DEBUG,
           TraceCode::GATEWAY_PRE_AUTH_REQUEST,
           $this->preAuthorizeRequest);

        $this->runRequestResponseFlow(
            $this->preAuthorizeRequest,
            $this->preAuthorizeResponse);

        $this->tracePreAuthResponse($this->preAuthorizeResponse);

        $this->isPreAuthSuccess();

        $this->persistAfterPreAuth();

        if ($this->error)
        {
            $this->throwException($this->preAuthorizeResponse['error']);
        }
    }

    protected function isPreAuthSuccess()
    {
        if ($this->error)
        {
            false;
        }

        $result = $this->preAuthorizeResponse['data']['result'];

        //
        // Check enroll result code.
        //
        list($result, $success) = Payment\Result::getPreAuthResultCode($result);

        if ($success === false)
        {
            $matches = [];
            preg_match('/!ERROR!-(.*)-[a-zA-Z ]+/', $result, $matches);

            if (isset($matches[1]) === true)
            {
                $errorCode = $matches[1];
            }
            else
            {
                $errorCode = $this->getErrorCodeFromResult($result);
            }

            $this->preAuthorizeResponse['error'] = Hdfc\ErrorHandler::getErrorDetails($errorCode);

            $this->error = true;
        }

        return $success;
    }

    protected function authorizeRecurring($input)
    {
        $this->createAuthRecurringRequestFields($input);

        $this->trace(
           Trace::DEBUG,
           TraceCode::GATEWAY_RECURRING_AUTH_REQUEST,
           $this->authSecondRecurringRequest);

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHORIZATION_INITIATED,
            $input);

        $this->runRequestResponseFlow(
            $this->authSecondRecurringRequest,
            $this->authSecondRecurringResponse);

        // Check for auth success.
        if ($this->isAuthSuccess($this->authSecondRecurringResponse) === true)
        {
            $this->validateAuthRecurringResponse();
        }

        $this->traceAuthRecurringResponse();

        $this->persistAfterAuthRecurring();

        if ($this->error)
        {
            $this->throwException($this->authSecondRecurringResponse['error']);
        }
    }

    protected function authorizeDebitPin($input)
    {
        $this->createDebitPinAuthRequestFields($input);

        $context['data'] = $this->debitPinAuthenticationRequest['data'];

        $context['data'] = Hdfc\Utility::unsetFields($context['data'], $this->stripFieldsList);

        $this->trace->info(
            TraceCode::GATEWAY_DEBIT_PIN_AUTHENTICATION_REQUEST,
            [
                'content'      => $context['data'],
                'gateway'      => $this->gateway,
                'payment_id'   => $input['payment']['id'],
                'terminal_id'  => $input['terminal']['id'],
            ]);

        $this->runRequestResponseFlow(
            $this->debitPinAuthenticationRequest,
            $this->debitPinAuthenticationResponse);

        $this->trace->info(
            TraceCode::GATEWAY_DEBIT_PIN_AUTHENTICATION_RESPONSE,
            [
                'content'     => $this->debitPinAuthenticationResponse,
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        $response = $this->debitPinAuthenticationResponse;

        if ($this->isDebitPinAuthSuccess($response) === false)
        {
            $this->model = $this->repo->persistAfterDebitPinAuthError(
                $this->debitPinAuthenticationRequest['data'],
                $this->debitPinAuthenticationResponse['error']);

            $this->throwException($this->debitPinAuthenticationResponse['error'], true, Base\Action::AUTHENTICATE);
        }

        $this->model = $this->repo->persistAfterDebitPinAuth(
            $this->debitPinAuthenticationRequest['data'],
            $this->debitPinAuthenticationResponse['data']);

        $tranportalId = $this->debitPinAuthenticationRequest['data']['id'];

        $tranportalPassword = $this->debitPinAuthenticationRequest['data']['password'];

        return $this->getAuthorizeUrl($response, $tranportalId, $tranportalPassword);
    }

    protected function getAuthorizeUrl($response, $tranportalId, $tranportalPassword)
    {
        $stringToHash = $response['data']['paymentId'] . $tranportalId;

        $hashedTranData = $this->getHash($stringToHash, $tranportalId.$tranportalPassword);

        $queryParamArray = [
            'PaymentID' => $response['data']['paymentId'],
            'trandata'  => $hashedTranData,
            'id'        => $tranportalId,
        ];

        $queryParam = http_build_query($queryParamArray);

        $request['url'] = $response['data']['paymenturl'] . '?' . $queryParam;

        $request['method'] = 'post';

        $request['content'] = [];

        $this->trace->info(
            TraceCode::GATEWAY_DEBIT_PIN_AUTHORIZATION_REQUEST,
            [
                'content' => $request,
                'gateway' => $this->gateway,
            ]);

        return $request;
    }

    protected function getHash($str, $secret)
    {
        return hash_hmac('sha256', $str, $secret);
    }

    protected function createDebitPinAuthRequestFields($input)
    {
        $payment = $input['payment'];

        $card = $input['card'];

        $currency = $payment['currency'];

        $data = [
            Hdfc\Fields::ACTION        => Action::PURCHASE,
            Hdfc\Fields::AMOUNT        => $payment['amount'] / 100,
            Hdfc\Fields::CURRENCY      => Currency::ISO_NUMERIC_CODES[$currency],
            Hdfc\Fields::TRACKID       => $payment['id'],
            Hdfc\Fields::CARD          => $card['number'],
            Hdfc\Fields::EXPIRY_MONTH  => $card['expiry_month'],
            Hdfc\Fields::EXPIRY_YEAR   => $card['expiry_year'],
            Hdfc\Fields::TYPE          => 'DC',
            Hdfc\Fields::NAME          => $card['name'],
            Hdfc\Fields::UDF1          => $input['callbackUrl'],
            Hdfc\Fields::UDF2          => $input['callbackUrl'],
            Hdfc\Fields::UDF3          => 'test',
            Hdfc\Fields::UDF4          => 'test',
            Hdfc\Fields::UDF5          => $this->getUdf5hash($payment),
        ];

        $this->debitPinAuthenticationRequest['data'] = $data;
    }

    protected function getUdf5hash($payment)
    {
        $terminal = $this->terminal;

        $tranPortalId = $terminal['gateway_terminal_id'];

        // For TEST mode, replace any random terminal given with
        // hdfc test terminal
        if ($this->mode === Mode::TEST)
        {
            $tranPortalId = $this->config['test_debit_pin_terminal_id'];
        }

        $currency = $payment['currency'];

        $amt = $payment['amount'] / 100;

        $currencyCode = Currency::ISO_NUMERIC_CODES[$currency];

        $strToHash = $tranPortalId . $payment['id'] . $amt . $currencyCode . Action::PURCHASE;

        return hash_hmac('sha256', $strToHash, $tranPortalId);
    }

    protected function isDebitPinAuthSuccess($response)
    {
        if(isset($response['error']) === true)
        {
            $this->error = true;

            return false;
        }
    }

    protected function traceAuthRecurringResponse()
    {
        if ($this->error)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_RECURRING_AUTH_ERROR,
                $this->authSecondRecurringResponse);
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_RECURRING_AUTH_RESPONSE,
                $this->authSecondRecurringResponse);
        }
    }

    protected function persistAfterAuthRecurring()
    {
        if ($this->error)
        {
            $this->repo->persistAfterAuthRecurringError(
                $this->authSecondRecurringRequest['data'],
                $this->authSecondRecurringResponse['error']);
        }
        else
        {
            $this->repo->persistAfterAuthRecurring(
                $this->authSecondRecurringRequest['data'],
                $this->authSecondRecurringResponse['data']);
        }
    }

    protected function persistAfterPreAuth()
    {
        if ($this->error)
        {
            $model = $this->repo->persistAfterPreAuthError(
                $this->preAuthorizeRequest['data'],
                $this->preAuthorizeResponse['error']);
        }
        else
        {
            $model = $this->repo->persistAfterPreAuth(
                $this->preAuthorizeRequest['data'],
                $this->preAuthorizeResponse['data']);
        }

        $this->model = $model;
    }

    protected function validateAuthRecurringResponse()
    {
        $data = $this->authSecondRecurringResponse['data'];

        $this->validatePostDate($data['postdate']);
    }

    protected function getErrorCodeFromResult($result)
    {
        switch ($result)
        {
            case Payment\Result::NOT_APPROVED:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00006;
                break;

            case Payment\Result::NOT_CAPTURED:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00007;
                break;

            case Payment\Result::HOST_TIMEOUT:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00004;
                break;

            case Payment\Result::DENIED_BY_RISK:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00005;
                break;

            case Payment\Result::AUTH_ERROR:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00010;
                break;

            case Payment\Result::CANCELED:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00011;
                break;
            case Payment\Result::NOT_APPROVED_IPAY:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00016;
                break;

            case Payment\Result::NOT_CAPTURED_IPAY:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00017;
                break;

            case Payment\Result::HOST_TIMEOUT_IPAY:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00015;
                break;

            case Payment\Result::DENIED_BY_RISK_IPAY:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::GW00256;
                break;

            case Payment\Result::AUTH_ERROR_IPAY:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00020;
                break;

            case Payment\Result::DENIED_CAPTURE:
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00021;
                break;

            case '':
                $errorCode = Hdfc\ErrorCodes\ErrorCodes::RP00002;
                break;

            default:
                $errorCode = $result;
                break;
        }

        return $errorCode;
    }
}
