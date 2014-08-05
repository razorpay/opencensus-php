<?php

use Models\Service;

class TransactionController extends BaseController
{
    public function postIndex()
    {
        $input = Input::all();

        $status = (new Service\Transaction)->process($input);

        return ['status' => $status];
    }

    public function getAnalytics()
    {
        $input = Input::all();

        $input['merchant_id'] = Auth::merchant()->id();

        $data = (new Service\Transaction)->getAnalytics($input);

        return array(
            'data' => $data, 
            'mode' => 'test'
        );
    }

    public function getAggregations()
    {
        $merchant_id = Auth::merchant()->id();

        $data = (new Service\Transaction)->getAggregations($merchant_id);

        return array(
            'data' => $data,
            'mode' => 'test'
        );
    }

    public function getTransactions()
    {
        $input = Input::all();

        $data = (new Service\Transaction)->fetchListFromApi($input);

        return $data;
    }

    public function getTransaction($id = NULL)
    {
        $data = (new Service\Transaction)->fetchTxnFromApi($id);

        return $data;
    }
}
