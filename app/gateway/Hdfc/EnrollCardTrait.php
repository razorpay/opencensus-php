<?php

namespace Gateway\Hdfc;

use EE\Exception;
use Gateway\Hdfc;
use Models\Card;
use Trace\Trace;
use Trace\TraceCode;

trait EnrollCardTrait
{
    /**
     * Sends request for enrolling the card
     * with hdfc gateway
     *
     * @param  array $input
     * Should contain 'txn' and 'card' arrays
     *
     */
    protected function enrollCard(array $input)
    {
        $this->setId($input['txn']['id']);

        //
        // Fields to be sent to HDFC gateway for card-enrollment
        //
        $this->createEnrollRequestFields($input);

        $this->trace(
            Trace::DEBUG,
            TraceCode::GATEWAY_ENROLL_REQUEST,
            $this->enrollRequest);

        //
        // Send enroll request and receive response.
        // This function also checks for and sets
        // generic error
        //
        $this->runRequestResponseFlow(
            $this->enrollRequest,
            $this->enrollResponse);

        //
        // If there is an error then just return
        //
        if ($this->error)
        {
            $this->throwException($this->enrollResponse['error']);
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

            $this->throwException($this->enrollResponse['error']);
        }

        //
        // Checks and sets eci if needed
        //
        $this->checkAndSetEci();

        $this->validateEnrollResponse();

        $this->persistAfterEnroll();

        $this->setEnrollStatus();
    }

    /**
     * Collect all fields to be sent for
     * enrolling the card
     *
     * @param  array $input
     * Contains the 'txn' and 'card' details
     */
    protected function createEnrollRequestFields($input)
    {
        $txn = $input['txn'];

        $card = $input['card'];

        $data = &$this->enrollRequest['data'];

        $data['trackid'] = $txn['id'];

        // Convert amount from integer to decimal
        $data['amt'] = $txn['amount']/100;

        // Collect udf fields
        $data['udf1'] = 'junk';

        $data['udf2'] = $txn['email'];

        $data['udf3'] = $txn['contact'];

        $data['udf4'] = 'junk';

        $data['udf5'] = 'junk';

        $this->udfCheckAndMeetHdfcRequirements($data);

        $this->udfRemoveHackCharacters($data);

        //
        // Collect fields related to the card
        //
        $this->mapKeys($card, $this->cardKeyMappings, $data);

        //
        // Write currency code manually.
        // Later change it to something better
        // when we support multiple currencies
        //
        $data['currencycode'] = self::INR_CODE;

        $data['action'] = Hdfc\Action::AUTHORIZE;
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
     * @return [type]       [description]
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

    protected function validateEnrollResponse()
    {
        $trackid = $this->enrollResponse['data']['trackid'];

        if ($trackid !== $this->id)
        {
            throw new Exception\LogicException(
                'Gateway Exception: Track id do not match: ' . $trackid . ' ' . $this->id);
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
            $this->model = $this->repo->persistAfterEnrollError(
                            $this->id,
                            $this->enrollResponse['error']);

            $this->id = $this->model->id;

            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_ENROLL_ERROR,
                $this->enrollResponse);
        }
        else
        {
            $this->model = $this->repo->persistAfterEnroll(
                    $this->enrollRequest['data'],
                    $this->enrollResponse['data']);

            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_ENROLL_RESPONSE,
                $this->enrollResponse);
        }
    }

    /**
     * Check eci value and set it to 7 if not defined.
     * See eci field definition for more info.
     *
     * @return void
     */
    protected function checkAndSetEci()
    {
        $eci = &$this->enrollResponse['data']['eci'];

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

        $notEnrolled = ($enroll === Hdfc\Result::NOT_ENROLLED);

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
                return;

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
     * @return void
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
        // 'enrollSuccess' variable tells us whether
        // its a success code or failure.
        //
        list($result, $success) = Hdfc\Result::getResultCode($result);

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
        Assert($this->error === false);

        $enrollResult = $this->enrollResponse['data']['enroll_result'];

        $this->error = true;

        switch ($enrollResult)
        {
            case Hdfc\Result::FSS0001_ENROLLED:
                Hdfc\ErrorHandler::setErrorInResponse(
                    $this->enrollResponse,
                    Hdfc\ErrorCode::FSS0001);
                break;

            case Hdfc\Result::UNKNOWN_ERROR_ENROLLED:
                //
                // If enroll failed with an invalid code, set error
                // for that and mark the operation as failure.
                //

                $this->enrollResponse['error'] =
                    Hdfc\ErrorHandler::getInvalidResultCodeError();
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
     * @return  void
     */
    protected function setEnrollStatus()
    {
        //
        // By default, enrollStatus should be null
        //

        Assert($this->enrollStatus === null);

        if ($this->error)
            $this->enrollStatus = false;
        else
            $this->enrollStatus = $this->enrollResponse['data']['enroll_result'];
    }

    /**
     * Returns enrollStatus
     *
     * @return void
     */
    protected function getEnrollStatus()
    {
        return $this->enrollStatus;
    }
}