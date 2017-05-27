<?php

namespace App\Http\Controllers;

use Auth;
use Input;
use App\Api;
use Response;
use App\Merchant;
use Carbon\Carbon;
use App\Transaction;
use App\MerchantDetails;
use App\Http\AppResponse;

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

        $input['merchant_id'] = Auth::user()->currentMerchant()->id;

        $data = (new Transaction\Service)->getAnalytics($input, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getAggregations($mode)
    {
        $this->checkMode($mode);

        $merchant_id = Auth::user()->currentMerchant()->id;

        $data = (new Transaction\Service)->getAggregations($merchant_id, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getPaymentAggregations($mode)
    {
        $this->checkMode($mode);

        $merchant_id = Auth::user()->currentMerchant()->id;

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

    public function postAddfunds($mode)
    {
        $this->checkMode($mode);

        $id = Input::get('razorpay_payment_id');

        $input = Input::except('razorpay_payment_id');

        $error = (new Api\Service)->capturePayment($id, $mode, $input);

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

    public function getResourceReport($mode, $resource)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $response) = (new Api\Service)->generateResourceReport($mode, $resource, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function getTransactionBrokingReport($mode, $resource = 'broking')
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $file) = (new Api\Service)->generateTransactionBrokingReport($mode, $resource, $input);

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
            $merchantId = $data['merchant']['id'];

            $merchantDetails = (new MerchantDetails\Service)->fetchDetails($merchantId);

            $data['merchant_details'] = $merchantDetails;

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

        if (isset($input['date']) === false)
        {
            $timestamp = Carbon::yesterday()->timestamp;
        }
        else
        {
            $timestamp = Carbon::parse($input['date'])->timestamp;
        }

        $created_at = (new Transaction\Service)->getCreatedAtFromInputAndType($timestamp, $type);

        $merchantId = isset($input['merchant_id']) ? $input['merchant_id'] : null;

        $data = (new Transaction\Service)->getTimelyTransactionsForTheType($created_at, $mode, $type, $merchantId);

        list($error, $data) = (new Transaction\Service)->updateTypeAggregations($data, $created_at, $mode, $type);

        return AppResponse::jsonResponse($error, $data);
    }
}
