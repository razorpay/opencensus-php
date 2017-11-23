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

        $data = (new Transaction\Service)->getAnalytics($input, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getAggregations($mode)
    {
        $this->checkMode($mode);

        $data = (new Transaction\Service)->getAggregations($mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getPaymentAggregations($mode)
    {
        $this->checkMode($mode);

        $data = (new Transaction\Service)->getPaymentAggregations($mode);

        return AppResponse::jsonResponse([], $data);
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
        $errorMsg = 'Oops!, We are not able to generate the Invoice for this period.';

        $this->checkMode($mode);

        $input = Input::all();

        $month = intval($input['month']);

        $year = intval($input['year']);

        // GST is applicable from 1st July 2017
        $isGstApplicable = (($year >= 2017) and ($month >= 7));

        if ($isGstApplicable) {
            $input['format'] = 'new';
        }

        list($error, $data) = (new Api\Service)->getInvoiceReportData($mode, $input);

        if ($error === null && sizeOf($data) !== 0)
        {
            $merchantId = $data['merchant_id'];

            list($error, $merchant) = (new Merchant\Service)->fetchMerchantFromApi($merchantId);

            if ($error !== null) {

              return AppResponse::validationErrorResponse($errorMsg);
            }

            $data['merchant'] = $merchant;

            $merchantDetails = (new MerchantDetails\Service)->fetchDetails($merchantId);

            $gst = (empty($merchantDetails['gstin']) === false) ? $merchantDetails['gstin'] :
                    ((empty($merchantDetails['p_gstin']) === false) ? $merchantDetails['p_gstin'] : '');

            $data['gst'] = $gst;
            $data['isGstApplicable'] = $isGstApplicable;

            $data['merchant_details'] = $merchantDetails;

            // return PDF::url('http://google.com');
            // PDF::setOutputMode('F');
            // return PDF::html('merchant.invoice', $data);//->download('invoice.pdf');

            //->download('invoice.pdf');
            return Response::view($isGstApplicable ? 'merchant.invoice.invoice' : 'merchant.invoice_old', $data);
        }
        else
        {
            return AppResponse::validationErrorResponse($errorMsg);
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
