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
            'amount'                    => $request->item[0]->unitPrice,
            'status'                    => $status,
            'ref'                       => $response->requestID);
        

        $repo = $this->repo;

        return $this->createOrFail($attributes);
    }

    public function persistAfterEnrollError($id, $error, $requestData)
    {
        $attributes = array(
            'payment_id'            => $id,
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

        $model = $repo::where('payment_id', '=', $id)->firstOrFail()
                    ->update([
                        'capture_ref' => $response->requestID,
                        'status' => $status
                    ]);

        return $model;
    }

    public function persistAfterCaptureError($id, $error, $requestData)
    {
        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail()
                    ->update([
                        'status' => Payment\Status::CAPTURE_FAILED,
                        'error_code' => $error->reasonCode
                    ]);

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

        $model = $repo::where('payment_id', '=', $id)->firstOrFail()
                    ->update([
                        'ref' => $response->requestID,
                        'status' => $status
                    ]);

        return $model;
    }

    public function persistAfterAuthorizeError($id, $error, $requestData)
    {
        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail()
                    ->update([
                        'status' => Payment\Status::AUTHORIZE_FAILED,
                        'error_code' => $error->reasonCode
                    ]);

        return $model;
    }

    public function persistAfterNotEnrolledAuthorize($id, $request, $response)
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

        $model = $repo::where('payment_id', '=', $id)->firstOrFail()
                    ->update([
                        'ref' => $response->requestID,
                        'status' => $status
                    ]);

        return $model;
    }

    public function persistAfterNotEnrolledAuthorizeError($id, $error, $requestData)
    {
        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail()
                    ->update([
                        'status' => Payment\Status::AUTHORIZE_FAILED,
                        'error_code' => $error->reasonCode
                    ]);

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

        if($cardType === 'MasterCard')
        {
            $model->auth_data = $response->payerAuthValidateReply->ucafAuthenticationData;
            $model->collection_indicator = $response->payerAuthValidateReply->ucafCollectionIndicator;
        }

        $this->saveOrFail($model);

        return $model;
    }

    public function persistAfterValidateError($id, $error, $requestData, $cardType)
    {
        $repo = $this->repo;

        $model = $repo::where('payment_id', '=', $id)->firstOrFail()
                    ->update([
                        'status' => Payment\Status::VALIDATE_FAILED,
                        'error_code' => $error->reasonCode
                    ]);

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
}
