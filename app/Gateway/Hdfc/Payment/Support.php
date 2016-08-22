<?php

namespace RZP\Gateway\Hdfc\Payment;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Hdfc;
use RZP\Models\Payment as PaymentModel;
use RZP\Gateway\Hdfc\Payment;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Card;

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
     * @throws Exception\LogicException
     */
    protected function supportPayment($input, $type)
    {
        if ($this->isRefundingAuthorizedPayment($input, $type))
        {
            return;
        }

        $this->model = $this->retrievePreviousGatewayTransaction($input, $type);

        $result = $this->model['result'];

        //
        // If the result is captured, and support type is capture request,
        // then we need to check whether it was a purchase txn or auth.
        // For purchase txn, simply return back from here.
        //
        if (($result === Result::CAPTURED) and
            ($type === 'capture'))
        {
            if (in_array($input['card']['network_code'], $this->purchase))
            {
                return;
            }
            else
            {
                throw new Exception\LogicException(
                    'Illogical place reached',
                    null,
                    ['input' => $input, 'model' => $this->model, 'type' => $type]);
            }
        }

        //
        // Mark the type of support payment.
        // It will be either 'capture' or 'refund'
        //
        $this->setSupportPaymentType($type);

        //
        // Fill the fields required for the payment
        //
        $this->createSupportPaymentRequestFields($input);

        $this->trace->debug(
            TraceCode::GATEWAY_SUPPORT_REQUEST,
            $this->supportPaymentRequest);

        $this->runRequestResponseFlow(
            $this->supportPaymentRequest,
            $this->supportPaymentResponse);


        $this->verifyAndSaveSupportResponse($type, $input);
    }

    protected function retrievePreviousGatewayTransaction($input, $type)
    {
        $status = null;

        if ($type === 'capture')
        {
            $status = Status::AUTHORIZED;

            // For purchase transactions, status will be captured.
            if (in_array($input['card']['network_code'], $this->purchase))
            {
                $status = Status::CAPTURED;
            }
        }
        else if ($type === 'refund')
        {
            $status = Status::CAPTURED;
        }

        if ($status === Status::CAPTURED)
        {
            $this->model = $this->repo->retrieveCapturedOrAcceptedCaptureError(
                                            $input['payment']['id']);
        }
        else
        {
            $this->model = $this->repo->retrieveByPaymentIdAndStatusOrFail(
                                            $input['payment']['id'], $status);
        }

        $this->id = $input['payment']['id'];

        return $this->model;
    }

    protected function isCapturedSuccessfully($paymentId)
    {
        try
        {
            // Currently, not checking for GW00176 (retrieveCapturedOrAcceptedCaptureError).
            // We should add this later in case we get more issues.
            $capturedGatewayEntity = $this->repo->retrieveByPaymentIdAndStatusOrFail($paymentId, Status::CAPTURED);

            // Ideally, the action should always be either Purchase or Capture only here, since
            // it's a captured record.
            // If the action is anything else, it is a bug and should be fixed separately.
            if (($capturedGatewayEntity->getAction() === Action::PURCHASE) or
                ($capturedGatewayEntity->getAction() === Action::CAPTURE))
            {
                return true;
            }

            return false;
        }
        catch (\Exception $ex)
        {
            return false;
        }
    }

    protected function isSupportPaymentSuccess()
    {
        if ($this->error)
        {
            return false;
        }

        $response = & $this->supportPaymentResponse;

        Result::modifySpecificResultValueIfRequired($response['data']['result']);

        $result = $response['data']['result'];

        $success = Payment\Result::isResultCodeIndicatingSuccess($result);

        if ($success === false)
        {
            $errorCode = Hdfc\ErrorCode::getErrorCodeForResult($result);

            Hdfc\ErrorHandler::setErrorInResponse($response, $errorCode);

            $this->error = true;

            return false;
        }

        return true;
    }

    protected function isAnAcceptedError()
    {
        assert ($this->error === true);

        $response = $this->supportPaymentResponse;

        $error = $response['error'];
        $input = $this->input;

        if (($this->action === Base\Action::CAPTURE) and
            ($error['code'] === Hdfc\ErrorCode::GW00176) and
            ($input['payment']['status'] === 'authorized') and
            ($input['payment']['amount_authorized'] === (int) $input['amount']))
        {
            $this->trace->error(
                TraceCode::PAYMENT_CAPTURE_FORCED,
                $this->supportPaymentResponse);

            $this->error = null;

            return true;
        }

        return false;
    }

    protected function setSupportPaymentType($type)
    {
        assert(($type === 'capture') or
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
        $card = $input['card'];

        $this->supportPaymentRequest['url'] = Hdfc\Urls::SUPPORT_PAYMENT_URL;

        $data = &$this->supportPaymentRequest['data'];
        $data = [];

        $type = $this->supportPaymentRequest['type'];

        $action = constant(Action::class.'::'.strtoupper($type));

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

        // For refund, udf should not be PaymentID
        if ($type !== 'refund')
        {
            $data['udf5'] = 'PaymentID';
        }

        // However if it's Rupay, then udf5 need to be PaymentID
        // even for RuPay
        if ($input['card']['network'] === 'RuPay')
        {
            $data['udf5'] = 'PaymentID';
        }
    }

    protected function verifyAndSaveSupportResponse($type, $input)
    {
        $data = $this->supportPaymentResponse['data'];

        if ($this->error === false)
        {
            $this->validateSupportPaymentTrackId();

            $this->validatePostDate($data['postdate']);

            $this->isSupportPaymentSuccess();
        }

        $this->persistAfterSupportPayment($type, $input);

        if ($this->error)
        {
            if ($this->isAnAcceptedError() === true)
            {
                return;
            }

            // This is being done to enable testing of capture timeout queue.
            // Removes stale data.
            $error = $this->supportPaymentResponse['error'];
            $this->supportPaymentResponse['error'] = [];
            $this->error = false;

            $this->throwException($error);
        }
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

        // We throw an error after persisting the error data.
        // This is done in the calling function.
        if ($this->error)
        {
            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_SUPPORT_ERROR,
                $this->supportPaymentResponse);

            $this->model = $this->repo->persistAfterSupportPaymentError(
                                $this->supportPaymentRequest['data'],
                                $this->supportPaymentResponse['data'],
                                $this->supportPaymentResponse['error'],
                                $type,
                                $paymentId,
                                $refundId);
        }
        else
        {
            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_SUPPORT_RESPONSE,
                $this->supportPaymentResponse);

            $this->model = $this->repo->persistAfterSupportPayment(
                    $this->supportPaymentRequest['data'],
                    $this->supportPaymentResponse['data'],
                    $paymentId,
                    $refundId);
        }
    }

    protected function isRefundingAuthorizedPayment($input, $type)
    {
        if ($type === 'refund')
        {
            $id = $input['payment']['id'];

            $gatewayEntity = $this->repo->findByPaymentIdToVerify($id);

            $gatewayAction = (int) $gatewayEntity->getAction();

            $gatewayStatus = $gatewayEntity->getStatus();

            // Now this is the first payment,
            // either the action : purchase and status : captured
            // or the action : authorize and status : authorized
            if (($gatewayAction === Action::AUTHORIZE) and
                ($gatewayStatus === Payment\Status::AUTHORIZED))
            {
                // Check if there exists a captured one as well
                $capturedEntity = $this->repo->findByPaymentIdAndStatus($id, Payment\Status::CAPTURED);

                $count = $capturedEntity->count();

                if ($count === 0)
                {
                    return true;
                }

                return false;

            }
            else if (($gatewayAction === Action::PURCHASE) and
                     ($gatewayStatus === Payment\Status::CAPTURED))
            {
                return false;
            }

        }

        return false;
    }

    protected function isRefundRequired($input)
    {
        $id = $input['payment']['id'];

        $gatewayEntities = $this->repo->findByPaymentId($id);

        // No refund required for
        // - authorize, authorize is the only entity
        // - refunded entity is available.

        $count = $gatewayEntities->count();

        if ($count === 1)
        {
            // If there is only one entity, implies the transaction
            // for capture never happened. Adding a check on payment for the
            // same.
            $this->assertPaymentRefundedWithoutCapture($input);

            $entity = $gatewayEntities->first();

            $gatewayAction = (int) $entity->getAction();

            $gatewayStatus = $entity->getStatus();

            // When the count is one, it is possible that the action is purchase.
            // For purchase transactions, the status will always be captured.
            // Hence, count=1 is valid situation for refund for these kind of transactions.
            if (($gatewayAction === Action::PURCHASE) and
                ($gatewayStatus === Payment\Status::CAPTURED))
            {
                    return true;
            }
            else if (($entity->getAction() === Action::AUTHORIZE) and
                     ($entity->getStatus() === Payment\Status::AUTHORIZED))
            {
                    return false;
            }
            else
            {
                //should not reach here
                throw new Exception\LogicException(
                    'Only available entity for hdfc gateway payment is in an'.
                    'unacceptable state.',
                    null,
                    $input);
            }
        }
        else
        {
            foreach ($gatewayEntities->all() as $gatewayEntity)
            {
                // Refunded record will be created only if an actual refund has taken place.
                // Hence, if already refunded, we don't need to run the refund again.
                if ($gatewayEntity->getStatus() === Payment\Status::REFUNDED)
                {
                    return false;
                }
            }

            return true;
        }
    }

    protected function assertPaymentRefundedWithoutCapture($input)
    {
        assert($input['payment'][PaymentModel\Entity::STATUS] === PaymentModel\Status::REFUNDED);

        assert($input['payment'][PaymentModel\Entity::CAPTURED] === false);
    }
}
