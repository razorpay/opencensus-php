<?php

namespace RZP\Gateway\Hdfc\Payment;

use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Base;
use RZP\Gateway\Hdfc\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Card;
use RZP\Models\Order;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Feature\Constants as Feature;

trait Enroll
{
    /**
     * Mapping of keys from rzp to
     * to hdfc gateway for card
     * @var array
     */
    protected $cardKeyMappings = array(
        'name'          => 'member',
        'cvv'           => 'cvv2',
        'number'        => 'card',
        'expiry_month'  => 'expmonth',
        'expiry_year'   => 'expyear');

    /**
     * Sends request for enrolling the card
     * with hdfc gateway
     *
     * @param  array $input
     * Should contain 'payment' and 'card' arrays
     *
     */
    protected function enrollCard(array $input)
    {
        $this->setId($input['payment']['id']);

        $this->callbackUrl = $input['callbackUrl'];

        //
        // Fields to be sent to HDFC gateway for card-enrollment
        //
        $this->createEnrollRequestFields($input);

        $this->trace(
            Trace::DEBUG,
            TraceCode::GATEWAY_ENROLL_REQUEST,
            $this->enrollRequest);

        $network = $input['card']['network_code'];

        // Only required in case of Rupay
        // TODO: This currently does not honour our PROXY_ENABLED
        // setting, which is set to false in production.
        //
        // We will shift it back once we have whitelisted FSS
        if ($network === Card\Network::RUPAY)
        {
            $this->enrollRequest['options']['proxy'] = $this->proxy;
        }

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_INITIATED,
            $input);

        //
        // Send enroll request and receive response.
        // This function also checks for and sets
        // generic error
        //
        $this->runRequestResponseFlow(
            $this->enrollRequest,
            $this->enrollResponse);

        $this->checkAdditionalEnrollError($this->enrollResponse);

        //
        // If there is an error then just return
        //
        if ($this->error)
        {
            $this->persistAfterEnroll();

            $this->throwException($this->enrollResponse['error'], true, Base\Action::AUTHENTICATE);
        }

        //
        // Checks for enroll result.
        //
        // If enroll result is anything other than
        // 'ENROLLED' and 'NOT ENROLLED' then we
        // consider enroll as failed and set an error
        // message to that effect
        //
        if ($this->isEnrollSuccess() === false)
        {
            $this->persistAfterEnroll();

            $this->throwException($this->enrollResponse['error'], true);
        }

        //
        // Checks and sets eci if needed
        //
        $this->checkAndSetEci();

        $this->validateEnrollResponse();

        $this->persistAfterEnroll();

