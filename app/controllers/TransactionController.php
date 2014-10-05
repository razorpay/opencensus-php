<?php

use Models\Api;

use Http\AppResponse;
use Models\Transaction;

class TransactionController extends BaseController
{
    public function postIndex($mode, $resource)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $input['resource'] = $resource;

        $status = (new Transaction\Service)->process($input, $mode);

        return ['status' => $status];
    }

    public function getAnalytics($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $input['merchant_id'] = Auth::merchant()->id();

        $data = (new Transaction\Service)->getAnalytics($input, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getAggregations($mode)
    {
        $this->checkMode($mode);

        $merchant_id = Auth::merchant()->id();

        $data = (new Transaction\Service)->getAggregations($merchant_id, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getTransactions($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Api\Service)->fetchCollection($input, $mode, 'transaction');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getTransaction($mode, $id = null)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchEntity($input, $mode, 'transaction');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPayments($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Api\Service)->fetchCollection($input, $mode, 'payment');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPayment($mode, $id = null)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchEntity($id, $mode, 'payment');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPaymentRefunds($mode, $id = null)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchPaymentRefunds($id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postCapturePayment($mode, $id = null)
    {
        $this->checkMode($mode);

        $amount = Input::get('amount');

        $error = (new Api\Service)->capturePayment($id, $amount, $mode);

        return AppResponse::jsonResponse($error);
    }

    public function postRefundPayment($mode, $id = null)
    {
        $this->checkMode($mode);

        $amount = Input::get('amount');

        $error = (new Api\Service)->refundPayment($id, $amount, $mode);

        return AppResponse::jsonResponse($error);
    }

    public function getRefunds($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Api\Service)->fetchCollection($input, $mode, 'refund');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getRefund($mode, $id = null)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchEntity($id, $mode, 'refund');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getSettlements($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Api\Service)->fetchCollection($input, $mode, 'settlement');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getSettlement($mode, $id = null)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchEntity($id, $mode, 'settlement');

        return AppResponse::jsonResponse($error, $data);
    }

    protected function checkMode($mode)
    {
        if ($mode !== 'live' and $mode !== 'test')
        {
            throw new \Exception('Invalid Mode');
        }
    }
}
