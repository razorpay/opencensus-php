<?php

namespace RZP\Services\Settlements;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount\Type;
use RZP\Models\Currency\Currency;
use RZP\Exception\RuntimeException;

class Dashboard extends Base
{
    //********************* All endpoints for dashboard are configured here ***************************//

    const FETCH_URI             = '/twirp/rzp.settlements.dashboard.v1.DashboardService/Fetch';
    const FETCH_MULTIPLE_URI    = '/twirp/rzp.settlements.dashboard.v1.DashboardService/FetchMultiple';
    const SCHEDULE_CREATE_URI   = '/twirp/rzp.settlements.schedule.v1.ScheduleService/Create';
    const SCHEDULE_GET_URI      = '/twirp/rzp.settlements.schedule.v1.ScheduleService/Get';
    const SCHEDULE_GET_IDS_URI  = '/twirp/rzp.settlements.schedule.v1.ScheduleService/GetAllIds';

    const MERCHANT_CONFIG_GET       = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Get';
    const MERCHANT_CONFIG_CREATE    = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Create';
    const MERCHANT_CONFIG_UPDATE    = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Update';

    const BANK_ACCOUNT_GET          = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Get';
    const BANK_ACCOUNT_CREATE       = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Create';
    const BANK_ACCOUNT_UPDATE       = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Update';
    const BANK_ACCOUNT_DELETE       = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Delete';

    public function __construct($app)
    {
        parent::__construct($app);

        $this->setAdminHeader();
    }

    /**
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function fetch(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::FETCH_URI, $input, $auth);
    }

    /**
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function fetchMultiple(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::FETCH_MULTIPLE_URI, $input, $auth);
    }

    /**
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function scheduleCreate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::SCHEDULE_CREATE_URI, $input, $auth);
    }

    /**
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function scheduleGet(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::SCHEDULE_GET_URI, $input, $auth);
    }

    /**
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function scheduleGetIds(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::SCHEDULE_GET_IDS_URI, $input, $auth);
    }

    /**
     * Merchant Config Service Get
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigGet(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::MERCHANT_CONFIG_GET, $input, $auth);
    }

    /**
     * Merchant Config Service Create
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigCreate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::MERCHANT_CONFIG_CREATE, $input, $auth);
    }

    /**
     * Merchant Config Service Update
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigUpdate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::MERCHANT_CONFIG_UPDATE, $input, $auth);
    }

    /**
     * Bank Account Service Create
     * @param array  $input
     * @param $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bankAccountCreate(array $input, $mode = null) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::BANK_ACCOUNT_CREATE, $input, $auth);
    }

    /**
     * Bank Account Service Update
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bankAccountUpdate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::BANK_ACCOUNT_UPDATE, $input, $auth);
    }

    /**
     * Bank Account Service Get
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bankAccountGet(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::BANK_ACCOUNT_GET, $input, $auth);
    }

    /**
     * Bank Account Service Delete
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bankAccountDelete(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::BANK_ACCOUNT_DELETE, $input, $auth);
    }

    /**
     * @param $input
     * @param $mode
     * @return array|null
     * @throws RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function createBankAccount($input, $mode)
    {
        if ($input->getType() !== Type::MERCHANT)
        {
            return null;
        }

        $req = $this->getBankAccountCreateRequestForSettlementService($input);

        return $this->bankAccountCreate($req, $mode);
    }

    /**
     * This is used to update the bank account
     * @param $newBankAccount
     * @return mixed
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function changeBankAccount($newBankAccount)
    {
        if ($newBankAccount->getType() !== Type::MERCHANT)
        {
            return $newBankAccount;
        }

        $input = ['merchant_id' => $newBankAccount->getMerchantId()];

        $old = $this->bankAccountGet($input);

        $request = $this->getBankAccountCreateRequestForSettlementService($newBankAccount);

        $request = array_merge(['id' => $old['bankAccounts'][0]['id']], $request);

        return $this->bankAccountUpdate($request);
    }


    /**
     * this method returns the bank account request
     * @param $ba
     * @return array
     */
    public function getBankAccountCreateRequestForSettlementService($ba)
    {
        return [
            'merchant_id'         =>  $ba->getMerchantId(),
            'account_number'      =>  $ba->getAccountNumber(),
            'account_type'        =>  $ba->getAccountType() !== null ? $ba->getAccountType():'current',
            'ifsc_code'           =>  $ba->getIfscCode(),
            'beneficiary_name'    =>  $ba->getBeneficiaryName(),
            'beneficiary_address' =>  $ba->getBeneficiaryAddress1(),
            'beneficiary_city'    =>  $ba->getBeneficiaryCity(),
            'beneficiary_state'   =>  $ba->getBeneficiaryState(),
            'beneficiary_country' =>  $ba->getBeneficiaryCountry(),
            'beneficiary_email'   =>  $ba->getBeneficiaryEmail(),
            'beneficiary_mobile'  =>  $ba->getBeneficiaryMobile(),
            'accepted_currency'   =>  Currency::INR
        ];
    }

    /**
     * @param array $txnIds
     * @param string $reason
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function Hold(array $txnIds, string $reason) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::Hold, [
            "ids"    => $txnIds,
            "reason" => $reason,
        ], $auth);
    }

    /**
     * @param array $txnIds
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function Release(array $txnIds) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::Release, [
            "ids" => $txnIds,
        ], $auth);
    }
}
