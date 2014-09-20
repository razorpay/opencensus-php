<?php

use Models\Service;

use Http\AppResponse;

class TransactionController extends BaseController
{
    public function postIndex($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $status = (new Service\Transaction)->process($input, $mode);

        return ['status' => $status];
    }

    public function getAnalytics($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $input['merchant_id'] = Auth::merchant()->id();

        $data = (new Service\Transaction)->getAnalytics($input, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getAggregations($mode)
    {
        $this->checkMode($mode);

        $merchant_id = Auth::merchant()->id();

        $data = (new Service\Transaction)->getAggregations($merchant_id, $mode);

        return AppResponse::jsonResponse([], $data);
    }

    public function getTransactions($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Service\Transaction)->fetchListFromApi($input, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getTransaction($mode, $id = NULL)
    {
        $this->checkMode($mode);
        
        list($error, $data) = (new Service\Transaction)->fetchFromApi($id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPayments($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Service\Payment)->fetchListFromApi($input, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPayment($mode, $id = NULL)
    {
        $this->checkMode($mode);
        
        list($error, $data) = (new Service\Payment)->fetchFromApi($id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPaymentRefunds($mode, $id = NULL)
    {
        $this->checkMode($mode);
        
        list($error, $data) = (new Service\Payment)->fetchRefundsFromApi($id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postCaptureTransaction($mode, $id = NULL)
    {
        $this->checkMode($mode);
        
        $amount = Input::get('amount');

        $error = (new Service\Payment)->capture($id, $amount, $mode);

        return AppResponse::jsonResponse($error);
    }

    public function postRefundTransaction($mode, $id = NULL)
    {
        $this->checkMode($mode);

        $amount = Input::get('amount');
        
        $error = (new Service\Payment)->refund($id, $amount, $mode);

        return AppResponse::jsonResponse($error);
    }

    public function getRefunds($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Service\Refund)->fetchListFromApi($input, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getRefund($mode, $id = NULL)
    {
        $this->checkMode($mode);
        
        list($error, $data) = (new Service\Refund)->fetchFromApi($id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getSettlements($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Service\Settlement)->fetchListFromApi($input, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getSettlement($mode, $id = NULL)
    {
        $this->checkMode($mode);
        
        list($error, $data) = (new Service\Settlement)->fetchFromApi($id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    protected function checkMode($mode)
    {
        if($mode !== 'live' and $mode !== 'test')
        {
            throw new \Exception('Invalid Mode');
        }
    }
}
