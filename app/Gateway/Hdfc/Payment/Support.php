<?php

namespace RZP\Gateway\Hdfc\Payment;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Models\Payment as PaymentModel;
use RZP\Gateway\Hdfc\Payment;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Card;

trait Support
{
    /**
     * Forms the crux of doing support
     * payments (capture and refund).
     *
     * @param  array $input array containing payment
     *                         and card details
     * @param  string $type should be either 'capture'
     *                         or 'refund'
     * @return array
     * @throws Exception\LogicException
     */
    protected function supportPayment($input, $type)
    {
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
            if ((in_array($input['card']['network_code'], $this->purchase, true)) or
                ($input['payment']['auth_type'] === PaymentModel\AuthType::PIN))
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

        if ($type === 'refund')
        {
            return [
                PaymentModel\Gateway::GATEWAY_RESPONSE => json_encode($this->supportPaymentResponse['xml']),
                PaymentModel\Gateway::GATEWAY_KEYS     => $this->getGatewayData($this->supportPaymentResponse['data'])
            ];
        }
    }

    protected function retrievePreviousGatewayTransaction($input, $type)
    {
        $status = null;

        if ($type === 'capture')
        {
            $status = Status::AUTHORIZED;

            // For purchase transactions, status will be captured.
            if ((in_array($input['card']['network_code'], $this->purchase, true)) or
                (isset($input['payment']['auth_type']) === true and $input['payment']['auth_type'] === PaymentModel\AuthType::PIN))
            {
                $status = Status::CAPTURED;
            }
        }
        else if ($type === 'refund')
        {
            $status = Status::CAPTURED;
        }

        if (($status === Status::CAPTURED) and ($type === 'refund'))
        {
            $entity = $this->repo->retrieveCapturedOrAcceptedCaptureFailures(
                                            $input['payment']['id']);
            if (empty($entity) === true)
            {
                throw new Exception\LogicException(
                    'Captured Entity not found in DB',
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    [
                        'payment_id'  => $input['payment']['id'],
                    ]);
            }

            $this->model = $entity;
        }
        else if ($status === Status::CAPTURED)
        {
            $this->model = $this->repo->retrieveCapturedOrAcceptedCaptureErrorOrFail(
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
            $this->trace->traceException($ex, null, null, ['payment_id' => $paymentId]);

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
            $errorCode = Hdfc\ErrorCodes\ErrorCodes::getErrorCodeForResult($result);

            Hdfc\ErrorHandler::setErrorInResponse($response, $errorCode);

            if (isset($response['data']['authRespCode']))
            {
                $response['error']['authRespCode'] = $response['data']['authRespCode'];
            }

            $this->error = true;

            return false;
        }

        return true;
    }

    protected function isAnAcceptedError()
    {
        assertTrue ($this->error === true);

        $response = $this->supportPaymentResponse;

        $error = $response['error'];
        $input = $this->input;

        if (($this->action === Base\Action::CAPTURE) and
            ($error['code'] === Hdfc\ErrorCodes\ErrorCodes::GW00176) and
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
        assertTrue(($type === 'capture') or
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

        $data = & $this->supportPaymentRequest['data'];
        $data = [];

        $type = $this->supportPaymentRequest['type'];

        $action = constant(Action::class . '::' . strtoupper($type));

        $data['action'] = $action;

        //
        // Convert amount from integer to decimal
        //
        $data['amt'] = $input['amount'] / 100;

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

        //
        // For Rupay Cards, gateway_transaction_id and gateway_payment_id are always different
        // unlike cases for other card types.
        // Previously we didn't stored transaction Id,
        // and we populated gateway_payment_id in gateway_transaction_id for older payments
        // This need to be done till data is fixed for older payments
        //
        if ($input['card']['network'] === 'RuPay')
        {
            if ($data['transid'] === null)
            {
                $data['transid'] = $this->model->getGatewayPaymentId();
                $data['udf5'] = 'PaymentID';
            }
        }

        $data['udf5'] = '';

        $this->setDebitSecondRecurringPayment($input);

        if (($this->secondDebitRecurringFlag === true) and
            ($type === 'refund'))
        {
            $data['trackid'] = $input['payment']['id'];
            $data['transid'] = $data['trackid'];
            $data['udf5']    = 'TrackID';
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
        $trackId = $this->supportPaymentResponse['data']['trackid'];

        if ($trackId !== $this->supportPaymentRequest['data']['trackid'])
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

    protected function hasValidRefundOrCaptureEntityForAllowingRefund($refundId, $paymentId)
    {
        $response = true;

        $gatewayRefundEntities = $this->repo->findByRefundIdOrderedById($refundId);

        //
        // If there is only one refund entity and we are running manual refund, this should
        // be in refunded state with error code denied by risk (because of a previous bug in the code).
        // If there are multiple refund entities, it means that it was denied by risk multiple times
        // by the gateway. Since the bug has been fixed, the status should be refund_failed.
        // If the above conditions don't match, it means we are doing manual refund on something that
        // we should not.
        //
        // This is with the assumption that multiple attempts for the refund were not made during
        // the bug period (where status is refunded and error code is denied by risk)
        //
        if ($gatewayRefundEntities->count() !== 0)
        {
            if ($gatewayRefundEntities->count() === 1)
            {
                if (($gatewayRefundEntities[0]->getResult() !== Result::DENIED_BY_RISK) or
                    ($gatewayRefundEntities[0]->getStatus() !== Status::REFUNDED))
                {
                    $response = false;
                }
            }
            else
            {
                if (($gatewayRefundEntities[0]->getResult() !== Result::DENIED_BY_RISK) or
                    ($gatewayRefundEntities[0]->getStatus() !== Status::REFUND_FAILED))
                {
                    $response = false;
                }
            }
        }

        // But, manual gateway refund can be done even if there's a captured entity or
        // a captured failed entity with GW00176 error code.
        // THIS CONDITION IS DANGEROUS BECAUSE it allows a gateway refund on a captured entity.
        // HENCE THIS MUST BE USED WITH CAUTION. Proper checks MUST BE PERFORMED before calling this function.
        if (($gatewayRefundEntities->count() === 0) or ($response === false))
        {
            $gatewayCapturedEntities = $this->repo->retrieveCapturedOrAcceptedCaptureError($paymentId);

            if ($gatewayCapturedEntities->count() === 0)
            {
                $response = false;
            }
        }

        return $response;
    }

    protected function isRefundRequired(array $input)
    {
        $id = $input['payment']['id'];

        $gatewayEntities = $this->repo->findByPaymentId($id);

        // No refund required for
        // - authorize, authorize is the only entity
        // - refunded entity is available.

        $count = $gatewayEntities->count();

        if ($count === 1)
        {
            $response = $this->isRefundRequiredWhenOneGatewayEntity($input, $gatewayEntities);
        }
        else
        {
            $response = $this->isRefundRequiredWhenMultipleGatewayEntities($gatewayEntities, $input['refund']['id']);
        }

        $this->trace->info(
            TraceCode::REFUND_GATEWAY_REQUIRED,
            [
                'payment_id'            => $input['payment'][PaymentModel\Entity::ID],
                'refund_id'             => $input['refund'][PaymentModel\Refund\Entity::ID],
                'is_refund_required'    => $response
            ]
        );

        return $response;
    }

    protected function isRefundRequiredWhenMultipleGatewayEntities($gatewayEntities, $refundId)
    {
        $response = true;

        foreach ($gatewayEntities->all() as $gatewayEntity)
        {
            // Refunded record will be created only if an actual refund has taken place.
            // Hence, if already refunded, we don't need to run the refund again.

            // Even if it does have, the result should be DENIED_BY_RISK.
            // This is because there was a bug earlier where we had marked them as successfully
            // refunded even though they were not refunded. The bug is now fixed.
            if (($gatewayEntity->getStatus() === Payment\Status::REFUNDED) and
                ($gatewayEntity->getRefundId() === $refundId))
            {
                if ($gatewayEntity->getResult() !== Result::DENIED_BY_RISK)
                {
                    $response = false;
                }
            }
        }

        return $response;
    }

    protected function isRefundRequiredWhenOneGatewayEntity(array $input, $gatewayEntities)
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
            $response = true;
        }
        else if (($entity->getAction() === Action::AUTHORIZE) and
            ($entity->getStatus() === Payment\Status::AUTHORIZED))
        {
            $response = false;
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

        return $response;
    }

    protected function assertPaymentRefundedWithoutCapture($input)
    {
        assertTrue($input['payment'][PaymentModel\Entity::STATUS] === PaymentModel\Status::REFUNDED);

        assertTrue($input['payment'][PaymentModel\Entity::CAPTURED] === false);
    }

    protected function decideAuthenticationGateway($input)
    {
        if ((isset($input['authenticate']['gateway']) === true) and
            ($input['authenticate']['gateway'] === PaymentModel\Gateway::MPI_ENSTAGE))
        {
            $authenticationGateway = PaymentModel\Gateway::MPI_ENSTAGE;
        }
        else
        {
            $authenticationGateway = PaymentModel\Gateway::MPI_BLADE;
        }

        return $authenticationGateway;
    }

    protected function callAuthenticationGateway(array $input, $authenticationGateway)
    {
        return $this->app['gateway']->call(
            $authenticationGateway,
            $this->action,
            $input,
            $this->mode);
    }

    public function otpGenerate(array $input)
    {
        if ((isset($input['otp_resend']) === true) and
            ($input['otp_resend'] === true))
        {
            return $this->otpResend($input);
        }

        return $this->authorize($input);
    }

    public function otpResend(array $input)
    {
        parent::action($input, Base\Action::OTP_RESEND);

        $mpiEntity = $this->app['repo']
                          ->mpi
                          ->findByPaymentIdAndActionOrFail($input['payment']['id'], Base\Action::AUTHORIZE);

        if ($mpiEntity->getGateway() !== PaymentModel\Gateway::MPI_ENSTAGE)
        {
            //
            // This error is consistent with error thrown in otpResend trait
            throw new Exception\LogicException(
                'Gateway does not support OTP resend',
                null,
                ['payment_id' => $input['payment']['id']]);
        }

        $authenticationGateway = $mpiEntity->getGateway();

        $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

        return $authResponse;
    }
}
