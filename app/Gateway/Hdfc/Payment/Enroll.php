<?php

namespace RZP\Gateway\Hdfc\Payment;

use RZP\Exception;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Hdfc\Payment;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Card;
use RZP\Models\Currency\Currency;

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
     * @return string
     */
    protected function enrollCard(array $input)
    {
        //
        // Fields to be sent to HDFC gateway for card-enrollment
        //
        $this->createEnrollRequestFields($input);

        $this->trace(
            Trace::DEBUG,
            TraceCode::GATEWAY_ENROLL_REQUEST,
            $this->enrollRequest);

        $network = $input['card']['network_code'];

        //
        // Only required in case of Rupay
        // TODO: This currently does not honour our PROXY_ENABLED
        // setting, which is set to false in production.
        //
        // We will shift it back once we have whitelisted FSS
        //
        if ($network === Card\Network::RUPAY)
        {
            $this->enrollRequest['options']['proxy'] = $this->proxy;
        }

        //
        // Send enroll request and receive response.
        // This function also checks for and sets
        // generic error
        //
        $error = $this->runRequestResponseFlow(
                    $this->enrollRequest,
                    $this->enrollResponse);

        //
        // If there is an error then just throw an exception
        //
        if ($error)
        {
            $this->persistAfterEnrollError();

            $this->throwException($this->enrollResponse['error'], true);
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
            $this->persistAfterEnrollError();

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

        $data = & $this->enrollRequest['data'];

        // set the iso numeric currency code
        $currency = $payment['currency'];

        $data = [
            'trackid'       => $payment['id'],
            'amt'           => $payment['amount'] / 100,
            'currencycode'  => Currency::ISO_NUMERIC_CODES[$currency],
            'action'        => $this->getActionForEnrollRequest($card),
        ];

        $this->addUdfFieldsToEnrollRequest($payment, $data);

        $this->addCardDetailsToEnrollRequest($card, $data);

        $url = $input['callbackUrl'];

        // This is required in case of rupay for handling
        // s2s callback during development.
        if ($this->env === 'dev')
        {
            $url = $this->getCallbackUrlForDev($url);
        }

        // Only required in case of Rupay. Weird! But ... !
        if ($card['network_code'] === Card\Network::RUPAY)
        {
            $data['merchantResponseUrl'] = $url;
            $data['merchantErrorUrl'] = $url;
        }

        // This is crucial, please do not remove it
        unset($this->enrollRequest['content']);
    }

    protected function getCallbackUrlForDev($url)
    {
        $parts = parse_url($url);

        // $parts['host'] = 'https://dev.razorpay.com';
        // $url = $parts['host'] . $parts['path'];

        $parts['host'] = 'rzp.ngrok.com';
        $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];

        return $url;
    }

    protected function getActionForEnrollRequest($card)
    {
        $action = Action::AUTHORIZE;

        if (in_array($card['network_code'], $this->purchaseNetworks))
        {
            $action = Action::PURCHASE;
        }

        return $action;
    }

    protected function addCardDetailsToEnrollRequest($card, & $data)
    {
        foreach ($this->cardKeyMappings as $rzpKey => $hdfcKey)
        {
            $data[$hdfcKey] = $card[$rzpKey];
        }
    }

    protected function addUdfFieldsToEnrollRequest($payment, & $data)
    {
        $udfData = [
            'udf1'      => 'test',
            'udf2'      => $payment['email'],
            'udf3'      => $payment['contact'],
            'udf4'      => 'test',
        ];

        $this->udfCheckAndMeetHdfcRequirements($udfData);

        $data = array_merge($data, $udfData);
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
     * @param  array $udfData
     */
    protected function udfCheckAndMeetHdfcRequirements(array & $udfData)
    {
        //
        // First remove the 'so-called bs' hack characters
        //
        $this->udfRemoveHackCharacters($udfData);

        //
        // Now, check the lengths and strip it up if above 250.
        //
        $this->udfStripExtraLength($udfData);
    }

    protected function udfRemoveHackCharacters(array & $udfData)
    {
        $hdfcHackChars = array(
            '<','>','(',')','{','}','[',']','?','&','*','~',
            '`','!','#','$','%','^','=','+','|','\\','/',':',
            '\'','"',',',';');

        foreach ($udfData as $udfKey => $udfValue)
        {
            $data[$udfKey] = str_replace($hdfcHackChars, ' ', $udfValue);
        }
    }

    protected function udfStripExtraLength(array & $udfData)
    {
        foreach ($udfData as $udfKey => $udfValue)
        {
            $len = strlen($udfValue);

            if ($len > 250)
            {
                $start = $len - 250;

                $udfData[$udfKey] = substr($udfValue, $start);
            }
        }
    }

    protected function validateEnrollResponse()
    {
        $trackId = $this->enrollResponse['data']['trackid'];

        $paymentId = $this->input['payment']['id'];

        if ($trackId !== $paymentId)
        {
            throw new Exception\LogicException(
                'Gateway Exception: Track id do not match: ' . $trackId . ' ' . $paymentId);
        }
    }

    /**
     * Stores relevant enroll response
     * fields in db on enroll success
     *
     * @return void
     */
    protected function persistAfterEnroll()
    {
        $this->trace(
            Trace::INFO,
            TraceCode::GATEWAY_ENROLL_RESPONSE,
            $this->enrollResponse);

        $this->model = $this->repo->persistAfterEnroll(
                $this->enrollRequest['data'],
                $this->enrollResponse['data']);

    }

    protected function persistAfterEnrollError()
    {
        $this->trace(
            Trace::ERROR,
            TraceCode::GATEWAY_ENROLL_ERROR,
            $this->enrollResponse);

        $this->model = $this->repo->persistAfterEnrollError(
            $this->enrollRequest['data'],
            $this->enrollResponse['error']);
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
            $eci = & $this->enrollResponse['data']['eci'];
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
        {
            return;
        }

        $visaOrDiners = (($network === Card\Network::VISA) or
                         ($network === Card\Network::DICL));

        if ($visaOrDiners)
        {
            //
            // For visa and diners, eci should be 6.
            //
            if ($eci === '6')
            {
                return;
            }

            throw new Exception\LogicException('eci value should be 6. Eci: ' . $eci);
        }

        $masterCardOrMaestro = (($network === Card\Network::MC) or
                                ($network === Card\Network::MAES));

        if ($masterCardOrMaestro)
        {
            //
            // For mastercard and maestro, eci should be 1.
            //
            if ($eci === '1')
            {
                return;
            }

            throw new Exception\LogicException('eci value should be 1. Eci: ' . $eci);
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
        $result = & $this->enrollResponse['data']['result'];

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
        $enrollResult = $this->enrollResponse['data']['enroll_result'];

        switch ($enrollResult)
        {
            case Payment\Result::FSS0001_ENROLLED:
                $errorCode = Hdfc\ErrorCode::FSS0001;
                break;

            case Payment\Result::UNKNOWN_ERROR_ENROLLED:
                //
                // If enroll failed with an invalid code, set error
                // for that and mark the operation as failure.
                //
                $errorCode = Hdfc\ErrorCode::getInvalidResultCodeErrorCode();
                break;

            case Payment\Result::AUTH_ERROR:
                $errorCode = Hdfc\ErrorCode::RP00010;
                break;

            case Payment\Result::NOT_SUPPORTED:
                $errorCode = Hdfc\ErrorCode::RP00012;
                break;

            default:
                throw new Exception\LogicException('Should not reach here');
        }

        $this->enrollResponse['error'] = Hdfc\ErrorHandler::getErrorDetails($errorCode);

        $this->enrollResponse['error']['enroll_result'] = $this->enrollResponse['data']['enroll_result'];

        $this->trace(
            Trace::ERROR,
            TraceCode::GATEWAY_ENROLL_ERROR,
            $this->enrollResponse);
    }

    /**
     * Sets enroll status.
     * Allowed values are 'ENROLLED'
     * and NOT_ENROLLED
     *
     * @return  string
     */
    protected function getEnrollStatus()
    {
        return $this->enrollResponse['data']['enroll_result'];
    }
}
