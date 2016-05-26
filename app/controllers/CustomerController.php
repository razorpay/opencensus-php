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

    public function updateToken($id, $token)
    {
        $input = Input::all();

        $data = (new Customer\Token\Service)->edit($id, $token, $input);

        return ApiResponse::json($data);
    }

    public function deleteToken($id, $token)
    {
        $data = (new Customer\Token\Service)->delete($id, $token);

        return ApiResponse::json($data);
    }

    public function fetchToken($id, $token)
    {
        $data = (new Customer\Token\Service)->fetch($id, $token);

        return ApiResponse::json($data);
    }

    public function fetchTokens($id)
    {
        $data = (new Customer\Token\Service)->fetchMultiple($id);

        return ApiResponse::json($data);
    }

    public function fetchTokensByAppId($appId)
    {
        $tokens = (new Customer\Token\Service)->fetchTokensByAppId($appId);

        return ApiResponse::json($tokens);
    }

    public function fetchCustomerStatus($contact)
    {
        $status = (new Customer\Service)->fetchCustomerStatus($contact, true);

        return ApiResponse::json($status);
    }

    public function deleteAppToken($appId, $token)
    {
        $data = (new Customer\Token\Service)->deleteAppToken($appId, $token);

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

    public function validateDeviceToken($deviceToken)
    {
        $input = Input::all();

        $data = (new Customer\Service)->validateDeviceToken($deviceToken, $input);

        return ApiResponse::json($data);
    }

    public function updateSmsStatus($id)
    {
        $input = Input::all();

        $data = (new Customer\Service)->updateSmsStatus($id, $input);

        return ApiResponse::json($data);
    }
}