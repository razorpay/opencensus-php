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
        
        list($error, $data) = (new Service\Transaction)->fetchTxnFromApi($id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postCaptureTransaction($mode, $id = NULL)
    {
        $this->checkMode($mode);
        
        $amount = Input::get('amount');

        $error = (new Service\Transaction)->captureTxn($id, $amount, $mode);

        return AppResponse::jsonResponse($error);
    }

    public function postRefundTransaction($mode, $id = NULL)
    {
        $this->checkMode($mode);
        
        $error = (new Service\Transaction)->refundTxn($id, $mode);

        return AppResponse::jsonResponse($error);
    }

    protected function checkMode($mode)
    {
        if($mode !== 'live' and $mode !== 'test')
        {
            throw new \Exception('Invalid Mode');
        }
    }
}
