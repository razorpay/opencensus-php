<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Customer;
use Request;

class CustomerController extends Controller
{
    protected $customer;
    protected $token;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->customer = new Customer\Service;
        
        $this->token = new Customer\Token\Service;
    }

    public function createLocalCustomer()
    {
        $input = Request::all();

        $data = $this->customer->createLocalCustomer($input);

        return ApiResponse::json($data);
    }

    public function updateCustomer($id)
    {
        $input = Request::all();

        $data = $this->customer->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function getCustomer($id)
    {
        $data = $this->customer->fetch($id);

        return ApiResponse::json($data);
    }
    
    public function getCustomers()
    {
        $input = Request::all();
        
        $customers = $this->customer->fetchMultiple($input);

        return ApiResponse::json($customers);
    }

    public function deleteCustomer($id)
    {
        $data = $this->customer->delete($id);

        return ApiResponse::json($data);
    }

    public function addToken($id)
    {
        $input = Request::all();

        $data = $this->token->add($id, $input);

        return ApiResponse::json($data);
    }

    public function updateToken($id, $token)
    {
        $input = Request::all();

        $data = $this->token->edit($id, $token, $input);

        return ApiResponse::json($data);
    }

    public function deleteToken($id, $token)
    {
        $data = $this->token->deleteTokenForLocalCustomer($id, $token);

        return ApiResponse::json($data);
    }

    public function fetchToken($id, $token)
    {
        $data = $this->token->fetch($id, $token);

        return ApiResponse::json($data);
    }

    public function fetchTokens($id)
    {
        $data = $this->token->fetchMultiple($id);

        return ApiResponse::json($data);
    }

    public function fetchTokensForGlobalCustomer()
    {
        $tokens = $this->token->fetchTokensForGlobalCustomer();

        return ApiResponse::json($tokens);
    }

    public function fetchPaymentsForGlobalCustomer()
    {
        $input = Request::all();

        $payments = $this->customer->fetchPaymentsForGlobalCustomer($input);

        return ApiResponse::json($payments);
    }

    public function fetchGlobalCustomerStatus($contact)
    {
        $input = Request::all();

        $status = $this->customer->fetchGlobalCustomerStatus($contact, $input, true);

        return ApiResponse::json($status);
    }

    public function deleteTokenForGlobalCustomer($token)
    {
        $data = $this->token->deleteTokenForGlobalCustomer($token);

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

        $data = $this->customer->addBankAccount($id, $input);

        return ApiResponse::json($data);
    }

    public function getBankAccounts($id)
    {
        $data = $this->customer->getBankAccounts($id);

        return ApiResponse::json($data);
    }

    public function postOtp()
    {
        $input = Request::all();

        $data = $this->customer->sendOtp($input);

        return ApiResponse::json($data);
    }

    public function verifyOtp()
    {
        $input = Request::all();

        $data = $this->customer->verifyOtp($input);

        return ApiResponse::json($data);
    }

    public function validateDeviceToken($deviceToken)
    {
        $input = Request::all();

        $data = $this->customer->validateDeviceToken($deviceToken, $input);

        return ApiResponse::json($data);
    }

    public function updateSmsStatus($id)
    {
        $input = Request::all();

        $data = $this->customer->updateSmsStatus($id, $input);

        return ApiResponse::json($data);
    }

    public function postCreateAddress($customerId)
    {
        $input = Request::all();

        $address = $this->customer->createAddress($customerId, $input);

        return ApiResponse::json($address);
    }

    public function getAddresses($customerId)
    {
        $input = Request::all();

        $addresses = $this->customer->fetchAddresses($customerId, $input);

        return ApiResponse::json($addresses);
    }

    public function putPrimaryAddress($customerId, $addressId)
    {
        $address = $this->customer->setPrimaryAddress($customerId, $addressId);

        return ApiResponse::json($address);
    }

    public function deleteAddress($customerId, $addressId)
    {
        $data = $this->customer->deleteAddress($customerId, $addressId);

        return ApiResponse::json($data);
    }
}
