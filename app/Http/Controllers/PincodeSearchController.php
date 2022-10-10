<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;

class PincodeSearchController extends Controller
{
    public function get($id)
    {
        $url = Request::path();

        $useStateName = $useGstCodes = strpos($url, '1cc') !== false;

        $data = $this->app['pincodesearch']->fetchCityAndStateFromPincode(
            $id,
            $useStateName,
            $useGstCodes
          );

        return ApiResponse::json($data);
    }

    public function getFor1cc($id)
    {
        try
        {
            return $this->get($id);
        }
        catch(\Throwable $ex)
        {
            if (($ex instanceof BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::SERVER_ERROR:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }
    }

    public function getByCountry($country, $pincode)
    {
        try
        {
            $data = $this->app['pincodesearch']->fetchCityAndStateFromPincode($pincode, false, false, $country);
            return ApiResponse::json($data);
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::SERVER_ERROR:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }
    }
}
