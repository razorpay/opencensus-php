<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Mode;

class OfflineController extends Controller
{
    public function fetchMultiple()
    {
        $input = Request::all();

        $data = $this->service('offline_device')->fetch($input);

        return ApiResponse::json($data);
    }

    public function registerDevice()
    {
        $input = Request::all();

        $data = $this->service('offline_device')->register($input);

        return ApiResponse::json($data);
    }

    public function linkDevice()
    {
        $input = Request::all();

        $data = $this->service('offline_device')->link($input);

        return ApiResponse::json($data);
    }

    public function initiateDeviceActivationTest()
    {
        $this->ba->setModeAndDbConnection(Mode::TEST);

        return $this->initiateDeviceActivation();
    }

    public function initiateDeviceActivationLive()
    {
        $this->ba->setModeAndDbConnection(Mode::LIVE);

        return $this->initiateDeviceActivation();
    }

    protected function initiateDeviceActivation()
    {
        $input = Request::all();

        $data = $this->service('offline_device')->initiateActivation($input);

        return ApiResponse::json($data);
    }

    public function fetchVaOrderStatusTest($deviceId, $virtualAccountId)
    {
        $this->ba->setModeAndDbConnection(Mode::TEST);

        return $this->fetchVaOrderStatus($deviceId, $virtualAccountId);
    }

    public function fetchVaOrderStatusLive($deviceId, $virtualAccountId)
    {
        $this->ba->setModeAndDbConnection(Mode::LIVE);

        return $this->fetchVaOrderStatus($deviceId, $virtualAccountId);
    }

    protected function fetchVaOrderStatus($deviceId, $virtualAccountId)
    {
        $data = $this->service('offline_device')->fetchVaOrderStatus($deviceId, $virtualAccountId);

        return ApiResponse::json($data);
    }
}
