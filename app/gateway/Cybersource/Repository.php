<?php

namespace Gateway\Cybersource;

use EE\Exception;
use Gateway\Cybersource\Payment;
use Gateway\Cybersource;
//use Gateway\Cybersource\Payment\Action;
use Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Cybersource';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'auth'                          => 'sometimes|string|max:6',
        'gateway_transaction_id'        => 'sometimes|numeric|digits:16',
        'ref'                           => 'sometimes|numeric|digits:12');

    public function findByPaymentIdToVerify($id)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $id)
                    ->whereIn('action', [Action::AUTHORIZE, Action::PURCHASE])
                    ->first();
    }

    public function persistAfterEnroll($id, $request, $response)
    {
        $result = $response->reasonCode;

        if ($result === Payment\Result::ENROLLED)
        {
            $status = Payment\Status::ENROLLED;
        }
        else if ($result === Payment\Result::NOT_ENROLLED)
        {
            $status = Payment\Status::NOT_ENROLLED;
        }

        $attributes = array(
            'payment_id'                => $id,
            'gateway_transaction_id'    => '1',
            'action'                    => '1',
            'amount'                    => $request->item[0]->unitPrice,
            'status'                    => $status,
            'ref'                       => $response->requestID);
        

        $repo = $this->repo;

        return $this->createOrFail($attributes);
    }

    public function persistAfterEnrollError($id, array $error, $requestData)
    {
        $attributes = array(
            'payment_id'            => $id,
            'action'                => '1',
            'amount'                => $requestData->item[0]->unitPrice,
            'error_code'            => $error->reasonCode,
            'error_text'            => '1',
            'status'                => Payment\Status::ENROLL_FAILED);

        $repo = $this->repo;

        return $repo::createOrFail($attributes);

    }

    public function persistAfterCapture($id, $request, $response)
    {
        $result = $response->reasonCode;

        if ($result === Payment\Result::CAPTURED)
        {
            $status = Payment\Status::CAPTURED;
        }
        else
        {
            throw new Exception\LogicException('Should not rech here.');
        }

        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail();

        $model->capture_ref = $response->requestID;

        $model->status = $status;

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterCaptureError($id, $error, $requestData)
    {
        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail();

        $model->status = Payment\Status::CAPTURE_FAILED;

        $model->error_code = $error->reasonCode;

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterAuthorize($id, $request, $response)
    {
        $result = $response->reasonCode;

        if ($result === Payment\Result::AUTHORIZED)
        {
            $status = Payment\Status::AUTHORIZED;
        }
        else
        {
            throw new Exception\LogicException('Should not rech here.');
        }

        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail();

        $model->ref = $response->requestID;

        $model->status = $status;

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterAuthorizeError($id, $error, $requestData)
    {
        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail();

        $model->status = Payment\Status::AUTHORIZE_FAILED;

        $model->error_code = $error->reasonCode;

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterValidate($id, $request, $response, $cardType)
    {
        $result = $response->reasonCode;

        if ($result === Payment\Result::VALIDATED)
        {
            $status = Payment\Status::VALIDATED;
        }
        else
        {
            throw new Exception\LogicException('Should not rech here.');
        }

        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail();

        $model->eci_raw = $response->payerAuthValidateReply->eciRaw;

        $model->commerce_indicator = $response->payerAuthValidateReply->commerceIndicator;

        $model->xid = $response->payerAuthValidateReply->xid;

        $model->pares_status = $response->payerAuthValidateReply->paresStatus;

        if($cardType === 'Visa')
        {
            $model->cavv = $response->payerAuthValidateReply->cavv;
        }

        // if($cardType === 'Mastercard')
        // {
        //     $model->
        // }

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterValidateError($id, $error, $requestData, $cardType)
    {
        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail();

        $model->status = Payment\Status::VALIDATE_FAILED;

        $model->error_code = $error->reasonCode;

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterAuthNotEnrolled($model, $data)
    {
        $status = Payment\Status::AUTHORIZED;

        if ($data['result'] === Payment\Result::CAPTURED)
        {
            $status = Payment\Status::CAPTURED;
        }

        $attributes = array(
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

    public function persistAfterAuthNotEnrolledError($model, $error)
    {
        $attributes = array(
            'status'        => Payment\Status::AUTH_NOT_ENROLL_FAILED,
            'error_code'    => $error['code'],
            'error_text'    => $error['text']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthEnrolledError($model, $error)
    {
        $attributes = array(
            'status'        => Payment\Status::AUTH_ENROLL_FAILED,
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
        $status = '';
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
                throw new Exception\LogicException('Should not rech here. action: ' . $action);
        }

        $attributes = array(
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
        $requestdata,
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

        $result = null;

        if (isset($error['result']))
        {
            $result = $error['result'];
        }

        $attributes = array(
            'payment_id'                => $paymentId,
            'refund_id'                 => $refundId,
            'gateway_transaction_id'    => $requestdata['transid'],
            'amount'                    => $requestdata['amt'],
            'error_code'                => $error['code'],
            'error_text'                => $result,
            'action'                    => $action,
            'status'                    => $status);

        return $this->createOrFail($attributes);
    }

    public function retrieve($id)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $id)->firstOrFail();
    }

    public function retrieveCapturedOrAcceptedCaptureError($id)
    {
        $repo = $this->repo;

        $payment = $repo::where('payment_id', '=', $id)
                  ->where('status', '=', Payment\Status::CAPTURED)
                  ->first();

        if ($payment !== null)
        {
            return $payment;
        }

        return $repo::where('payment_id', '=', $id)
                    ->where('error_code', '=', ErrorCode::GW00176)
                    ->firstOrFail();
    }

    public function retrieveByPaymentIdAndStatus($id, $status)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $id)
                  ->where('status', '=', $status)
                  ->firstOrFail();
    }

    public function retrieveMultiplePayments(array $ids)
    {
        $repo = $this->repo;

        return $repo::whereIn('payment_id', $ids)->get();
    }

    public function retrieveCapturedPayments(array $ids)
    {
        $repo = $this->repo;

        return $repo::whereIn('payment_id', $ids)
                    ->where('status', '=', Payment\Status::CAPTURED)
                    ->get();
    }

    public function retrieveRefunds(array $ids)
    {
        $repo = $this->repo;

        return $repo::whereIn('refund_id', $ids)
                    ->where('status', '=', Payment\Status::REFUNDED)
                    ->get();
    }

    public function fetchBetweenTimestamps($from, $to)
    {
        $repo = $this->repo;

        return $repo::whereBetween('created_at', $from, $to);
    }

    public function findByGatewayTransactionIdOrFail($gatewayTxnId)
    {
        $repo = $this->repo;

        return $repo::where('gateway_transaction_id', '=', $gatewayTxnId)->firstOrFail();
    }

    public function findByGatewayTransactionIdAndStatus($gatewayTxnId, $status)
    {
        $repo = $this->repo;

        return $repo::where('gateway_transaction_id', '=', $gatewayTxnId)
                    ->where('status', '=', $status)
                    ->first();
    }

    public function findByGatewayTransactionIdAndErrorCode($gatewayTxnId, $error)
    {
        $repo = $this->repo;

        return $repo::where('gateway_transaction_id', '=', $gatewayTxnId)
                    ->where('error_code', '=', $error)
                    ->first();
    }

    public function findByPaymentId($id)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $id)
                    ->get();
    }

    public function findByPaymentIdAndStatus($id, $status)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $id)
                    ->where('status', '=', $status)
                    ->get();
    }
}
