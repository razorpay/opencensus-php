<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Models\Customer;
use Request;

class CustomerController extends Controller
{
    public function createLocalCustomer()
    {
        $input = Request::all();

        $data = (new Customer\Service)->createLocalCustomer($input);

        return ApiResponse::json($data);
    }

    public function updateCustomer($id)
    {
        $input = Request::all();

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
        $input = Request::all();

        $data = (new Customer\Token\Service)->add($id, $input);

        return ApiResponse::json($data);
    }

    public function updateToken($id, $token)
    {
        $input = Request::all();

        $data = (new Customer\Token\Service)->edit($id, $token, $input);

        return ApiResponse::json($data);
    }

    public function deleteToken($id, $token)
    {
        $data = (new Customer\Token\Service)->deleteTokenForLocalCustomer($id, $token);

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

    public function fetchTokensForGlobalCustomer()
    {
        $tokens = (new Customer\Token\Service)->fetchTokensForGlobalCustomer();

        return ApiResponse::json($tokens);
    }

    public function fetchPaymentsForGlobalCustomer()
    {
        $input = Request::all();

        $payments = (new Customer\Service)->fetchPaymentsForGlobalCustomer($input);

        return ApiResponse::json($payments);
    }

    public function fetchGlobalCustomerStatus($contact)
    {
        $input = Request::all();

        $status = (new Customer\Service)->fetchGlobalCustomerStatus($contact, $input, true);

        return ApiResponse::json($status);
    }

    public function deleteTokenForGlobalCustomer($token)
    {
        $data = (new Customer\Token\Service)->deleteTokenForGlobalCustomer($token);

        return ApiResponse::json($data);
    }

    public function logoutCustomer()
    {
        $input = Request::all();

        $data = (new Customer\AppToken\Service)->deleteAppTokensForGlobalCustomer($input);

        return ApiResponse::json($data);
    }

    public function postBankAccount($id)
    {
        $input = Request::all();

        $data = (new Customer\Service)->addBankAccount($id, $input);

        return ApiResponse::json($data);
    }

    public function getBankAccounts($id)
    {
        $data = (new Customer\Service)->getBankAccounts($id);

        return ApiResponse::json($data);
    }

    public function postOtp()
    {
        $input = Request::all();

        $data = (new Customer\Service)->sendOtp($input);

        return ApiResponse::json($data);
    }

    public function verifyOtp()
    {
        $input = Request::all();

        $data = (new Customer\Service)->verifyOtp($input);

        return ApiResponse::json($data);
    }

    public function validateDeviceToken($deviceToken)
    {
        $input = Request::all();

        $data = (new Customer\Service)->validateDeviceToken($deviceToken, $input);

        return ApiResponse::json($data);
    }

    public function updateSmsStatus($id)
    {
        $input = Request::all();

        $data = (new Customer\Service)->updateSmsStatus($id, $input);

        return ApiResponse::json($data);
    }

    public function createAddress($customerId)
    {
        $input = Request::all();

        // TODO: Probably move this to Address\Core directly instead of from Customer\Service?
        $data = (new Customer\Service)->createAddress($customerId, $input);

        return ApiResponse::json($data);
    }
    
    public function getAddresses($customerId)
    {
        $input = Request::all();
        
        $data = (new Customer\Service)->fetchAddresses($customerId, $input);
        
        return ApiResponse::json($data);
    }
    
    public function deleteAddress($customerId, $addressId)
    {
        // TODO: Probably move this to Address\Core directly? Or Maybe Address\Service?
        $data = (new Customer\Service)->deleteAddress($customerId, $addressId);
        
        return ApiResponse::json($data);
    }
}