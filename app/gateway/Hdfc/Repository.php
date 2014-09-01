<?php

namespace Gateway\Hdfc;

use EE\Exception;
use Gateway\Hdfc;
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
        if ($response['enroll_result'] === Hdfc\Result::ENROLLED)
        {
            $status = Hdfc\Status::ENROLLED;
        }
        else if ($response['enroll_result'] === Hdfc\Result::NOT_ENROLLED)
        {
            $status = Hdfc\Status::NOT_ENROLLED;
        }

        $attributes = array(
            'trackid' => $request['trackid'],
            'gateway_transaction_id' => $response['paymentid'],
            'action' => $request['action'],
            'enroll_result' => $response['enroll_result'],
            'status' => $status,
            'eci' => $response['eci']);

        $repo = $this->repo;

        return $this->createOrFail($attributes);
    }

    public function persistAfterEnrollError($id, array $error)
    {
        $attributes = array(
            'trackid' => $id,
            'action' => Hdfc\Action::AUTHORIZE,
            'error_code' => $error['code'],
            'error_text' => $error['text'],
            'enroll_result' => $error['enroll_result'],
            'status' => Hdfc\Status::ENROLL_FAILED);

        $repo = $this->repo;

        return $repo::createOrFail($attributes);
    }

    public function persistAfterAuthNotEnrolled($model, $data)
    {
        $attributes = array(
            'status' => Hdfc\Status::AUTHORIZED,
            'action' => Hdfc\Action::AUTHORIZE,
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
            'status'    => Hdfc\Status::AUTHORIZED,
            'result'    => $data['result'],
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
            'action' => Hdfc\Action::AUTHORIZE,
            'status' => Hdfc\Status::AUTH_NOT_ENROLL_FAILED,
            'error_code' => $error['code'],
            'error_text' => $error['text']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthEnrolledError($model, $error)
    {
        $attributes = array(
            'action' => Hdfc\Action::AUTHORIZE,
            'status' => Hdfc\Status::AUTH_ENROLL_FAILED,
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
            case Hdfc\Action::REFUND:
                $status = Hdfc\Status::REFUNDED;
                break;

            case Hdfc\Action::CAPTURE:
                $status = Hdfc\Status::CAPTURED;
                break;

            default:
                throw new Exception\LogicException('Should not rech here. action: ' . $action);
        }

        $attributes = array(
            'trackid'                => $responseData['trackid'],
            'gateway_transaction_id' => $responseData['tranid'],
            'action'                 => $requestData['action'],
            'status'                 => $status,
            'result'                 => $responseData['result'],
            'ref'                    => $responseData['ref'],
            'auth'                   => $responseData['auth'],
            'avr'                    => $responseData['avr'],
            'postdate'               => $responseData['postdate']);

        return $this->createOrFail($attributes);
    }

    public function persistAfterSupportTxnError($id, $gateway_transaction_id, array $error, $type)
    {
        $action = '';
        $status = '';
        switch($type)
        {
            case 'refund':
                $action = Hdfc\Action::REFUND;
                $status = Hdfc\Status::REFUND_FAILED;
                break;

            case 'capture':
                $action = Hdfc\Action::CAPTURE;
                $status = Hdfc\Status::CAPTURE_FAILED;
                break;
        }

        $attributes = array(
            'trackid'                   => $id,
            'gateway_transaction_id'    => $gateway_transaction_id,
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
}