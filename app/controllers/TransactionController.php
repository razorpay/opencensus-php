<?php

use Models\Service;

class TransactionController extends BaseController
{
    public function postIndex()
    {
        $input = Input::all();

        $status = Service\Transaction::getInstance()->process($input);

        return $status;
    }

    public function getAnalytics()
    {
        $input = Input::all();

        $input['merchant_id'] = Auth::id();

        $data = Service\Transaction::getInstance()->getAnalytics($input);

        return array(
            'data' => $data, 
            'mode' => 'test'
        );
    }

    public function getAggregations()
    {
        $merchant_id = Auth::id();

        $data = Service\Transaction::getInstance()->getAggregations($merchant_id);

        return array(
            'data' => $data,
            'mode' => 'test'
        );
    }

    public function getTransactions()
    {
        $input = Input::all();

        $data = Service\Transaction::getInstance()->fetchListFromApi($input);

        return $data;
    }

    public function getTransaction($id = NULL)
    {
        $data = Service\Transaction::getInstance()->fetchTxnFromApi($id);

        return $data;
    }
}
