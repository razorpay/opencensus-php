<?php

namespace App\Http\Controllers;

use Auth;
use App\Api;
use App\Merchant;
use App\Http\AppResponse;
use App\Transaction;
use Input;
use Response;
use Carbon\Carbon;

class TransactionController extends Controller
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

        $input['merchant_id'] = Auth::user()->getCurrentMerchantId();

        $data = (new Transaction\Service)->getAnalytics($input, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getAggregations($mode)
    {
        $this->checkMode($mode);

        $merchant_id = Auth::user()->getCurrentMerchantId();

        $data = (new Transaction\Service)->getAggregations($merchant_id, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getPaymentAggregations($mode)
    {
        $this->checkMode($mode);

        $merchant_id = Auth::user()->getCurrentMerchantId();

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

    public function getOrders($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Api\Service)->fetchCollection($input, $mode, 'order');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getOrderPayments($mode, $id)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchOrderPayments($id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPayment($mode, $id = null)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchEntity($id, $mode, 'payment');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPaymentCardData($mode, $id)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchCardDetails($id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getOrder($mode, $id = null)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchEntity($id, $mode, 'order');

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

    public function getGenerateReport($mode)
    {
        $input = Input::all();
        $this->checkMode($mode);

        list($error, $file) = (new Api\Service)->generateReport($mode, $input);

        if (empty($error) === false)
        {
            return AppResponse::notFoundResponse($error);
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

        $input = Input::all();

        $error = (new Api\Service)->refundPayment($id, $input, $mode);

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

    public function getSettlementDetails($mode, $id) {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->getEntity($mode, 'settlement')->getDetails($id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getResourceReport($mode, $resource)
    {
        $this->checkMode($mode);
        $input = Input::all();

        list($error, $file) = (new Api\Service)->generateResourceReport($mode, $resource, $input);

        if (empty($error) === false)
        {
            return AppResponse::notFoundResponse($error);
        }

        $file->download('xlsx');
    }

    public function getInvoiceReport($mode)
    {
        $this->checkMode($mode);
        $input = Input::all();

        list($error, $data) = (new Api\Service)->getInvoiceReportData($mode, $input);

        if ($error === null)
        {
            // return PDF::url('http://google.com');
            // PDF::setOutputMode('F');
            // return PDF::html('merchant.invoice', $data);//->download('invoice.pdf');
            return Response::view('merchant.invoice', $data);//->download('invoice.pdf');
        }
        else
        {
            return AppResponse::validationErrorResponse($error);
        }
    }
    /**
    * Expects date input in format "3 august 2016"
    */
    public function updateTypeAggregations($mode, $type)
    {
        $input = Input::all();

        $timestamp = Carbon::parse($input['date'])->timestamp;
        $created_at = (new Transaction\Service)->getCreatedAtFromInputAndType($timestamp, $type);

        $merchantId = isset($input['merchant_id']) ? $input['merchant_id'] : null;
        $data = (new Transaction\Service)->getTimelyTransactionsForTheType($created_at, $mode, $type, $merchantId);

        list($error, $data) = (new Transaction\Service)->updateTypeAggregations($data, $created_at, $mode, $type);

        return AppResponse::jsonResponse($error, $data);
    }

    public function uploadRefundFile($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $response = (new Api\Service)->uploadRefundFile($input);

        return AppResponse::jsonResponse($response);
    }

    public function fetchMultipleRefunds($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $response = (new Api\Service)->fetchCollection($input, $mode, 'batch');

        return AppResponse::jsonResponse($response);
    }

    public function fetchRefundById($mode, $id)
    {
        $this->checkMode($mode);

        $response = (new Api\Service)->fetchEntity($id, $mode, 'batch');

        return AppResponse::jsonResponse($response);
    }

    public function downloadRefundFile($mode, $id)
    {
        $this->checkMode($mode);

        $response = (new Api\Service)->downloadRefundFile($id);

        return AppResponse::jsonResponse($response);
    }

    public function retryRefundFile($mode, $id)
    {
        $this->checkMode($mode);

        $response = (new Api\Service)->retryRefundFile($id);

        return AppResponse::jsonResponse($response);
    }
}
