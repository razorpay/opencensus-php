<?php

use Models\Service;

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

        return array(
            'data' => $data, 
            'mode' => $mode
        );
    }

    public function getAggregations($mode)
    {
        $this->checkMode($mode);

        $merchant_id = Auth::merchant()->id();

        $data = (new Service\Transaction)->getAggregations($merchant_id, $mode);

        return array(
            'data' => $data,
            'mode' => $mode
        );
    }

    public function getTransactions($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        $data = (new Service\Transaction)->fetchListFromApi($input, $mode);

        return $data;
    }

    public function getTransaction($id = NULL, $mode)
    {
        $this->checkMode($mode);
        
        $data = (new Service\Transaction)->fetchTxnFromApi($id, $mode);

        return $data;
    }

    protected function checkMode($mode)
    {
        if($mode !== 'live' and $mode !== 'test')
        {
            throw new \Exception('Invalid Mode');
        }
    }
}
