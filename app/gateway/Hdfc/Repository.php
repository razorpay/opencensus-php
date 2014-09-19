<?php

namespace Gateway\Hdfc;

use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Transaction;
use Models\Base;

class Repository extends Base\Repository
{
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
            'trackid' => $id,
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
                $model = $repo::where('trackid','=',$id)->firstOrFail();
                $model->$responseFieldXml = $xml;
                $this->save($model);
                break;

            case 'refund':
            case 'capture':
                $model = $this->createOrFail($attributes);
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
        if ($response['enroll_result'] === Transaction\Result::ENROLLED)
        {
            $status = Transaction\Status::ENROLLED;
        }
        else if ($response['enroll_result'] === Transaction\Result::NOT_ENROLLED)
        {
            $status = Transaction\Status::NOT_ENROLLED;
        }

        $attributes = array(
            'trackid' => $request['trackid'],
            'gateway_transaction_id' => $response['paymentid'],
            'action' => $request['action'],
            'amount' => $request['amt'],
            'enroll_result' => $response['enroll_result'],
            'status' => $status,
            'eci' => $response['eci']);

        $repo = $this->repo;

        return $this->createOrFail($attributes);
    }

    public function persistAfterEnrollError($id, array $error, $requestdata)
    {
        $attributes = array(
            'trackid' => $id,
            'action' => Transaction\Action::AUTHORIZE,
            'amount' => $requestdata['amt'],
            'error_code' => $error['code'],
            'error_text' => $error['text'],
            'enroll_result' => $error['enroll_result'],
            'status' => Transaction\Status::ENROLL_FAILED);

        $repo = $this->repo;

        return $repo::createOrFail($attributes);
    }

    public function persistAfterAuthNotEnrolled($model, $data)
    {
        $attributes = array(
            'status' => Transaction\Status::AUTHORIZED,
            'action' => Transaction\Action::AUTHORIZE,
            'amount' => $data['amt'],
            'result' => $data['result'],
            'ref' => $data['ref'],
            'auth' => $data['auth'],
            'avr' => $data['avr'],
            'gateway_transaction_id' => $data['tranid'],
            'postdate' => $data['postdate']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthEnrolled($model, $data)
    {
        $attributes = array(
            'status'    => Transaction\Status::AUTHORIZED,
            'result'    => $data['result'],
            'amount'    => $data['amt'],
            'ref'       => $data['ref'],
            'auth'      => $data['auth'],
            'avr'       => $data['avr'],
            'postdate'  => $data['postdate']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthNotEnrolledError($model, $error)
    {
        $attributes = array(
            'action' => Transaction\Action::AUTHORIZE,
            'status' => Transaction\Status::AUTH_NOT_ENROLL_FAILED,
            'error_code' => $error['code'],
            'error_text' => $error['text']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthEnrolledError($model, $error)
    {
        $attributes = array(
            'action' => Transaction\Action::AUTHORIZE,
            'status' => Transaction\Status::AUTH_ENROLL_FAILED,
            'error_code' => $error['code'],
            'error_text' => $error['text']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterSupportTxn($requestData, $responseData)
    {
        $status = '';
        $action = $requestData['action'];

        switch($action)
        {
            case Transaction\Action::REFUND:
                $status = Transaction\Status::REFUNDED;
                break;

            case Transaction\Action::CAPTURE:
                $status = Transaction\Status::CAPTURED;
                break;

            default:
                throw new Exception\LogicException('Should not rech here. action: ' . $action);
        }

        $attributes = array(
            'trackid'                => $responseData['trackid'],
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

    public function persistAfterSupportTxnError($id, $requestdata, array $error, $type)
    {
        $action = '';
        $status = '';
        switch($type)
        {
            case 'refund':
                $action = Transaction\Action::REFUND;
                $status = Transaction\Status::REFUND_FAILED;
                break;

            case 'capture':
                $action = Transaction\Action::CAPTURE;
                $status = Transaction\Status::CAPTURE_FAILED;
                break;
        }

        $attributes = array(
            'trackid'                   => $id,
            'gateway_transaction_id'    => $requestdata['transid'],
            'amount'                    => $requestdata['amount'],
            'error_code'                => $error['code'],
            'error_text'                => $error['result'],
            'action'                    => $action,
            'status'                    => $status);

        return $this->createOrFail($attributes);
    }

    public function retrieve($id)
    {
        $repo = $this->repo;

        return $repo::where('trackid','=',$id)->firstOrFail();
    }

    public function retrieveMultipleTransactions(array $ids)
    {
        $repo = $this->repo;

        return $repo::whereIn('trackid', $ids)->get();
    }

    public function retrieveCapturedTransactions(array $ids)
    {
        $repo = $this->repo;

        return $repo::whereIn('trackid', $ids)
                    ->where('status', '=', Transaction\Status::CAPTURED)
                    ->get();
    }

    public function fetchBetweenTimestamps($from, $to)
    {
        $repo = $this->repo;

        return $repo::whereBetween('created_at', $from, $to);
    }
}