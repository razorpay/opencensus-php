<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Mode;

class MerchantInternationalEnablementController extends Controller
{
    public function preview()
    {
        $data = $this->service()->preview();

        return ApiResponse::json($data);
    }

    public function get()
    {
        $data = $this->service()->get();

        return ApiResponse::json($data);
    }

    public function draft()
    {
        $input = Request::all();

        $data = $this->service()->draft($input);

        return ApiResponse::json($data);
    }

    public function submit()
    {
        $input = Request::all();

        $data = $this->service()->submit($input);

        return ApiResponse::json($data);
    }

    public function discard()
    {
        $this->service()->discard();

        return ApiResponse::json(['status' => 'success']);
    }

    public function getInternationalVisibilityInfo()
    {
        $data = $this->service()->getInternationalVisibilityInfo();

        return ApiResponse::json($data);
    }

    public function reminderCallBack(string $mode,string $merchantId)
    {
        $mode = ($mode === Mode::TEST) ? Mode::TEST : Mode::LIVE;

        $this->app['basicauth']->setModeAndDbConnection($mode);

        $data = $this->service()->reminderCallBack($merchantId);

        return ApiResponse::json($data['response_body'], $data['status_code']);
    }
}
