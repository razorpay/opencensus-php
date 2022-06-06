<?php

namespace RZP\Models\Customer;

use Request;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Address;
use RZP\Models\Payment;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Core as MerchantCore;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->device = $this->app['basicauth']->getDevice();

        $this->core = new Customer\Core;
    }

    /**
     * Creates Local customer entity for merchant
     * @param  array $input
     * @return array customer data
     */
    public function createLocalCustomer($input)
    {
        $failOnDuplicate = true;

        if ((isset($input[Entity::FAIL_EXISTING])) and
            ($input[Entity::FAIL_EXISTING] === '0'))
        {
            $failOnDuplicate = false;
        }

        unset($input[Entity::FAIL_EXISTING]);

        $customer = $this->core->createLocalCustomer($input, $this->merchant, $failOnDuplicate);

        return $customer->toArrayPublic();
    }

    /**
     * Creates Global customer entity for shared merchant
     * @param  array $input customer data
     * @return array customer data
     */
    public function createGlobalCustomer($input)
    {
        $customer = $this->core->createGlobalCustomer($input);

        return $customer->toArrayPublic();
    }

    /**
     * Edits a local customer
     *
     * @param  string id of the customer
     * @param  array  edit params for customer
     * @return array  updated customer entity
     */
    public function edit($id, $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $customer = $this->core->edit($customer, $input);

        return $customer->toArrayPublic();
    }

    /**
     * Fetch local customer using id
     *
     * @param  string id of the customer
     * @return array customer details
     */
    public function fetch($id)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        return $customer->toArrayPublic();
    }

    public function fetchByDeviceAuth()
    {
        return $this->device->customer->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $this->trace->info(TraceCode::CUSTOMER_FETCH,
                           [
                               'input'   => $input
                           ]);

        $customers = $this->repo->customer->fetch($input, $this->merchant->getId());

        return $customers->toArrayPublic();
    }

    /**
     * Delete a local customer
     *
     * @param  local customer id
     * @return deleted customer
     */
    public function delete($id)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $customer = $this->repo->customer->deleteOrFail($customer);

        if ($customer === null)
            return [];

        return $customer->toArrayPublic();
    }

    public function addBankAccount($id, $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $ba = (new BankAccount\Core)->addOrUpdateBankAccountForCustomer($input, $customer);

        return $ba->toArrayPublic();
    }

    public function getBankAccounts($id)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $accounts = $this->repo->bank_account->getBankAccountsForCustomer($customer);

        return $accounts->toArrayPublic();
    }

    public function getDeviceCustomer()
    {
        $customerId = $this->device->getCustomerId();

        $customer = $this->repo->customer->fetchWithVpasBankAcnts($customerId);

        return $customer->toArrayPublic();
    }

    /**
     * Send Otp to customer
     *
     * @param  details of customer for otp send
     * @return success/failure
     */
    public function sendOtp($input)
    {
        $data = $this->core->sendOtp($input, $this->merchant);

        return $data;
    }

    /**
     * @param  otp verification data
     * @return success with tokens or failure
     */
    public function verifyOtp($input)
    {
        $data = $this->core->verifyOtp($input, $this->merchant);

        return $data;
    }
    /**
     * @param  otp verification data
     * @return success with tokens or failure
     * for 1cc
     */
    public function verifyOtp1cc($input)
    {
        $data = $this->core->verifyOtp1cc($input, $this->merchant);

        return $data;
    }
    /**
     * Used by the Open Wallet demo app
     */
    public function verifyOtpApp($input)
    {
        $data = $this->core->verifyOtpApp($input, $this->merchant);

        return $data->toArrayPublic();
    }

    public function fetchBankAccountsByContact($contact)
    {
        $contact = Customer\Validator::validateAndParseContact($contact);

        // TODO: Ensure that + is always entered in the database instead
        // of this hack

        if (strlen($contact) === 13)
        {
            $contact = substr($contact, 1);
        }

        $merchant = $this->repo->merchant->getSharedAccount();

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

        return $this->repo->bank_account->getBankAccountsForCustomer($customer)->toArrayPublic();
    }

    /**
     * We do this only for Global customers. If the contact sent is not
     * of a global customer, we just return back without any tokens.
     *
     * @param      $contact
     * @param      $input
     * @param bool $sendOtp true when the flow is via checkout.
     *                      false when called from the preferences.
     *
     *                      For checkout, we decide to send the OTP
     *                      only if the customer has tokens. Else, we just return back.
     *                      For preferences, only if device token is present,
     *                      we search for tokens and return back the results.
     *
     * @return array global customer existence, send otp if true
     */
    public function fetchGlobalCustomerStatus($contact, $input, $sendOtp = false)
    {
        Customer\Validator::validateSmsHash($input);

        $data = ['saved' => false, 'saved_address' => false, '1cc_consent_banner_views' => 0];

        if ($sendOtp === true)
        {
            if (isset($input['provider']) === true)
            {
                $contact = Customer\Validator::validateAndParseContact($contact);

                $input['contact'] = $contact;

                $method = $input['method'] ?? Payment\Method::CARDLESS_EMI;

                $terminal = $this->repo
                                 ->terminal
                                 ->getByMerchantProviderAndMethod($input['provider'], $this->merchant['id'], $method);

                $gateway = Payment\Gateway::CARDLESS_EMI;

                switch ($method)
                {
                    case Payment\Method::PAYLATER:
                        $gateway = Payment\Gateway::PAYLATER;
                        break;
                }

                if($this->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::REDIRECT_TO_EARLYSALARY))
                {
                    $input['callbackUrl'] = $this->getCallbackUrl();
                }

                // merchant id is required to fetch details from cache
                $input['merchant_id']      = $this->merchant['id'];
                $input['merchant_website'] = $this->merchant['website'];
                $input['merchant_mcc']     = $this->merchant['category'];
                $input['merchant_name']    = $this->merchant['name'];
                $input['merchant_features'] = $this->merchant->getEnabledFeatures();


                $retData = $this->app['gateway']->call($gateway, 'check_account', $input, $this->mode, $terminal);

                unset($input['merchant_id']);
                unset($input['merchant_website']);
                unset($input['merchant_mcc']);
                unset($input['merchant_name']);
                unset($input['callbackUrl']);
                unset($input['merchant_features']);

                if ($retData != null)
                {
                    return $retData;
                }

                $otpInput = ['contact' => $contact];

                $customBranding = (new MerchantCore())->isOrgCustomBranding($this->merchant);

                if ($customBranding === true)
                {
                    $otpInput['org_id'] = $this->merchant->getOrgId();
                }

                if (isset($input['sms_hash']) === true)
                {
                    $otpInput = array_merge($otpInput, ['sms_hash' => $input['sms_hash']]);
                }

                if (isset($input['otp_reason']) === true)
                {
                    $otpInput = array_merge($otpInput, ['otp_reason' => $input['otp_reason']]);
                }

                $this->sendOtp($otpInput);

                return ['saved' => true];
            }

            $sessionData = $this->app['request']->session()->all();

            $this->trace->info(TraceCode::CUSTOMER_CHECKCOOKIE_STATUS,
                [
                    'session' => $sessionData,
                    'input'   => $input
                ]);

            $key = $this->mode . '_checkcookie';

            if (empty($sessionData[$key]) === true)
            {
                return $data;
            }
        }

        $merchant = $this->repo->merchant->getSharedAccount();

        $contact = Customer\Validator::validateAndParseContact($contact);

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

        if ($customer !== null)
        {
            $data['saved'] = true;

            if (isset($input['device_token']))
            {
                $deviceToken = $input['device_token'];

                $result = $this->validateDeviceToken($deviceToken, $customer);

                if (($result['valid'] === true))
                {
                    $data['email'] = $customer->getEmail();

                    if (isset($result['tokens']))
                    {
                        $data['tokens'] = $result['tokens'];
                    }

                    $sendOtp = false;
                }
            }

            if ($sendOtp === true)
            {
                $otpInput = ['contact' => $contact];

                $customBranding = (new MerchantCore())->isOrgCustomBranding($this->merchant);

                if ($customBranding === true)
                {
                    $otpInput['org_id'] = $this->merchant->getOrgId();
                }

                if (isset($input['sms_hash']) === true)
                {
                    $otpInput = array_merge($otpInput, ['sms_hash' => $input['sms_hash']]);
                }

                if (isset($input['otp_reason']) === true)
                {
                    $otpInput = array_merge($otpInput, ['otp_reason' => $input['otp_reason']]);
                }

                $this->sendOtp($otpInput);
            }
            // check for saved addresses
            $rzpAddressCount = $this->repo->address->fetchRzpAddressCountFor1cc($customer);
            if ($rzpAddressCount !== 0)
            {
                $data['saved_address'] = true;
            }
            $addressConsentView = $this->core->fetchAddressConsentViewsFor1CC($customer);
            $data['1cc_consent_banner_views'] = $addressConsentView;
        }

        return $data;
    }

    /**
     * Validates if device token is valid device token for a contact
     *
     * @param  string $deviceToken to be validated
     * @param         $customer
     *
     * @return array issues a new app_token if device_token is valid
     */
    public function validateDeviceToken($deviceToken, $customer)
    {
        $result = ['valid' => false];

        $apps = $this->repo->app_token->fetchAppsByDeviceToken(
            $customer,
            $deviceToken);

        if (($apps !== null) and ($apps->count() > 0))
        {
            $result['valid'] = true;
        }

        // If result is valid, then create a new app token.
        if ($result['valid'] === true)
        {
            $custAppInput = [
                AppToken\Entity::DEVICE_TOKEN  => $deviceToken
            ];

            $app = (new AppToken\Core)->create($custAppInput, $customer, $this->merchant);

            $this->core->putAppTokenInSession($app);

            // Fetch existing tokens if exists
            $tokens = (new Customer\Token\Core)->fetchTokensByCustomerForCheckout($customer, $this->merchant);

            if (($tokens !== null) and ($tokens->count() > 0))
            {
                $tokens = (new Customer\Token\Core)->addConsentFieldInTokens($tokens);

                $result['tokens'] = $tokens->toArrayPublic();
            }
        }

        return $result;
    }

    public function updateSmsStatus($gateway, $input)
    {
        $data = (new Customer\Raven)->updateSmsStatus($gateway, $input);

        return $data;
    }

    public function fetchBalance($accountId)
    {
        $input = Request::all();

        Entity::stripSignWithoutValidation($accountId);
        $bankAccount = $this->repo->bank_account->findOrFail($accountId);

        if ($bankAccount->getEntityId() !== $this->device->customer->getId())
        {
            return;
        }

        $data = $this->core->sendBalanceEnqRequestToGateway($this->device, $this->device->customer, $bankAccount, $input);

        // msg id
        // Cache the balance from the callback and make a cache call here?
        $balance = 10000;

        return $balance;
    }

    public function fetchBankAccount($accountId)
    {
        Entity::stripSignWithoutValidation($accountId);

        $bankAccount = $this->repo->bank_account->find($accountId);

        if ($bankAccount->getEntityId() !== $this->device->customer->getId())
        {
            return;
        }

        return $bankAccount->toArrayPublic();
    }

    public function fetchPaymentsForGlobalCustomer($input)
    {
        Customer\Validator::validateFetchCustomerPaymentsInput($input);

        $skip = 0;

        if (empty($input['skip']) === false)
        {
            $skip = $input['skip'];
        }

        $appTokenId = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        $payments = new Base\PublicCollection;

        if ($appTokenId !== null)
        {
            $appToken = (new AppToken\Core)->getAppByAppTokenId($appTokenId, $this->merchant);

            if ($appToken !== null)
            {
                $payments = $this->repo->payment->fetchPaymentsForCustomerMethod(
                    $appToken->customer,
                    Payment\Method::CARD,
                    $skip);
            }
        }

        $collection = new Base\PublicCollection;

        foreach ($payments as $payment)
        {
            $info = array(
                'merchant'  => $payment->merchant->getBillingLabel(),
                'card'      => $payment->card->getLast4(),
                'amount'    => $payment->getAmount(),
                'time'      => $payment->getCaptureTimestamp(),
                'id'        => $payment->getPublicId());

            $collection->push($info);
        }

        return $collection->toArrayWithItems();
    }

    public function createGlobalAddress(array $input)
    {
        $address = $this->core->createGlobalAddress($input);

        return $address;
    }

    public function editGlobalAddress(array $input)
    {
        return $this->core->editGlobalAddress($input);
    }

    /**
     * @throws BadRequestException
     */
    public function recordAddressConsent1cc($input): array
    {
        return $this->core->recordAddressConsent1cc($input);
    }

    public function recordAddressConsent1ccAudits(array $input): array
    {
        (new Address\Core)->recordAddressConsent1ccAudits($input);

        $merchant = $this->repo->merchant->getSharedAccount();

        $contact = Customer\Validator::validateAndParseContact($input['contact']);

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

        $addressConsentView = $this->core->fetchAddressConsentViewsFor1CC($customer);

        return [
            '1cc_consent_banner_views' => $addressConsentView
        ];
    }

    public function createAddress($customerId, array $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant(
                                            $customerId, $this->merchant);

        $address = (new Address\Core)->create($customer, Address\Type::CUSTOMER, $input);

        return $address->toArrayPublic();
    }

    public function fetchAddresses($customerId, array $input)
    {
        Entity::verifyIdAndStripSign($customerId);

        $customer = $this->repo->customer->findByIdAndMerchant($customerId, $this->merchant);

        $addresses = $this->repo->address->fetchAddressesForEntity($customer, $input);

        return $addresses->toArrayPublic();
    }

    public function setPrimaryAddress($customerId, $addressId)
    {
        $address = $this->getAddressFromCustomerId($customerId, $addressId);

        // If the address is already set as primary, there's nothing to do.
        if ($address->isPrimary() === true)
        {
            return $address;
        }

        $address = (new Address\Core)->setPrimaryAddress($address);

        return $address->toArrayPublic();
    }

    public function deleteAddress($customerId, $addressId)
    {
        $address = $this->getAddressFromCustomerId($customerId, $addressId);

        $address = (new Address\Core)->delete($address);

        if ($address === null)
        {
            return [];
        }

        return $address->toArrayPublic();
    }

    /**
     * Gets the customer from customerId, with merchant as the restriction
     * Gets the address from addressId, with customer as the restriction
     * This ensures that the merchant is retrieving his customer only
     * and is attempting to get an address of that customer only.
     *
     * @param $customerId
     * @param $addressId
     * @return Address\Entity
     */
    protected function getAddressFromCustomerId($customerId, $addressId)
    {
        Entity::verifyIdAndStripSign($customerId);

        Address\Entity::verifyIdAndStripSign($addressId);

        $customer = $this->repo->customer->findByIdAndMerchant($customerId, $this->merchant);

        return $this->repo->address->findByEntityAndId($addressId, $customer);
    }

    public function setMPINForBankAccounts($accountNumber, $creds)
    {
        $bankAccounts = $this->repo->bank_account->getBankAccountsFromAccountNumber($accountNumber);

        $success = false;
        $error = [];

        if (isset($creds['otp']))
        {
            if ($creds['otp'] === '123456')
            {
                foreach ($bankAccounts as $bankAccount)
                {
                    $last6 = substr($bankAccount->getAccountNumber(), -6);

                    if ($creds['expiry'] === '1224')
                    {
                        $bankAccount->setMpin($creds['mpin']);
                        $this->repo->saveOrFail($bankAccount);

                        $success = true;
                    }
                    else
                    {
                        $error[] = 'Invalid Expiry';
                    }
                }
            }
            else
            {
                $error[] = 'Invalid OTP';
            }
        }
        else if (isset($creds['nmpin']))
        {
            foreach ($bankAccounts as $bankAccount)
            {
                if ($bankAccount->getMpin() === $creds['mpin'])
                {
                    $bankAccount->setMpin($creds['nmpin']);
                    $this->repo->saveOrFail($bankAccount);

                    $success = true;
                }
            }
        }

        if (!$success and empty($error))
        {
            $error[] = 'Invalid MPIN';
        }

        return [$success, $error];
    }

    public function setMpin($bankAccountId, $input)
    {
        Entity::stripSignWithoutValidation($bankAccountId);

        $bankAccount = $this->repo->bank_account->find($bankAccountId);

        // Confirm ownership of bank account
        assertTrue($bankAccount->getEntityId() === $this->device->customer->getId());

        $otpResponse = $this->core->sendOtpRequestToGateway($this->device, $this->device->customer, $bankAccount, $input);

        // TODO: Conditionally send Mpin request if otp request acknowledgement received correctly
        $response = $this->core->sendSetMpinRequestToGateway($this->device, $this->device->customer, $bankAccount, $input);

        return $response;
    }

    public function resetMpin($bankAccountId, $input)
    {
        Entity::stripSignWithoutValidation($bankAccountId);

        $bankAccount = $this->repo->bank_account->find($bankAccountId);

        // Confirm ownership of bank account
        assertTrue($bankAccount->getEntityId() === $this->device->customer->getId());
        $response = $this->core->sendResetMpinRequestToGateway($this->device, $this->device->customer, $bankAccount, $input);

        return $response;
    }

    public function fetchUpiBankAccounts($ifsc = 'RAZR')
    {
        $accounts = $this->repo->bank_account->getBankAccountsForCustomer($this->device->customer, $ifsc);

        return $accounts->toArrayPublic();
    }

    /**
     * Fetch balance details for a customer wallet account
     *
     * @param  string $customerId
     * @return array
     */
    public function getCustomerBalance(string $customerId) : array
    {
        Entity::verifyIdAndStripSign($customerId);

        $customerBalance = $this->repo
                                ->customer_balance
                                ->findByCustomerIdAndMerchantSilent($customerId, $this->merchant);

        // If no customer balance entity exists, return a default empty entity
        if ($customerBalance === null)
        {
            $customerBalance = (new Customer\Balance\Entity)->build();
        }

        return $customerBalance->toArrayPublic();
    }

    public function getCustomerBalanceStatement(string $customerId, array $input = []) : array
    {
        Entity::verifyIdAndStripSign($customerId);

        $customerBalance = $this->repo
                                ->customer_balance
                                ->findByCustomerIdAndMerchantSilent($customerId, $this->merchant);

        // If no customer balance entity exists, return an empty collection
        $records = new Base\PublicCollection;

        if ($customerBalance !== null)
        {
            $records = (new Customer\Transaction\Core)->getStatement($customerBalance, $this->merchant, $input);
        }

        return $records->toArrayPublic();
    }

    public function processCustomerWalletPayout(string $customerId, array $input = []): array
    {
        Entity::verifyIdAndStripSign($customerId);

        /** @var Customer\Balance\Entity $customerBalance */
        $customerBalance = $this->repo->customer_balance->findByIdAndMerchant($customerId, $this->merchant);

        $customer = $customerBalance->customer;

        $payout = (new Payout\Core)->createPayoutFromCustomerWallet($input, $customer, $this->merchant);

        return $payout->toArrayPublic();
    }
}