        return $this->getEnrollStatus();
    }

    /**
     * Collect all fields to be sent for
     * enrolling the card
     *
     * @param  array $input
     * Contains the 'payment' and 'card' details
     */
    protected function createEnrollRequestFields($input)
    {
        $payment = $input['payment'];

        $card = $input['card'];

        $this->enrollRequest['url'] = Hdfc\Urls::ENROLL_URL;

        $data = &$this->enrollRequest['data'];

        $data['trackid'] = $payment['id'];

        // Convert amount from integer to decimal
        $data['amt'] = $payment['amount'] / 100;

        // Collect udf fields
        $data['udf1'] = 'test';

        $data['udf2'] = $payment['email'];

        $data['udf3'] = $payment['contact'];

        $data['udf4'] = 'test';

        $data['udf5'] = 'test';

        $this->populateRiskUdfIfApplicable($data, $input);

        $this->populateUdf1IfApplicable($data, $input);

        $this->udfCheckAndMeetHdfcRequirements($data);

        $this->udfRemoveHackCharacters($data);

        // Collect fields related to the card
        $this->mapKeys($card, $this->cardKeyMappings, $data);

        // set the iso numeric currency code
        $currency = $payment['currency'];

        $data['currencycode'] = Currency::ISO_NUMERIC_CODES[$currency];

        $network = $input['card']['network_code'];

        $data['action'] = Action::AUTHORIZE;

        if (in_array($network, $this->purchase))
        {
            $data['action'] = Action::PURCHASE;
        }

        $url = $input['callbackUrl'];

        if ($this->env === 'dev')
        {
            $parts = parse_url($url);
            // $parts['host'] = 'https://dev.razorpay.com';
            // $url = $parts['host'] . $parts['path'];
            $parts['host'] = 'rzp.ngrok.com';
            $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        }

        // Only required in case of Rupay. Weird! But ... !
        if ($network === Card\Network::RUPAY)
        {
            $data['merchantResponseUrl'] = $url;
            $data['merchantErrorUrl'] = $url;
        }

        // This is crucial, please do not remove it
        unset($this->enrollRequest['content']);
    }

    /**
     * In the UDF field population, you cannot use <>(){}[]?&* ~`!#$%^=+|\\/:'\",;
     * characters in UDF as they are declared as Hack characters.
     * Each UDF can have a length of 250 charcters and only below special
     * characters can be use.
     *
     * 1. - (Minus)
     * 2. _(Underscore)
     * 3. @ At the Rate
     * 4. (Space)
     * 5. .(dot)
     *
     * @param  array      $data [description]
     */
    protected function udfCheckAndMeetHdfcRequirements(array & $data)
    {
        //
        // First remove the 'so-called bs' hack characters
        //
        $this->udfRemoveHackCharacters($data);

        //
        // Now, check the lengths and strip it up if above 250.
        //
        $this->udfStripExtraLength($data);
    }

    protected function udfRemoveHackCharacters(array & $data)
    {
        $hdfcHackChars = array(
            '<','>','(',')','{','}','[',']','?','&','*','~',
            '`','!','#','$','%','^','=','+','|','\\','/',':',
            '\'','"',',',';');

        foreach (range(1,5,1) as $i)
        {
            $data['udf'.$i] = str_replace($hdfcHackChars, ' ', $data['udf'.$i]);
        }
    }

    protected function udfStripExtraLength(array & $data)
    {
        foreach (range(1,5,1) as $i)
        {
            $udf = & $data['udf'.$i];

            $len = strlen($udf);

            if ($len > 250)
            {
                $start = $len - 250;

                $udf = substr($udf, $start);
            }
        }
    }

    protected function populateRiskUdfIfApplicable(array & $data, $input)
    {
        // We are passing additional information in UDF for the marketplace TID
        // for selected merchants on the basis of the feature flag.
        if (($input['terminal']->isShared() === true) and
            ($input['merchant']->isFeatureEnabled(Feature::FSS_RISK_UDF) === true))
        {
            // Collect udf fields
            $data['udf1'] = $input['merchant']->getBillingLabel();

            $data['udf4'] = $input['payment']['description'] ?? $data['udf4'];

            $data['udf5'] = $this->request->ip();
        }
    }

    protected function populateUdf1IfApplicable(array & $data, $input)
    {
        if ((isset($input['order']) === true) and
            (isset($input['order']['receipt']) === true))
        {
            $data['udf1'] = $input['order']['receipt'];
        }
        else if ((isset($input['payment']) === true) and
                (isset($input['payment']['order_id']) === true))
        {
            $orderId = $input['payment']['order_id'];

            $order = (new Order\Repository)->find($orderId);

            if (is_null($order) === false)
            {
                $data['udf1'] = $order->getReceipt();
            }
        }
    }

    protected function validateEnrollResponse()
    {
        $trackid = $this->enrollResponse['data']['trackid'];

        if ($trackid !== $this->id)
        {
            throw new Exception\LogicException(
                'Gateway Exception: Track id do not match',
                null,
                [
                     'id'       => $this->id,
                     'track_id' => $trackid,
                ]);
        }
    }

    /**
     * Stores relevant enroll response
     * fields in db depending on whether
     * enroll succeded or there was an
     * error.
     *
     * @return void
     */
    protected function persistAfterEnroll()
    {
        if ($this->error)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_ENROLL_ERROR,
                $this->enrollResponse);

            $this->model = $this->repo->persistAfterEnrollError(
                            $this->id,
                            $this->enrollResponse['error'],
                            $this->enrollRequest['data']);

            $this->id = $this->model->id;
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_ENROLL_RESPONSE,
                $this->enrollResponse);

            $this->model = $this->repo->persistAfterEnroll(
                    $this->enrollRequest['data'],
                    $this->enrollResponse['data']);
        }

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_PROCESSED,
            $this->input,
            null,
            [
                'enrolled' => Result::getEnrollmentStatus($this->model->enroll_result),
            ]);
    }

    /**
     * Check eci value and set it to 7 if not defined.
     * See eci field definition for more info.
     *
     * @return void
     * @throws Exception\LogicException
     */
    protected function checkAndSetEci()
    {
        $eci = null;

        if (isset($this->enrollResponse['data']['eci']))
        {
            $eci = &$this->enrollResponse['data']['eci'];
        }

        //
        // Set to 7 if it's null, that is we didn't receive a value.
        //
        $eci = (($eci === null) or ($eci === '')) ? '7' : $eci;

        //
        // If Visa/Diners Card Type is NOT Enrolled – Value “6”
        // If MasterCard/Maestro Card Type is NOT Enrolled – value “1”
        //

        $network = $this->input['card']['network'];
        $enroll = $this->enrollResponse['data']['enroll_result'];

        $notEnrolled = ($enroll === Payment\Result::NOT_ENROLLED);

        //
        // ECI checks only need to be done for NOT_ENROLLED cases
        //
        if ($notEnrolled === false)
            return;

        $visaOrDiners = (($network === Card\Network::VISA) or
                         ($network === Card\Network::DICL));

        if ($visaOrDiners)
        {
            //
            // For visa and diners, eci should be 6.
            //
            if ($eci === '6')
                return;

            throw new Exception\LogicException(
                'eci value should be 6',
                null,
                [
                    'eci' => $eci,
                ]);
        }

        $masterCardOrMaestro = (($network === Card\Network::MC) or
                                ($network === Card\Network::MAES));

        if ($masterCardOrMaestro)
        {
            //
            // For mastercard and maestro, eci should be 1.
            //
            if ($eci === '1')
                return;

            throw new Exception\LogicException(
                'eci value should be 1',
                null,
                [
                    'payment_id' => $this->input['payment']['id'],
                    'eci'        => $eci,
                ]);
        }
    }

    /**
     *
     * Checks for enroll result.
     *
     * If enroll result is anything other than
     * 'ENROLLED' and 'NOT ENROLLED' then we
     * consider enroll as failed and set an error
     * message to that effect
     *
     * @return bool
     */
    protected function isEnrollSuccess()
    {
        if ($this->error)
        {
            return false;
        }

        $result = &$this->enrollResponse['data']['result'];

        //
        // Check enroll result code.
        //
        list($result, $success) = Payment\Result::getResultCode($result);

        // Set the enroll result code irrespective of success/failure.
        $this->enrollResponse['data']['enroll_result'] = $result;

        if ($success === false)
        {
            $this->setErrorOnEnrollFailure();
        }

        return $success;
    }

    /**
     * In case enroll failed with an invalid enroll result
     * or with FSS0001 enroll result, then an error is
     * set here.
     *
     * Note: This function runs only when there is no generic error
     *       set earlier and proper enroll response is received.
     */
    protected function setErrorOnEnrollFailure()
    {
        assert($this->error === false);

        $enrollResult = $this->enrollResponse['data']['enroll_result'];

        $this->error = true;

        switch ($enrollResult)
        {
            case Payment\Result::FSS0001_ENROLLED:
                Hdfc\ErrorHandler::setErrorInResponse(
                    $this->enrollResponse,
                    Hdfc\ErrorCodes\ErrorCodes::FSS0001);
                break;

            case Payment\Result::UNKNOWN_ERROR_ENROLLED:
                //
                // If enroll failed with an invalid code, set error
                // for that and mark the operation as failure.
                //

                $this->enrollResponse['error'] =
                    Hdfc\ErrorHandler::getInvalidResultCodeError();
                break;

            case Payment\Result::AUTH_ERROR:
                Hdfc\ErrorHandler::setErrorInResponse(
                    $this->enrollResponse,
                    Hdfc\ErrorCodes\ErrorCodes::RP00010);
                break;

            case Payment\Result::NOT_SUPPORTED:
                Hdfc\ErrorHandler::setErrorInResponse(
                    $this->enrollResponse,
                    Hdfc\ErrorCodes\ErrorCodes::RP00012);
                break;

            default:
                throw new Exception\LogicException('Should not reach here');
        }

        $this->enrollResponse['error']['enroll_result'] = $this->enrollResponse['data']['enroll_result'];

        $this->trace(
            Trace::ERROR,
            TraceCode::GATEWAY_ENROLL_ERROR,
            $this->enrollResponse);
    }

    /**
     * Sets enroll status. In case of error it's false
     * The other allowed values are 'ENROLLED'
     * and NOT_ENROLLED
     *
     * @return  string
     */
    protected function getEnrollStatus()
    {
        if ($this->error)
        {
            $enrollStatus = false;
        }
        else
        {
            $enrollStatus = $this->enrollResponse['data']['enroll_result'];
        }

        return $enrollStatus;
    }

    protected function mapKeys($array, $map, &$data)
    {
        foreach ($map as $keyOld => $keyNew)
        {
            $data[$keyNew] = $array[$keyOld];
        }
    }

    /**
     * In some cases for enroll response we get only error_text in the xml without
     * the error_code_tag. This checks for this condition. The error_text tag value is of the form
     * <error_text>!ERROR!-code-description</error_text> . eg : <error_text>!ERROR!-GW00856-Invalid cvv.</error_text>
     */
    protected function checkAdditionalEnrollError(array &$response)
    {
        if ($this->error === true)
        {
            return;
        }

        $errorText = HDFC\Utility::getFieldFromXML($response['xml'], 'error_text');

        if ($errorText === null)
        {
            return;
        }

        $errorDetails = explode('-', $errorText);

        if ((isset($errorDetails[1]) === true) and (isset($errorDetails[2]) === true))
        {
            $response['error']['code']   = $errorDetails[1];
            $response['error']['text']   = $errorDetails[2];
            $response['error']['result'] = Hdfc\Utility::getFieldFromXML($response['xml'], 'result');

            $this->error = true;
        }
    }
}
