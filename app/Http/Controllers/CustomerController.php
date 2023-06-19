<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Http\JsonResponse;
use Request;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Models\Customer\Truecaller\AuthRequest\Service as TruecallerService;
use RZP\Models\Customer\Service;
use RZP\Trace\TraceCode;
use RZP\Exception\BaseException;
use RZP\Exception\RuntimeException;


class CustomerController extends Controller
{
    public function createLocalCustomer()
    {
        $input = Request::all();

        $data = $this->service()->createLocalCustomer($input);

        return ApiResponse::json($data);
    }

    public function getOrCreateLocalCustomerInternal()
    {
        $input = Request::all();

        $data = $this->service()->getOrCreateLocalCustomerInternal($input);

        return ApiResponse::json($data);
    }

    public function updateCustomer($id)
    {
        $input = Request::all();

        $data = $this->service()->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function updateGlobalCustomer()
    {
        $input = Request::all();

        $data = $this->service()->editGlobalCustomer($input);

        return ApiResponse::json($data);
    }

    public function getCustomer($id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function getCustomerByCustomerAndMerchantId($customerId, $merchantId)
    {
        $data = $this->service()->fetchByCustomerAndMerchantId($customerId, $merchantId);

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
        $this->trace->info(TraceCode::CUSTOMER_DELETE, ["customer_id" => $id]);

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

    public function cancelToken($id, $token)
    {
        $data = $this->service(E::TOKEN)->cancel($id, $token);

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

    public function fetchTokenCard($id)
    {
        $data = $this->service(E::TOKEN)->fetchCard($id);

        return ApiResponse::json($data);
    }

    public function fetchTokenVpa($id)
    {
        $data = $this->service(E::TOKEN)->fetchVpa($id);

        return ApiResponse::json($data);
    }

    public function fetchSubscriptionEmandateDetails($id)
    {
        $data = $this->service(E::TOKEN)->fetchSubscriptionEmandateDetails($id);

        return ApiResponse::json($data);
    }

    public function fetchSubscriptionCardMandateDetails($id)
    {
        $data = $this->service(E::TOKEN)->fetchSubscriptionCardMandateDetails($id);

        return ApiResponse::json($data);
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

        $sendOTP = true;

        if(isset($input['skip_otp']) === true)
        {
            $sendOTP = false;
        }

        $status = $this->service()->fetchGlobalCustomerStatus($contact, $input, $sendOTP);

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

    public function getBankAccounts($id)
    {
        $data = $this->service()->getBankAccounts($id);

        return ApiResponse::json($data);
    }

    public function deleteBankAccounts($id, $baId)
    {
        $data = $this->service()->deleteBankAccount($id, $baId);

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

    public function verifyOtp1cc()
    {
        $input = Request::all();

        try
        {
            $data = $this->service()->verifyOtp1cc($input);

            return ApiResponse::json($data);
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::SERVER_ERROR_RAVEN_FAILURE:
                    case ErrorCode::SERVER_ERROR_RUNTIME_ERROR: // Thrown at Services/Raven.php::sendRequest
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);

                    case ErrorCode::BAD_REQUEST_INCORRECT_OTP:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 422);
                }
            }
            throw $ex;
        }
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

    public function verifyOtpSupportPage()
    {
        $input = Request::all();

        $data = $this->service()->verifyOtpSupportPage($input);

        return ApiResponse::json($data);
    }

    public function validateDeviceToken($deviceToken)
    {
        $input = Request::all();

        $data = $this->service()->validateDeviceToken($deviceToken, $input);

        return ApiResponse::json($data);
    }

    public function updateSmsStatus($gateway)
    {
        $input = Request::all();

        $data = $this->service()->updateSmsStatus($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postCreateAddress($customerId)
    {
        $input = Request::all();

        $address = $this->service()->createAddress($customerId, $input);

        return ApiResponse::json($address);
    }

    public function createGlobalAddress()
    {
        $input = Request::all();

        $address = $this->service()->createGlobalAddress($input);

        return ApiResponse::json($address);
    }

    public function editGlobalAddress()
    {
        $input = Request::all();

        $address = $this->service()->editGlobalAddress($input);

        return ApiResponse::json($address);
    }

    public function recordAddressConsent1ccAudits()
    {
        $input = Request::all();

        return $this->service()->recordAddressConsent1ccAudits($input);
    }

    public function recordAddressConsent1cc()
    {
        $input = Request::all();

        $address = $this->service()->recordAddressConsent1cc($input);

        return ApiResponse::json($address);
    }

    public function getAddresses(string $customerId)
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

    /**
     * @param string $customerId
     *
     * @return mixed
     */
    public function postCustomerWalletPayout(string $customerId)
    {
        $input = Request::all();

        $response = $this->service()->processCustomerWalletPayout($customerId, $input);

        return ApiResponse::json($response);
    }

    public function postTokensUpiVpaBulk()
    {
        $input = Request::all();

        $response = $this->service(E::TOKEN)->createTokensUpiVpaBulk($input);

        return ApiResponse::json($response);
    }

    public function fetchTokensForGlobalCustomerV2()
    {
        $input = Request::all();

        $tokens = $this->service()->fetchTokensForGlobalCustomerV2($input);

        return ApiResponse::json($tokens);
    }

    public function deleteTokenForGlobalCustomerV2()
    {
        $input = Request::all();

        $tokens = $this->service()->deleteTokenForGlobalCustomerV2($input);

        return ApiResponse::json($tokens);
    }

    public function getOrCreateGlobalCustomer1cc()
    {
        $input = Request::all();

        $response = $this->service()->getOrCreateGlobalCustomer1cc($input);

        return ApiResponse::json($response);
    }

    public function fetchGlobalCustomerByID($id)
    {
        $response = $this->service()->fetchGlobalCustomerByID($id);

        return ApiResponse::json($response);
    }

    /**
     * @return JsonResponse
     *
     * @see Service::getCustomerDetailsForCheckout()
     */
    public function getCustomerDetailsForCheckout(): JsonResponse
    {
        $input = Request::all();

        $response = $this->service()->getCustomerDetailsForCheckout($input);

        return ApiResponse::json($response);
    }

    public function recordCustomerConsent1cc()
    {
        $input = Request::all();

        $response = $this->service()->recordCustomerConsent1cc($input);

        return ApiResponse::json($response);
    }

    public function handleTruecallerCallback()
    {
        $input = Request::all();

        $this->service(E::TRUECALLER_AUTH_REQUEST)->handleTruecallerCallback($input);

        return ApiResponse::json([]);
    }

    public function verifyTruecallerAuthRequest()
    {
        $input = Request::all();

        $data = $this->service()->verifyTrueCallerAuthRequest($input);

        return ApiResponse::json($data);
    }

    public function verifyOneCCTruecallerAuthRequest()
    {
        $input = Request::all();

        $data = $this->service()->verifyOneCCTrueCallerAuthRequest($input);

        return ApiResponse::json($data);
    }

    public function createTruecallerAuthRequestInternal()
    {
        $data = (new TruecallerService())->createTruecallerAuthRequestInternal();

        return ApiResponse::json($data);
    }
}
