<?php

namespace Gateway\Hdfc\Payment;

use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Payment;
use Trace\Trace;
use Trace\TraceCode;

trait Support
{
    /**
     * Forms the crux of doing support
     * payments (capture and refund).
     *
     * @param  array    $input array containing payment
     *                         and card details
     * @param  string   $type  should be either 'capture'
     *                         or 'refund'
     * @return array
     */
    protected function supportPayment($input, $type)
    {
        if ($this->isRefundingAuthorizedPayment($input, $type))
        {
            return;
        }

        $this->retrievePreviousGatewayTransaction($input, $type);

        //
        // Mark the type of support payment.
        // It will be either 'capture' or 'refund'
        //
        $this->setSupportPaymentType($type);

        //
        // Fill the fields required for the payment
        //
        $this->createSupportPaymentRequestFields($input);

        $this->trace(
            TRACE::DEBUG,
            TraceCode::GATEWAY_SUPPORT_REQUEST,
            $this->supportPaymentRequest);

        $this->runRequestResponseFlow(
            $this->supportPaymentRequest,
            $this->supportPaymentResponse);

        if ($this->error === false)
        {
            $this->validateSupportPaymentResponse();
        }

        $this->persistAfterSupportPayment($type, $input);

        if ($this->error)
        {
            $this->throwException($this->supportPaymentResponse['error']);
        }
    }

    protected function retrievePreviousGatewayTransaction($input, $type)
    {
        $status = null;

        if ($type === 'capture')
        {
            $status = Status::AUTHORIZED;
        }
        else if ($type === 'refund')
        {
            $status = Status::CAPTURED;
        }

        $this->model = $this->repo->retrieveByPaymentIdAndStatus(
            $input['payment']['id'], $status);

        $this->id = $input['payment']['id'];

        return $this->model;
    }

    protected function isSupportPaymentSuccess()
    {
        if ($this->error)
        {
            return false;
        }

        $response = & $this->supportPaymentResponse;

        $result = $response['data']['result'];

        //
        // Check enroll result code.
        // 'enrollSuccess' variable tells us whether
        // its a success code or failure.
        //
        switch ($result)
        {
            // See Payment\Result for code details
            case Payment\Result::CAPTURED:
                break;

            case Payment\Result::NOT_CAPTURED:
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

    protected function setSupportPaymentType($type)
    {
        Assert(($type === 'capture') or
               ($type === 'refund'));

        $this->supportPaymentRequest['type'] = $type;

        $this->supportPaymentResponse['type'] = $type;
    }

    /**
     * Collect all fields to be sent for
     * payment refund/capture
     *
     * @param  array $input
     * Contains the 'payment' details
     */
    protected function createSupportPaymentRequestFields($input)
    {
        $payment = $input['payment'];

        $card = $input['card'];

        $data = &$this->supportPaymentRequest['data'];

        $type = $this->supportPaymentRequest['type'];

        $action = constant(__NAMESPACE__.'\Action::'.strtoupper($type));

        $data['action'] = $action;

        //
        // Convert amount from integer to decimal
        //
        $data['amt'] = $input['amount']/100;

        $data['member'] = $card['name'];

        $data['transid'] = $this->model->gateway_transaction_id;

        if ($type === 'refund')
        {
            $data['trackid'] = $input['refund']['id'];
        }
        else if ($type === 'capture')
        {
            $data['trackid'] = $input['payment']['id'];
        }
    }

    protected function validateSupportPaymentResponse()
    {
        $data = $this->supportPaymentResponse['data'];

        $this->validateSupportPaymentTrackId();

        $this->validatePostDate($data['postdate']);
    }

    /**
     * Checks that trackid is in response is same as the
     * one in request sent
     *
     * @return void
     */
    protected function validateSupportPaymentTrackId()
    {
        $trackid = $this->supportPaymentResponse['data']['trackid'];

        if ($trackid !== $this->supportPaymentRequest['data']['trackid'])
        {
            throw new Exception\InvalidArgumentException(
                'Gateway Exception: Track id do not match');
        }
    }

    protected function persistAfterSupportPayment($type, $input)
    {
        $paymentId = $input['payment']['id'];
        $refundId = null;

        if ($type === 'refund')
        {
            $refundId = $input['refund']['id'];
        }

        if ($this->error)
        {

            $this->model = $this->repo->persistAfterSupportPaymentError(
                                $this->supportPaymentRequest['data'],
                                $this->supportPaymentResponse['error'],
                                $type,
                                $paymentId,
                                $refundId);

            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_SUPPORT_ERROR,
                $this->supportPaymentResponse);
        }
        else
        {
            $this->model = $this->repo->persistAfterSupportPayment(
                    $this->supportPaymentRequest['data'],
                    $this->supportPaymentResponse['data'],
                    $paymentId,
                    $refundId);

            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_SUPPORT_RESPONSE,
                $this->supportPaymentResponse);
        }
    }

    protected function isRefundingAuthorizedPayment($input, $type)
    {
        return (($type === 'refund') and
                ($input['payment']['status'] === 'authorized'));
    }
}