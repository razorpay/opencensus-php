<?php

use Models\Service;

class TransactionController extends BaseController
{
    public function postIndex()
    {
        $input = Input::all();

        $error = Service\Transaction::getInstance()->process($input);

        return $error;
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

    public function getTransactions()
    {
        $input = Input::all();

        $data = Service\Transaction::getInstance()->fetchFromApi($input);

        return $data;
    }
}
