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
     * @param  array $input array containing payment
     *                         and card details
     * @param  string $type should be either 'capture'
     *                         or 'refund'
     * @return array
     * @throws Exception\LogicException
     */
    protected function supportPayment($input, $type)
    {
        if (($type === 'refund') and
            ($this->isRefundNotRequiredOnGateway($input, $type)))
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

    /**
     * When refund request comes to gateway, it's not necessary that
     * we always call gateway refund. For cases like a card authorization on hdfc
     * mastercard or visa etc., since we did not capture a transaction,
     * there is no reason to call refund here.
     *
     * Also, we call refund on gateway when a payment has been captured on mastercard, visa.
     * But sometime capture times out. So in those cases, we need to check for those status
     * and still proceed with refund.
     */
    protected function isRefundNotRequiredOnGateway($input, $type)
    {
        assert ($type === 'refund');

        $paymentId = $input['payment']['id'];

        // Gets the first gateway entity matching the action
        // authorize or purchase
        $gatewayEntity = $this->repo->findByPaymentIdToVerify($paymentId);

        $gatewayAction = (int) $gatewayEntity->getAction();

        $gatewayStatus = $gatewayEntity->getStatus();

        // Now this is the first payment,
        // either the action : purchase and status : captured
        // or the action : authorize and status : authorized
        if (($gatewayAction === Action::AUTHORIZE) and
            ($gatewayStatus === Payment\Status::AUTHORIZED))
        {
            // Check if a captured entity is also present. If yes, refund is required on the gateway.
            $capturedEntity = $this->repo->retrieveCapturedOrAcceptedCaptureError($paymentId);

            if ($capturedEntity !== null)
            {
                return false;
            }

            return true;
        }
        else if (($gatewayAction === Action::PURCHASE) and
                 ($gatewayStatus === Payment\Status::CAPTURED))
        {
            return false;
        }

        return false;
    }

    protected function canForceRefund($input)
    {
        if ($this->isRefundRequired($input) === false)
        {
            return false;
        }

        $paymentId = $input['payment'][PaymentModel\Entity::ID];
        $refundId = $input['refund'][PaymentModel\Refund\Entity::ID];

        $gatewayPaymentEntities = $this->repo->findByPaymentId($paymentId);

        // There should be at least one authorized entity and exactly one refund entity.
        // In purchase transactions, there will be two entities. In others, there will be 3.
        if ($gatewayPaymentEntities->count() < 2)
        {
            return false;
        }

        $hasValidRefundOrCaptureEntityForAllowingRefund = $this->hasValidRefundOrCaptureEntityForAllowingRefund(
                                                                            $refundId, $paymentId);

        if ($hasValidRefundOrCaptureEntityForAllowingRefund === false)
        {
            return false;
        }

        // The transaction id for the refund should be present. Otherwise, it means that
        // the refund should come via normal flow and not via manualGatewayRefund.
        assert ($input['refund'][PaymentModel\Refund\Entity::TRANSACTION_ID] !== null);

        return true;
    }

    protected function hasValidRefundOrCaptureEntityForAllowingRefund($refundId, $paymentId)
    {
        $response = true;

        $gatewayRefundEntities = $this->repo->findByRefundId($refundId);

        // There should be only one gateway entity for refund.
        // This one gateway entity should have the result as DENIED_BY_RISK and
        // status as refunded.
        if (($gatewayRefundEntities->count() > 1) or
            ($gatewayRefundEntities[0]->getResult() !== Result::DENIED_BY_RISK) or
            ($gatewayRefundEntities[0]->getStatus() !== Status::REFUNDED))
        {
            $response = false;
        }

        // But, manual gateway refund can be done even if there's a captured entity or
        // a captured failed entity with GW00176 error code.
        // THIS CONDITION IS DANGEROUS BECAUSE it allows a gateway refund on a captured entity.
        // HENCE THIS MUST BE USED WITH CAUTION. Proper checks MUST BE PERFORMED before calling this function.
        if ($response === false)
        {
            $gatewayCapturedEntities = $this->repo->retrieveCapturedOrAcceptedCaptureError($paymentId);

            if ($gatewayCapturedEntities->count() > 0)
            {
                return true;
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
            $response = $this->isRefundRequiredWhenMultipleGatewayEntities($gatewayEntities);
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

    protected function isRefundRequiredWhenMultipleGatewayEntities($gatewayEntities)
    {
        $response = true;

        foreach ($gatewayEntities->all() as $gatewayEntity)
        {
            // Refunded record will be created only if an actual refund has taken place.
            // Hence, if already refunded, we don't need to run the refund again.

            // But, if the result is denied_by_risk, mark it as refund is required. This is because
            // there was a bug earlier where we had marked them as successfully refunded even though
            // they were not refunded. The bug is now fixed.
            if (($gatewayEntity->getStatus() === Payment\Status::REFUNDED) and
                ($gatewayEntity->getResult() !== Result::DENIED_BY_RISK))
            {
                $response = false;
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
        assert($input['payment'][PaymentModel\Entity::STATUS] === PaymentModel\Status::REFUNDED);

        assert($input['payment'][PaymentModel\Entity::CAPTURED] === false);
    }
}
