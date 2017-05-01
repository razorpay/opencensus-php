<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Customer;
use RZP\Models\BankAccount;
use RZP\Models\Upi\Vpa;
use Request;

class CustomerController extends Controller
{
    protected $customer;
    protected $token;

    public function createLocalCustomer()
    {
        $input = Request::all();

        $data = $this->service('customer')->createLocalCustomer($input);

        return ApiResponse::json($data);
    }

    public function updateCustomer($id)
    {
        $input = Request::all();

        $data = $this->service('customer')->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function getCustomer($id)
    {
        $data = $this->service('customer')->fetch($id);

        return ApiResponse::json($data);
    }

    public function fetchUpiCustomer()
    {
        $data = $this->service('customer')->fetchByDeviceAuth();

        return ApiResponse::json($data);
    }

    public function getDeviceCustomer()
    {
        $data = $this->service('customer')->getDeviceCustomer();

        return ApiResponse::json($data);
    }

    public function getCustomers()
    {
        $input = Request::all();

        $customers = $this->service('customer')->fetchMultiple($input);

        return ApiResponse::json($customers);
    }

    public function deleteCustomer($id)
    {
        $data = $this->service('customer')->delete($id);

        return ApiResponse::json($data);
    }

    public function addToken($id)
    {
        $input = Request::all();

        $data = $this->service('token')->add($id, $input);

        return ApiResponse::json($data);
    }

    public function updateToken($id, $token)
    {
        $input = Request::all();

        $data = $this->service('token')->edit($id, $token, $input);

        return ApiResponse::json($data);
    }

    public function deleteToken($id, $token)
    {
        $data = $this->service('token')->deleteTokenForLocalCustomer($id, $token);

        return ApiResponse::json($data);
    }

    public function fetchBalance($accountId)
    {
        $data = $this->service('customer')->fetchBalance($accountId);

        return ApiResponse::json($data);
    }

    public function fetchBankAccount($accountId)
    {
        $data = $this->service('customer')->fetchBankAccount($accountId);

        return ApiResponse::json($data);
    }

    public function fetchToken($id, $token)
    {
        $data = $this->service('token')->fetch($id, $token);

        return ApiResponse::json($data);
    }

    public function fetchTokens($id)
    {
        $data = $this->service('token')->fetchMultiple($id);

        return ApiResponse::json($data);
    }

    public function fetchTokensForGlobalCustomer()
    {
        $tokens = $this->service('token')->fetchTokensForGlobalCustomer();

        return ApiResponse::json($tokens);
    }

    public function fetchPaymentsForGlobalCustomer()
    {
        $input = Request::all();

        $payments = $this->service('customer')->fetchPaymentsForGlobalCustomer($input);

        return ApiResponse::json($payments);
    }

    public function fetchGlobalCustomerStatus($contact)
    {
        $input = Request::all();

        $status = $this->service('customer')->fetchGlobalCustomerStatus($contact, $input, true);

        return ApiResponse::json($status);
    }

    public function deleteTokenForGlobalCustomer($token)
    {
        $data = $this->service('token')->deleteTokenForGlobalCustomer($token);

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

        $data = $this->service('customer')->addBankAccount($id, $input);

        return ApiResponse::json($data);
    }

    public function createVpa()
    {
        $input = Request::all();

        $data = (new Vpa\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function getBankAccounts($id)
    {
        $data = $this->service('customer')->getBankAccounts($id);

        return ApiResponse::json($data);
    }

    public function fetchUpiBankAccounts($ifsc = 'RAZR')
    {
        return $this->service('customer')->fetchUpiBankAccounts($ifsc);
    }

    public function setMpin($bankAccountId)
    {
        $input = Request::all();

        return $this->service('customer')->setMpin($bankAccountId, $input);
    }

    public function resetMpin($bankAccountId)
    {
        $input = Request::all();

        $data = $this->service('customer')->resetMpin($bankAccountId, $input);

        return $data;
    }

    public function postOtp()
    {
        $input = Request::all();

        $data = $this->service('customer')->sendOtp($input);

        return ApiResponse::json($data);
    }

    public function verifyOtp()
    {
        $input = Request::all();

        $data = $this->service('customer')->verifyOtp($input);

        return ApiResponse::json($data);
    }

    /**
     * Used by the Open Wallet demo app
     *
     * @return mixed
     */
    public function verifyOtpApp()
    {
        $input = Request::all();

        $data = $this->service('customer')->verifyOtpApp($input);

        return ApiResponse::json($data);
    }

    public function validateDeviceToken($deviceToken)
    {
        $input = Request::all();

        $data = $this->service('customer')->validateDeviceToken($deviceToken, $input);

        return ApiResponse::json($data);
    }

    public function updateSmsStatus($id)
    {
        $input = Request::all();

        $data = $this->service('customer')->updateSmsStatus($id, $input);

        return ApiResponse::json($data);
    }

    public function postCreateAddress($customerId)
    {
        $input = Request::all();

        $address = $this->service('customer')->createAddress($customerId, $input);

        return ApiResponse::json($address);
    }

    public function getAddresses($customerId)
    {
        $input = Request::all();

        $addresses = $this->service('customer')->fetchAddresses($customerId, $input);

        return ApiResponse::json($addresses);
    }

    public function putPrimaryAddress($customerId, $addressId)
    {
        $address = $this->service('customer')->setPrimaryAddress($customerId, $addressId);

        return ApiResponse::json($address);
    }

    public function deleteAddress($customerId, $addressId)
    {
        $data = $this->service('customer')->deleteAddress($customerId, $addressId);

        return ApiResponse::json($data);
    }

    public function getCustomerWalletBalance(string $customerId)
    {
        $customerBalance = $this->service('customer')->getCustomerBalance($customerId);

        return ApiResponse::json($customerBalance);
    }

    public function getCustomerWalletStatement(string $customerId)
    {
        $input = Request::all();

        $statement = $this->service('customer')->getCustomerBalanceStatement($customerId, $input);

        return ApiResponse::json($statement);
    }
}
