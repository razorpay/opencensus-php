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

    public function addMethod($id)
    {
        $input = Input::all();

        $data = (new Customer\Methods\Service)->add($id, $input);

        return ApiResponse::json($data);
    }

    public function updateMethod($uid, $mid)
    {
        $input = Input::all();

        $data = (new Customer\Methods\Service)->edit($uid, $mid, $input);

        return ApiResponse::json($data);
    }

    public function deleteMethod($uid, $mid)
    {
        $data = (new Customer\Methods\Service)->delete($uid, $mid);

        return ApiResponse::json($data);
    }

    public function fetchMethod($uid, $mid)
    {
        $data = (new Customer\Methods\Service)->fetch($uid, $mid);

        return ApiResponse::json($data);
    }

    public function fetchMethods($uid)
    {
        $data = (new Customer\Methods\Service)->fetchMultiple($uid);

        return ApiResponse::json($data);
    }

    public function fetchMethodsByAppId($appId)
    {
        $methods = (new Customer\Methods\Service)->fetchMethodsByAppId($appId);

        return ApiResponse::json($methods);
    }
}