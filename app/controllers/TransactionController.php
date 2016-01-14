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

        $error = (new Transaction\Service)->process($input, $mode);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Returns analytics data for a given time range and type
     *
     * If the fields are not present in the request, returns empty
     * response
     * @param  string $mode test|live
     */
    public function getAnalytics($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $input['merchant_id'] = Auth::user()->user()->getCurrentMerchantId();

        $data = (new Transaction\Service)->getAnalytics($input, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getAggregations($mode)
    {
        $this->checkMode($mode);

        $merchant_id = Auth::user()->user()->getCurrentMerchantId();

        $data = (new Transaction\Service)->getAggregations($merchant_id, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getPaymentAggregations($mode)
    {
        $this->checkMode($mode);

        $merchant_id = Auth::user()->user()->getCurrentMerchantId();

        $data = (new Transaction\Service)->getPaymentAggregations($merchant_id, $mode);

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

        list($error, $data) = (new Api\Service)->fetchEntity($id, $mode, 'transaction');

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

    public function postAddfunds($mode)
    {
        $this->checkMode($mode);

        $id = Input::get('razorpay_payment_id');

        $amount = Input::get('amount');

        $error = (new Api\Service)->capturePayment($id, $amount, $mode);

        return AppResponse::jsonResponse($error);
    }

    public function getGenerateReport($mode, $month, $year)
    {
        $this->checkMode($mode);

        list($error, $file) = (new Api\Service)->generateReportForMonth($month, $year, $mode);

        if (empty($error) === false)
        {
            return AppResponse::jsonResponse($error);
        }

        $file->download('xlsx');
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
}
