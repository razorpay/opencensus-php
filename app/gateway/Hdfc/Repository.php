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
        $attributes = array(
            'trackid' => $request['trackid'],
            'transactionid' => $response['paymentid'],
            'action' => $request['action'],
            'enroll_result' => $response['enroll_result'],
            'status' => Hdfc\Status::ENROLLED,
            'eci' => $response['eci']);

        $repo = $this->repo;

        return $this->createOrFail($attributes);
    }

    public function persistAfterEnrollError($id, array $error)
    {
        $attributes = array(
            'trackid' => $id,
            'error_code' => $error['code'],
            'error_service' => $error['service'],
            'error_text' => $error['text'],
            'enroll_result' => Hdfc\Result::FAIL_ENROLLED,
            'status' => Hdfc\Status::ENROLL_FAILED);

        $repo = $this->repo;

        return $repo::createOrFail($attributes);
    }

    public function persistAfterAuthNotEnrolled($model, $data)
    {
        $attributes = array(
            'status' => Hdfc\Status::AUTH,
            'auth_result' => $data['result'],
            'ref' => $data['ref'],
            'auth' => $data['auth'],
            'avr' => $data['avr'],
            'transactionid' => $data['tranid'],
            'postdate' => $data['postdate']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthEnrolled($model, $data)
    {
        $attributes = array(
            'status' => Hdfc\Status::AUTH,
            'auth_result' => $data['result'],
            'ref' => $data['ref'],
            'auth' => $data['auth'],
            'avr' => $data['avr'],
            'postdate' => $data['postdate']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthNotEnrolledError($model, $error)
    {
        $attributes = array(
            'status' => Hdfc\Status::AUTH_NOT_ENROLL_FAILED,
            'error_code' => $error['code'],
            'error_service' => $error['service'],
            'error_text' => $error['text']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterAuthEnrolledError($model, $error)
    {
        $attributes = array(
            'status' => Hdfc\Status::AUTH_ENROLL_FAILED,
            'error_code' => $error['code'],
            'error_service' => $error['service'],
            'error_text' => $error['text']);

        $model->fill($attributes);

        $this->saveOrFail($model);
    }

    public function persistAfterSupportTxn($requestData, $responseData)
    {
        $attributes = array(
            'trackid' => $responseData['trackid'],
            'transactionid' => $responseData['tranid'],
            'action' => $requestData['action'],
            'status' => $responseData['result'],
            'ref' => $responseData['ref'],
            'auth' => $responseData['auth'],
            'avr' => $responseData['avr'],
            'postdate' => $responseData['postdate']);

        return $this->createOrFail($attributes);
    }

    public function persistAfterSupportTxnError($id, $transactionid, array $error, $type)
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
            'trackid' => $id,
            'transactionid' => $transactionid,
            'error_code' => $error['code'],
            'error_text' => $error['result'],
            'action' => $action,
            'status' => $status);

        return $this->createOrFail($attributes);
    }

    public function retrieve($id)
    {
        $repo = $this->repo;

        return $repo::where('trackid','=',$id)->firstOrFail();
    }
}