<?php

namespace Gateway\Hdfc;

use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Payment;
use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Hdfc';

    public function __construct()
    {
        $this->repo = __NAMESPACE__.'\Entity';

        parent::__construct();
    }

    public function saveXml($id, $xml, $responseType)
    {
        $oldRepo = $this->repo;

        $this->repo = __NAMESPACE__.'\ResponseXml';

        $repo = $this->repo;

        $attributes = array(
            'payment_id' => $id,
            $responseType => $xml);

        $model = null;

        switch($responseType)
        {
            case 'enroll':
                $model = $this->createOrFail($attributes);
                break;

            case 'auth_enrolled':
            case 'auth_not_enrolled':
                $responseFieldXml = $responseType;
                $model = $repo::where('payment_id','=',$id)->firstOrFail();
                $model->$responseFieldXml = $xml;
                $this->save($model);
                break;

            case 'refund':
            case 'capture':
                $model = $this->createOrFail($attributes);
                break;

            case 'inquiry':
                break;

            default:
                throw new Exception\InvalidArgumentException(
                                'Wrong responseType => '.$responseType);
        }

        $this->repo = $oldRepo;

        return $model;
    }

    public function persistAfterEnroll($request, $response)
    {
        if ($response['enroll_result'] === Payment\Result::ENROLLED)
        {
            $status = Payment\Status::ENROLLED;
        }
        else if ($response['enroll_result'] === Payment\Result::NOT_ENROLLED)
        {
            $status = Payment\Status::NOT_ENROLLED;
        }

        $attributes = array(
            'payment_id'            => $request['trackid'],
            'gateway_transaction_id'=> $response['paymentid'],
            'action'                => $request['action'],
            'amount'                => $request['amt'],
            'enroll_result'         => $response['enroll_result'],
            'status'                => $status,
            'eci'                   => $response['eci']);

        $repo = $this->repo;

        return $this->createOrFail($attributes);
    }

    public function persistAfterEnrollError($id, array $error, $requestdata)
    {
        $attributes = array(
            'payment_id'            => $id,
            'action'                => Payment\Action::AUTHORIZE,
            'amount'                => $requestdata['amt'],
            'error_code'            => $error['code'],
            'error_text'            => $error['text'],
            'enroll_result'         => $error['enroll_result'],
            'status'                => Payment\Status::ENROLL_FAILED);

        $repo = $this->repo;

        return $repo::createOrFail($attributes);
    }

    public function persistAfterAuthNotEnrolled($model, $data)
    {
        $attributes = array(
            'payment_id'    => $data['trackid'],
            'status'        => Payment\Status::AUTHORIZED,
            'action'        => Payment\Action::AUTHORIZE,
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
            $status = Payment\Status::CAPTURED;

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
            'action'        => Payment\Action::AUTHORIZE,
            'status'        => Payment\Status::AUTH_NOT_ENROLL_FAILED,
            'error_code'    => $error['code'],
            'error_text'    => $error['text']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthEnrolledError($model, $error)
    {
        $attributes = array(
            'action'        => Payment\Action::AUTHORIZE,
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
            'payment_id'             => $paymentId,
            'refund_id'              => $refundId,
            'gateway_transaction_id' => $responseData['tranid'],
            'amount'                 => $responseData['amt'],
            'action'                 => $requestData['action'],
            'status'                 => $status,
            'result'                 => $responseData['result'],
            'ref'                    => $responseData['ref'],
            'auth'                   => $responseData['auth'],
            'avr'                    => $responseData['avr'],
            'postdate'               => $responseData['postdate']);

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
        switch($type)
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

        $attributes = array(
            'payment_id'                => $paymentId,
            'refund_id'                 => $refundId,
            'gateway_transaction_id'    => $requestdata['transid'],
            'amount'                    => $requestdata['amt'],
            'error_code'                => $error['code'],
            'error_text'                => $error['result'],
            'action'                    => $action,
            'status'                    => $status);

        return $this->createOrFail($attributes);
    }

    public function retrieve($id)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $id)->firstOrFail();
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

    public function findByPaymentId($id)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $id)
                    ->get();
    }
}