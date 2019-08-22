<?php

namespace RZP\Gateway\Hdfc;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Hdfc\Payment;
use RZP\Gateway\Hdfc\Payment\Action;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'hdfc';

    protected $appFetchParamRules = [
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'auth'                          => 'sometimes|string|max:6',
        'gateway_transaction_id'        => 'sometimes|numeric|digits:16',
        'ref'                           => 'sometimes|numeric|digits:12'
    ];

    public function findByPaymentIdToVerify($id)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->whereIn('action', [Action::AUTHORIZE, Action::PURCHASE])
                    ->first();
    }

    public function findPaymentsByPaymentIdToVerify($id)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->whereIn('action', [Action::AUTHORIZE, Action::PURCHASE])
                    ->get();
    }

    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Action::CAPTURE)
                    ->firstOrFail();
    }

    public function persistAfterEnroll($request, $response)
    {
        switch($response['enroll_result'])
        {
            case Payment\Result::ENROLLED:
                $status = Payment\Status::ENROLLED;

                break;

            case Payment\Result::NOT_ENROLLED:
                $status = Payment\Status::NOT_ENROLLED;

                break;

            case Payment\Result::INITIALIZED:
                $status = Payment\Status::INITIALIZED;

                break;

            default:
                $status = null;
        }

        //
        // 'received' is marked as false because after this we will
        // initiate auth request. And 'received' is marked as true
        // only after that if we receive positive result.
        //

        $attributes = [
            'received'                  => '0',
            'payment_id'                => $request['trackid'],
            'gateway_transaction_id'    => $response['paymentid'],
            'gateway_payment_id'        => $response['paymentid'],
            'action'                    => $request['action'],
            'amount'                    => $request['amt'],
            'currency'                  => $request['currencycode'],
            'enroll_result'             => $response['enroll_result'],
            'status'                    => $status,
            'eci'                       => $response['eci']
        ];

        return $this->createOrFail($attributes);
    }

    public function persistAfterEnrollError($id, array $error, $requestData)
    {
        $enrollResult = null;

        if (isset($error['enroll_result']) === true)
        {
            $enrollResult = $error['enroll_result'];
        }

        $attributes = [
            'received'              => '1',
            'payment_id'            => $id,
            'action'                => $requestData['action'],
            'amount'                => $requestData['amt'],
            'currency'              => $requestData['currencycode'],
            'error_code2'           => $error['code'],
            'error_text'            => $error['text'],
            'enroll_result'         => $enrollResult,
            'status'                => Payment\Status::ENROLL_FAILED
        ];

        return $this->createOrFail($attributes);
    }

    public function persistAfterDebitPinAuth($request, $response)
    {
        $attributes = [
            'received'                  => '0',
            'payment_id'                => $request['trackid'],
            'gateway_payment_id'        => $response['paymentId'],
            'action'                    => $request['action'],
            'amount'                    => $request['amt'],
            'currency'                  => $request['currencycode'],
            'status'                    => $response['result'],
            'card'                      => $request['card'],
            'type'                      => $request['type'],
        ];

        return $this->createOrFail($attributes);
    }

    public function persistAfterDebitPinAuthError($request, $error)
    {
        $attributes = [
            'received'               => '0',
            'payment_id'             => $request['trackid'],
            'action'                 => $request['action'],
            'amount'                 => $request['amt'],
            'currency'               => $request['currencycode'],
            'card'                   => $request['card'],
            'type'                   => $request['type'],
            'error_code2'            => $error['code'],
            'error_text'             => $error['text'],
            'status'                 => Payment\Status::DEBIT_PIN_AUTHENTICATION_FAILED,
        ];

        return $this->createOrFail($attributes);
    }

    public function persistAfterDebitPinAuthorize($model, $data)
    {
        $status = Payment\Status::AUTHORIZED;

        if ($data['result'] === Payment\Result::CAPTURED)
        {
            $status = Payment\Status::CAPTURED;
        }

        $attributes = [
            'received'               => '1',
            'payment_id'             => $data['trackid'],
            'status'                 => $status,
            'result'                 => $data['result'],
            'ref'                    => $data['ref'],
            'auth'                   => $data['auth'],
            'postdate'               => $data['postdate'],
            'gateway_transaction_id' => $data['tranid'],
        ];

        $model->fill($attributes);

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterDebitPinAuthorizeError($model, $authResponse)
    {
        $error = $authResponse['error'];

        $result = null;

        if (isset($authResponse['data']['result']))
        {
            $result = $authResponse['data']['result'];
        }

        $attributes = [
            'received'    => '1',
            'status'      => Payment\Status::DEBIT_PIN_AUTHORIZATION_FAILED,
            'result'      => $result,
            'error_text'  => $error['text'],
            'error_code'  => $error['code'],
        ];

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthNotEnrolled($model, $data)
    {
        return $this->persistCallbackData($model, $data);
    }

    public function persistAfterAuthEnrolled($model, $data)
    {
        return $this->persistCallbackData($model, $data);
    }

    protected function persistCallbackData($model, $data)
    {
        $status = Payment\Status::AUTHORIZED;

        if ($data['result'] === Payment\Result::CAPTURED)
        {
            $status = Payment\Status::CAPTURED;
        }

        $attributes = [
            'received'               => '1',
            'payment_id'             => $data['trackid'],
            'status'                 => $status,
            'result'                 => $data['result'],
            'ref'                    => $data['ref'],
            'auth'                   => $data['auth'],
            'avr'                    => $data['avr'],
            'postdate'               => $data['postdate'],
            'gateway_transaction_id' => $data['tranid'],
        ];

        if (empty($data['amt']) === false)
        {
            $attributes['amount'] = $data['amt'];
        }

        if (empty($data['eci']) === false)
        {
            $attributes['eci'] = $data['eci'];
        }

        $model->fill($attributes);

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterAuthRecurring($request, $data)
    {
        $status = Payment\Status::AUTHORIZED;

        if ($data['result'] === Payment\Result::CAPTURED)
        {
            $status = Payment\Status::CAPTURED;
        }

        $attributes = [
            'received'      => '1',
            'payment_id'    => $data['trackid'],
            'status'        => $status,
            'action'        => $request['action'],
            'amount'        => $data['amt'],
            'result'        => $data['result'],
            'currency'      => $request['currencycode'],
            'ref'           => $data['ref'],
            'auth'          => $data['auth'],
            'avr'           => $data['avr'],
            'postdate'      => $data['postdate'],
            'gateway_transaction_id' => $data['tranid']
        ];

        return $this->createOrFail($attributes);
    }

    public function persistAfterPreAuth($request, $data)
    {
        $status = Payment\Status::AUTHORIZED;

        if ($data['result'] === Payment\Result::CAPTURED)
        {
            $status = Payment\Status::CAPTURED;
        }

        $attributes = [
            'received'      => '1',
            'payment_id'    => $data['trackid'],
            'status'        => $status,
            'action'        => $request['action'],
            'enroll_result' => $request['enrollmentflag'],
            'amount'        => $data['amt'],
            'result'        => $data['result'],
            'currency'      => $request['currencycode'],
            'ref'           => $data['ref'],
            'auth'          => $data['auth'],
            'avr'           => $data['avr'],
            'postdate'      => $data['postdate'],
            'gateway_transaction_id' => $data['tranid']
        ];

        return $this->createOrFail($attributes);
    }

    public function persistAfterAuthNotEnrolledError($model, $authResponse)
    {
        $error = $authResponse['error'];

        $result = null;

        if (isset($authResponse['data']['result']))
        {
            $result = $authResponse['data']['result'];
        }

        $attributes = [
            'received'      => '1',
            'status'        => Payment\Status::AUTH_NOT_ENROLL_FAILED,
            'result'        => $result,
            'error_code2'   => $error['code'],
            'error_text'    => $error['text']
        ];

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthEnrolledError($model, $authResponse)
    {
        $error = $authResponse['error'];

        $result = null;

        if (isset($authResponse['data']['result']))
        {
            $result = $authResponse['data']['result'];
        }

        $attributes = [
            'received'      => '1',
            'status'        => Payment\Status::AUTH_ENROLL_FAILED,
            'result'        => $result,
            'error_code2'   => $error['code'],
            'error_text'    => $error['text']
        ];

        if (empty($authResponse['data']['eci']) === false)
        {
            $attributes['eci'] = $authResponse['data']['eci'];
        }

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthRecurringError($request, $error)
    {
        $attributes = [
            'received'              => '1',
            'payment_id'            => $request['trackid'],
            'action'                => $request['action'],
            'amount'                => $request['amt'],
            'currency'              => $request['currencycode'],
            'error_code2'           => $error['code'],
            'error_text'            => $error['text'],
            'status'                => Payment\Status::AUTH_RECURRING_FAILED
         ];

        return $this->createOrFail($attributes);
    }

    public function persistAfterPreAuthError($request, $error)
    {
        $attributes = [
            'received'              => '1',
            'payment_id'            => $request['trackid'],
            'action'                => $request['action'],
            'amount'                => $request['amt'],
            'currency'              => $request['currencycode'],
            'enroll_result'         => $request['enrollmentflag'],
            'error_code2'           => $error['code'],
            'error_text'            => $error['text'],
            'status'                => Payment\Status::AUTHORIZE_FAILED
         ];

        return $this->createOrFail($attributes);
    }

    public function persistAfterSupportPayment(
        $requestData,
        $responseData,
        $paymentId,
        $refundId = null)
    {
        $action = $requestData['action'];

        switch($action)
        {
            case Payment\Action::REFUND:
                $status = Payment\Status::REFUNDED;
                break;

            case Payment\Action::CAPTURE:
                $status = Payment\Status::CAPTURED;
                break;

            default:
                throw new Exception\LogicException(
                    'Should not reach here.',
                    null,
                    [
                        'payment_id' => $paymentId,
                        'action'     => $action,
                    ]);
        }

        $attributes = [
            'received'                  => '1',
            'payment_id'                => $paymentId,
            'refund_id'                 => $refundId,
            'gateway_transaction_id'    => $responseData['tranid'],
            'amount'                    => $responseData['amt'],
            'action'                    => $requestData['action'],
            'status'                    => $status,
            'result'                    => $responseData['result'],
            'ref'                       => $responseData['ref'],
            'auth'                      => $responseData['auth'],
            'avr'                       => $responseData['avr'],
            'postdate'                  => $responseData['postdate']
        ];

        return $this->createOrFail($attributes);
    }

    public function persistAfterSupportPaymentError(
        $requestData,
        $responseData,
        array $error,
        $type,
        $paymentId,
        $refundId = null)
    {
        $action = '';
        $status = '';

        switch ($type)
        {
            case 'refund':
                $action = Payment\Action::REFUND;
                $status = Payment\Status::REFUND_FAILED;
                break;

            case 'capture':
                $action = Payment\Action::CAPTURE;
                $status = Payment\Status::CAPTURE_FAILED;
                break;
        }

        $errorText = $error['text'];

        if (isset($error['result']))
        {
            $errorText = $error['result'];
        }

        $result = null;

        if (isset($responseData['result']))
        {
            $result = $responseData['result'];
        }

        $attributes = [
            'received'                  => '1',
            'payment_id'                => $paymentId,
            'refund_id'                 => $refundId,
            'gateway_transaction_id'    => $requestData['transid'],
            'amount'                    => $requestData['amt'],
            'error_code2'               => $error['code'],
            'error_text'                => $errorText,
            'action'                    => $action,
            'status'                    => $status,
            'result'                    => $result
        ];

        return $this->createOrFail($attributes);
    }

    public function retrieve($id)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->firstOrFail();
    }

    public function retrieveCapturedOrAcceptedCaptureErrorOrFail($paymentId)
    {
        $payment = $this->newQuery()
                        ->where('payment_id', '=', $paymentId)
                        ->where('status', '=', Payment\Status::CAPTURED)
                        ->first();

        if ($payment !== null)
        {
            return $payment;
        }

        return $this->newQuery()
                    ->where('payment_id', '=', $paymentId)
                    ->where('status', '=', Payment\Status::CAPTURE_FAILED)
                    ->where('error_code2', '=', Hdfc\ErrorCodes\ErrorCodes::GW00176)
                    ->firstOrFail();
    }

    public function retrieveCapturedOrAcceptedCaptureFailures($paymentId)
    {
        $payment = $this->newQuery()
                        ->where('payment_id', '=', $paymentId)
                        ->where('status', '=', Payment\Status::CAPTURED)
                        ->first();

        if ($payment !== null)
        {
            return $payment;
        }

        $errorCodes = [Hdfc\ErrorCodes\ErrorCodes::GW00176, Hdfc\ErrorCodes\ErrorCodes::GW00177];

        return $this->newQuery()
                    ->where('payment_id', '=', $paymentId)
                    ->where('status', '=', Payment\Status::CAPTURE_FAILED)
                    ->whereIn('error_code2', $errorCodes)
                    ->first();
    }

    public function retrieveCapturedOrAcceptedCaptureError($paymentId)
    {
        try
        {
            return $this->retrieveCapturedOrAcceptedCaptureErrorOrFail($paymentId);
        }
        catch(\Exception $ex)
        {
            return null;
        }
    }

    public function retrieveByPaymentIdAndStatusOrFail($paymentId, $status)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $paymentId)
                    ->where('status', '=', $status)
                    ->firstOrFail();
    }

    public function retrieveMultiplePayments(array $ids)
    {
        return $this->newQuery()
                    ->whereIn('payment_id', $ids)
                    ->get();
    }

    public function retrieveCapturedPayments(array $ids)
    {
        return $this->newQuery()
                    ->whereIn('payment_id', $ids)
                    ->where('status', '=', Payment\Status::CAPTURED)
                    ->get();
    }

    public function retrieveRefunds(array $ids)
    {
        return $this->newQuery()
                    ->whereIn('refund_id', $ids)
                    ->where('status', '=', Payment\Status::REFUNDED)
                    ->get();
    }

    public function fetchBetweenTimestamps($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween('created_at', [$from, $to]);
    }

    public function findByGatewayPaymentIdOrFail(string $gatewayPaymentId)
    {
        return $this->newQuery()
                    ->where('gateway_payment_id', '=', $gatewayPaymentId)
                    ->firstOrFail();
    }

    public function findByGatewayTransactionIdOrFail($gatewayTxnId)
    {
        return $this->newQuery()
                    ->where('gateway_transaction_id', '=', $gatewayTxnId)
                    ->firstOrFail();
    }

    public function findByGatewayTransactionIdAndStatus($gatewayTxnId, $status)
    {
        return $this->newQuery()
                    ->where('gateway_transaction_id', '=', $gatewayTxnId)
                    ->where('status', '=', $status)
                    ->first();
    }

    public function findByGatewayTransactionIdAndErrorCode($gatewayTxnId, $error)
    {
        return $this->newQuery()
                    ->where('gateway_transaction_id', '=', $gatewayTxnId)
                    ->where('error_code2', '=', $error)
                    ->first();
    }

    public function findByRefundIdOrderedById($refundId, $direction = 'desc')
    {
        return $this->newQuery()
                    ->where('refund_id', '=', $refundId)
                    ->orderBy('id', $direction)
                    ->get();
    }

    public function findByPaymentIdAndStatus($id, $status)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->where('status', '=', $status)
                    ->get();
    }

    public function findSuccessfulRefundByRefundId($refundId)
    {
        $refundEntities =  $this->newQuery()
                                ->where('refund_id', '=', $refundId)
                                ->where('status', '=', Payment\Status::REFUNDED)
                                ->get();

        //
        // There should never be more than one successful gateway refund entity
        // for a given refund_id
        //

        if ($refundEntities->count() > 1)
        {
            throw new Exception\LogicException(
                'Multiple successful refund entities found for a refund ID',
                Error\ErrorCode::SERVER_ERROR_MULTIPLE_REFUNDS_FOUND,
                [
                    'refund_id' => $refundId,
                    'refund_entities' => $refundEntities->toArray()
                ]);
        }

        return $refundEntities;
    }
}
