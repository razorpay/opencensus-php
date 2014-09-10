<?php

use Models\Service;

use Http\AppResponse;

class RefundController extends BaseController
{
    public function postIndex($mode)
    {
        //@todo
        // $this->checkMode($mode);

        // $input = Input::all();

        // $status = (new Service\Transaction)->process($input, $mode);

        // return ['status' => $status];
    }

    public function getAnalytics($mode)
    {   
        //@todo
        // $this->checkMode($mode);

        // $input = Input::all();

        // $input['merchant_id'] = Auth::merchant()->id();

        // $data = (new Service\Transaction)->getAnalytics($input, $mode);

        // return AppResponse::jsonResponse([], $data);
    }

    public function getAggregations($mode)
    {
        //@tofo
        // $this->checkMode($mode);

        // $merchant_id = Auth::merchant()->id();

        // $data = (new Service\Transaction)->getAggregations($merchant_id, $mode);

        // return AppResponse::jsonResponse([], $data);
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
        
        list($error, $data) = (new Service\Refund)->fetchRefundFromApi($id, $mode);

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
