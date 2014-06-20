<?php

namespace Gateway\HdfcGateway;

use Trace\Trace;
use Trace\TraceEvent;

trait HdfcGatewayEnrollCard
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
            TraceEvent::GATEWAY_ENROLL_REQUEST,
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
            return false;
        }

        // Checks and sets eci if needed
        $this->checkAndSetEci();

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
            $this->setErrorOnEnrollFailure();

            return false;
        }

        $this->validateEnrollResponse();

        $this->persistAfterEnroll();

        $this->setEnrollStatus();

        return true;
    }

    protected function getErrorOnEnrollFailure()
    {
        $er = $this->enrollResponse;

        $error = HdfcGatewayErrorHandler::translateError(
                      $er['error']['code']);

        return array('failed', $error);
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

        // Collect creds
        list($data['id'], $data['password']) = static::getCredentials();

        $data['trackid'] = $txn['id'];

        // Convert amount from integer to decimal
        $data['amt'] = $txn['amount']/100;

        // Collect udf fields
        $data['udf1'] = 'junk';

        $data['udf2'] = $txn['udf']['email'];

        $data['udf3'] = $txn['udf']['contact'];

        $data['udf4'] = 'junk';

        $data['udf5'] = 'junk';

        // Collect fields related to the card
        $this->mapKeys($card, $this->cardKeyMappings, $data);

        //
        // Write currency code manually.
        // Later change it to something better
        // when we support multiple currencies
        //
        $data['currencycode'] = self::INR_CODE;

        $data['action'] = HdfcGatewayAction::AUTH;
    }

    protected function validateEnrollResponse()
    {
        $trackid = $this->enrollResponse['data']['trackid'];

        if ($trackid !== $this->id)
        {
            throw new InvalidArgumentException('Gateway Exception: Track id do not match');
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
            $this->model = HdfcGatewayDal::persistAfterEnrollError(
                            $this->id,
                            $this->enrollResponse['error']);

            $this->trace(
                Trace::ERROR,
                TraceEvent::GATEWAY_ENROLL_ERROR,
                $this->enrollResponse);
        }
        else
        {
            $this->model = HdfcGatewayDal::persistAfterEnroll(
                    $this->enrollRequest['data'],
                    $this->enrollResponse['data']);

            $this->trace(
                Trace::INFO,
                TraceEvent::GATEWAY_ENROLL_RESPONSE,
                $this->enrollResponse);
        }
    }

    /**
     * Get the fields from xml response
     * of the enrolling crad
     */
    protected function parseEnrollResponseEci()
    {
        $result = &$this->enrollResponse['data']['result'];

        $this->enrollResponse['data']['enroll_result'] = HdfcGatewayResult::resultCode($result);
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

        $eci = (($eci === null) or ($eci === '')) ? '7' : $eci;
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
     *
     * @return void
     */
    protected function isEnrollSuccess()
    {
        $result = &$this->enrollResponse['data']['result'];

        //
        // Check enroll result code.
        // 'enrollSuccess' variable tells us whether
        // its a success code or failure.
        //
        list($result, $success) = HdfcGatewayResult::getResultCode($result);

        // Set the enroll result code irrespective of success/failure.
        $this->enrollResponse['data']['enroll_result'] = $result;

        return $success;
    }

    /**
     * In case enroll failed with a an invalid enroll result
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

        if ($enrollResult === HdfcGatewayResult::FSS0001_ENROLLED)
        {
            $this->enrollResponse['error'] =
                HdfcGatewayErrorHandler::getError(HdfcGatewayErrorCode::FSS0001);
        }
        else if ($enrollResult === HdfcGatewayResult::UNKNOWN_ERROR_ENROLLED)
        {
            //
            // If enroll failed with an invalid code, set error
            // for that and mark the operation as failure.
            //

            $this->enrollResponse['error'] =
                HdfcGatewayErrorHandler::getInvalidEnrollCodeError();
        }
        else
        {
            throw new \LogicException('Should not reach here');
        }

        $this->error = true;

        $this->trace(
            Trace::ERROR,
            TraceEvent::GATEWAY_ENROLL_ERROR,
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