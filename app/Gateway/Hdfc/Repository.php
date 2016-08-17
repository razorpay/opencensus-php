<?php

namespace RZP\Gateway\Hdfc;

use RZP\Exception;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Hdfc\Payment;
use RZP\Gateway\Hdfc\Payment\Action;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Hdfc';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'auth'                          => 'sometimes|string|max:6',
        'gateway_transaction_id'        => 'sometimes|numeric|digits:16',
        'ref'                           => 'sometimes|numeric|digits:12');

    public function findByPaymentIdToVerify($id)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->whereIn('action', [Action::AUTHORIZE, Action::PURCHASE])
                    ->first();
    }

    public function findCapturedPaymentById($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Action::CAPTURE)
                    ->firstOrFail();
    }

    public function persistAfterEnroll($request, $response)
    {
        $result = $response['enroll_result'];

        $status = null;

        if ($result === Payment\Result::ENROLLED)
        {
            $status = Payment\Status::ENROLLED;
        }
        else if ($result === Payment\Result::NOT_ENROLLED)
        {
            $status = Payment\Status::NOT_ENROLLED;
        }
        else if ($result === Payment\Result::INITIALIZED)
        {
            $status = Payment\Status::INITIALIZED;
        }

        //
        // 'received' is marked as false because after this we will
        // initiate auth request. And 'received' is marked as true
        // only after that if we receive positive result.
        //

        $attributes = array(
            'received'                  => '0',
            'payment_id'                => $request['trackid'],
            'gateway_transaction_id'    => $response['paymentid'],
            'action'                    => $request['action'],
            'amount'                    => $request['amt'],
            'enroll_result'             => $response['enroll_result'],
            'status'                    => $status,
            'eci'                       => $response['eci']);

        return $this->createOrFail($attributes);
    }

    public function persistAfterEnrollError($id, array $error, $requestData)
    {
        $enrollResult = null;

        if (isset($error['enroll_result']) === true)
        {
            $enrollResult = $error['enroll_result'];
        }

        $attributes = array(
            'received'              => '1',
            'payment_id'            => $id,
            'action'                => $requestData['action'],
            'amount'                => $requestData['amt'],
            'error_code'            => $error['code'],
            'error_text'            => $error['text'],
            'enroll_result'         => $enrollResult,
            'status'                => Payment\Status::ENROLL_FAILED);

        return $this->createOrFail($attributes);
    }

    public function persistAfterAuthNotEnrolled($model, $data)
    {
        $status = Payment\Status::AUTHORIZED;

        if ($data['result'] === Payment\Result::CAPTURED)
        {
            $status = Payment\Status::CAPTURED;
        }

        $attributes = array(
            'received'      => '1',
            'payment_id'    => $data['trackid'],
            'status'        => $status,
            'amount'        => $data['amt'],
            'result'        => $data['result'],
            'ref'           => $data['ref'],
            'auth'          => $data['auth'],
            'avr'           => $data['avr'],
            'postdate'      => $data['postdate'],
            'gateway_transaction_id' => $data['tranid']);

        $model->fill($attributes);

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterAuthEnrolled($model, $data)
    {
        $status = Payment\Status::AUTHORIZED;

        if ($data['result'] === Payment\Result::CAPTURED)
        {
            $status = Payment\Status::CAPTURED;
        }

        $attributes = array(
            'received'      => '1',
            'payment_id'    => $data['trackid'],
            'status'        => $status,
            'result'        => $data['result'],
            'ref'           => $data['ref'],
            'auth'          => $data['auth'],
            'avr'           => $data['avr'],
            'postdate'      => $data['postdate']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthNotEnrolledError($model, $authResponse)
    {
        $error = $authResponse['error'];

        $result = null;

        if (isset($authResponse['data']['result']))
        {
            $result = $authResponse['data']['result'];
        }

        $attributes = array(
            'received'      => '1',
            'status'        => Payment\Status::AUTH_NOT_ENROLL_FAILED,
            'result'        => $result,
            'error_code'    => $error['code'],
            'error_text'    => $error['text']);

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

        $attributes = array(
            'received'      => '1',
            'status'        => Payment\Status::AUTH_ENROLL_FAILED,
            'result'        => $result,
            'error_code'    => $error['code'],
            'error_text'    => $error['text']);

        $model->fill($attributes);

        $this->saveOrFail($model);
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
                throw new Exception\LogicException('Should not reach here. Action: ' . $action);
        }

        $attributes = array(
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
            'postdate'                  => $responseData['postdate']);

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

        $attributes = array(
            'received'                  => '1',
            'payment_id'                => $paymentId,
            'refund_id'                 => $refundId,
            'gateway_transaction_id'    => $requestData['transid'],
            'amount'                    => $requestData['amt'],
            'error_code'                => $error['code'],
            'error_text'                => $errorText,
            'action'                    => $action,
            'status'                    => $status,
            'result'                    => $result);

        return $this->createOrFail($attributes);
    }

    public function retrieve($id)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)->firstOrFail();
    }

    public function retrieveCapturedOrAcceptedCaptureError($id)
    {
        $payment = $this->newQuery()
                        ->where('payment_id', '=', $id)
                        ->where('status', '=', Payment\Status::CAPTURED)
                        ->first();

        if ($payment !== null)
        {
            return $payment;
        }

        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->where('error_code', '=', ErrorCode::GW00176)
                    ->firstOrFail();
    }

    public function retrieveByPaymentIdAndStatus($id, $status)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->where('status', '=', $status)
                    ->firstOrFail();
    }

    public function retrieveMultiplePayments(array $ids)
    {
        return $this->newQuery()
                    ->whereIn('payment_id', $ids)->get();
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
                    ->whereBetween('created_at', $from, $to);
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
                    ->where('error_code', '=', $error)
                    ->first();
    }

    public function findByPaymentId($id)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->get();
    }

    public function findByPaymentIdAndStatus($id, $status)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->where('status', '=', $status)
                    ->get();
    }
}
