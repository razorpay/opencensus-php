<?php

use Http\ApiResponse;
use Models\Customer;
use Models\Customer\Account;

class CustomerController extends BaseController
{
    public function createCustomer()
    {
        $input = Input::all();

        $data = (new Customer\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function updateCustomer($id)
    {
        $input = Input::all();

        $data = (new Customer\Service)->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function getCustomer($id)
    {
        $data = (new Customer\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function deleteCustomer($id)
    {
        $data = (new Customer\Service)->delete($id);

        return ApiResponse::json($data);
    }

    public function addToken($id)
    {
        $input = Input::all();

        $data = (new Customer\Token\Service)->add($id, $input);

        return ApiResponse::json($data);
    }

    public function updateToken($uid, $mid)
    {
        $input = Input::all();

        $data = (new Customer\Token\Service)->edit($uid, $mid, $input);

        return ApiResponse::json($data);
    }

    public function deleteToken($uid, $mid)
    {
        $data = (new Customer\Token\Service)->delete($uid, $mid);

        return ApiResponse::json($data);
    }

    public function fetchToken($uid, $mid)
    {
        $data = (new Customer\Token\Service)->fetch($uid, $mid);

        return ApiResponse::json($data);
    }

    public function fetchTokens($uid)
    {
        $data = (new Customer\Token\Service)->fetchMultiple($uid);

        return ApiResponse::json($data);
    }

    public function fetchTokensByAppId($appId)
    {
        $tokens = (new Customer\Token\Service)->fetchTokensByAppId($appId);

        return ApiResponse::json($tokens);
    }

    public function fetchCustomerStatus($contact)
    {
        $status = (new Customer\Token\Service)->fetchCustomerStatus($contact);

        return ApiResponse::json($status);
    }

    public function deleteAppToken($appId, $mId)
    {
        $data = (new Customer\Token\Service)->deleteAppToken($appId, $mId);

        return ApiResponse::json($data);
    }

    public function postOtp()
    {
        $input = Input::all();

        $data = (new Customer\Service)->sendOtp($input);

        return ApiResponse::json($data);
    }

    public function verifyOtp()
    {
        $input = Input::all();

        $data = (new Customer\Service)->verifyOtp($input);

        return ApiResponse::json($data);
    }

    public function updateSmsStatus($service)
    {
        $input = Input::all();

        $data = (new Customer\Service)->updateSmsStatus($service, $input);

        return ApiResponse::json($data);
    }
}