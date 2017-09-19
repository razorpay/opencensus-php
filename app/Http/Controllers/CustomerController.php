<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Constants\Entity as E;

class CustomerController extends Controller
{
    public function createLocalCustomer()
    {
        $input = Request::all();

        $data = $this->service()->createLocalCustomer($input);

        return ApiResponse::json($data);
    }

    public function updateCustomer($id)
    {
        $input = Request::all();

        $data = $this->service()->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function getCustomer($id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function fetchUpiCustomer()
    {
        $data = $this->service()->fetchByDeviceAuth();

        return ApiResponse::json($data);
    }

    public function getDeviceCustomer()
    {
        $data = $this->service()->getDeviceCustomer();

        return ApiResponse::json($data);
    }

    public function getCustomers()
    {
        $input = Request::all();

        $customers = $this->service()->fetchMultiple($input);

        return ApiResponse::json($customers);
    }

    public function deleteCustomer($id)
    {
        $data = $this->service()->delete($id);

        return ApiResponse::json($data);
    }

    public function addToken($id)
    {
        $input = Request::all();

        $data = $this->service(E::TOKEN)->add($id, $input);

        return ApiResponse::json($data);
    }

    public function updateToken($id, $token)
    {
        $input = Request::all();

        $data = $this->service(E::TOKEN)->edit($id, $token, $input);

        return ApiResponse::json($data);
    }

    public function deleteToken($id, $token)
    {
        $data = $this->service(E::TOKEN)->deleteTokenForLocalCustomer($id, $token);

        return ApiResponse::json($data);
    }

    public function fetchBalance($accountId)
    {
        $data = $this->service()->fetchBalance($accountId);

        return ApiResponse::json($data);
    }

    public function fetchBankAccount($accountId)
    {
        $data = $this->service()->fetchBankAccount($accountId);

        return ApiResponse::json($data);
    }

    public function fetchToken($id, $token)
    {
        $data = $this->service(E::TOKEN)->fetch($id, $token);

        return ApiResponse::json($data);
    }

    public function fetchTokens($id)
    {
        $data = $this->service(E::TOKEN)->fetchMultiple($id);

        return ApiResponse::json($data);
    }

    public function fetchTokensForGlobalCustomer()
    {
        $tokens = $this->service(E::TOKEN)->fetchTokensForGlobalCustomer();

        return ApiResponse::json($tokens);
    }

    public function fetchPaymentsForGlobalCustomer()
    {
        $input = Request::all();

        $payments = $this->service()->fetchPaymentsForGlobalCustomer($input);

        return ApiResponse::json($payments);
    }

    public function fetchGlobalCustomerStatus($contact)
    {
        $input = Request::all();

        $status = $this->service()->fetchGlobalCustomerStatus($contact, $input, true);

        return ApiResponse::json($status);
    }

    public function deleteTokenForGlobalCustomer($token)
    {
        $data = $this->service(E::TOKEN)->deleteTokenForGlobalCustomer($token);

        return ApiResponse::json($data);
    }

    public function logoutCustomer()
    {
        $input = Request::all();

        $data = $this->service(E::APP_TOKEN)->deleteAppTokensForGlobalCustomer($input);

        return ApiResponse::json($data);
    }

    public function postBankAccount($id)
    {
        $input = Request::all();

        $data = $this->service()->addBankAccount($id, $input);

        return ApiResponse::json($data);
    }

    public function createVpa()
    {
        $input = Request::all();

        $data = $this->service(E::VPA)->create($input);

        return ApiResponse::json($data);
    }

    public function getBankAccounts($id)
    {
        $data = $this->service()->getBankAccounts($id);

        return ApiResponse::json($data);
    }

    public function fetchUpiBankAccounts($ifsc = 'RAZR')
    {
        return $this->service()->fetchUpiBankAccounts($ifsc);
    }

    public function setMpin($bankAccountId)
    {
        $input = Request::all();

        return $this->service()->setMpin($bankAccountId, $input);
    }

    public function resetMpin($bankAccountId)
    {
        $input = Request::all();

        $data = $this->service()->resetMpin($bankAccountId, $input);

        return $data;
    }

    public function postOtp()
    {
        $input = Request::all();

        $data = $this->service()->sendOtp($input);

        return ApiResponse::json($data);
    }

    public function verifyOtp()
    {
        $input = Request::all();

        $data = $this->service()->verifyOtp($input);

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

        $data = $this->service()->verifyOtpApp($input);

        return ApiResponse::json($data);
    }

    public function validateDeviceToken($deviceToken)
    {
        $input = Request::all();

        $data = $this->service()->validateDeviceToken($deviceToken, $input);

        return ApiResponse::json($data);
    }

    public function updateSmsStatus($id)
    {
        $input = Request::all();

        $data = $this->service()->updateSmsStatus($id, $input);

        return ApiResponse::json($data);
    }

    public function postCreateAddress($customerId)
    {
        $input = Request::all();

        $address = $this->service()->createAddress($customerId, $input);

        return ApiResponse::json($address);
    }

    public function getAddresses($customerId)
    {
        $input = Request::all();

        $addresses = $this->service()->fetchAddresses($customerId, $input);

        return ApiResponse::json($addresses);
    }

    public function putPrimaryAddress($customerId, $addressId)
    {
        $address = $this->service()->setPrimaryAddress($customerId, $addressId);

        return ApiResponse::json($address);
    }

    public function deleteAddress($customerId, $addressId)
    {
        $data = $this->service()->deleteAddress($customerId, $addressId);

        return ApiResponse::json($data);
    }

    public function getCustomerWalletBalance(string $customerId)
    {
        $customerBalance = $this->service()->getCustomerBalance($customerId);

        return ApiResponse::json($customerBalance);
    }

    public function getCustomerWalletStatement(string $customerId)
    {
        $input = Request::all();

        $statement = $this->service()->getCustomerBalanceStatement($customerId, $input);

        return ApiResponse::json($statement);
    }

    public function postMigrateToGatewayTokens()
    {
        $input = Request::all();

        $summary = $this->service(E::TOKEN)->migrateToGatewayTokens($input);

        return ApiResponse::json($summary);
    }
}
