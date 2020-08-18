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

    const FETCH_URI                    = '/twirp/rzp.settlements.dashboard.v1.DashboardService/Fetch';
    const FETCH_MULTIPLE_URI           = '/twirp/rzp.settlements.dashboard.v1.DashboardService/FetchMultiple';
    const SCHEDULE_CREATE_URI          = '/twirp/rzp.settlements.schedule.v1.ScheduleService/Create';
    const SCHEDULE_GET_URI             = '/twirp/rzp.settlements.schedule.v1.ScheduleService/Get';
    const SCHEDULE_GET_IDS_URI         = '/twirp/rzp.settlements.schedule.v1.ScheduleService/GetAllIds';

    const MERCHANT_CONFIG_GET          = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Get';
    const MERCHANT_CONFIG_CREATE       = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Create';
    const MERCHANT_CONFIG_UPDATE       = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Update';
    const MERCHANT_CONFIG_EDIT_FEATURE = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/UpdateFeature';

    const BANK_ACCOUNT_GET             = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Get';
    const BANK_ACCOUNT_CREATE          = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Create';
    const BANK_ACCOUNT_UPDATE          = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Update';
    const BANK_ACCOUNT_DELETE          = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Delete';

    const EXECUTION_REGISTER           = '/twirp/rzp.settlements.execution.v1.ExecutionService/Register';
    const EXECUTION_TRIGGER_MULTIPLE   = '/twirp/rzp.settlements.execution.v1.ExecutionService/TriggerMultiple';
    const EXECUTION_RESUME             = '/twirp/rzp.settlements.execution.v1.ExecutionService/Resume';

    const CHANNEL_STATUS_UPDATE        = '/twirp/rzp.settlements.transfer.v1.TransferService/SetChannelState';
    const CHANNEL_STATUS_GET           = '/twirp/rzp.settlements.transfer.v1.TransferService/GetChannelState';

    const SETTLEMENT_RETRY             = '/twirp/rzp.settlements.settlement.v1.SettlementService/Retry';

    public function __construct($app)
    {
        parent::__construct($app);

        $this->setAdminHeader();
    }

    /**
     * Dashboard Service fetch
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
     * Dashboard Service fetchMultiple
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
     * Schedule Service Create
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
     * Schedule Service Get
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
     * Schedule Service GetIds
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
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigCreate(array $input, $mode = null) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::MERCHANT_CONFIG_CREATE, $input, $auth, $mode);
    }

    /**
     * Merchant Config Service Update
     * @param array  $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigUpdate(array $input, $mode = null) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::MERCHANT_CONFIG_UPDATE, $input, $auth, $mode);
    }

    /**
     * Bank Account Service Create
     * @param array  $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bankAccountCreate(array $input, $mode = null) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::BANK_ACCOUNT_CREATE, $input, $auth, $mode);
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
     * Used to create the bank account when the merchant is onboarded
     * @param $input
     * @param $mode
     * @return array|null
     * @throws RuntimeException
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
     * Used to register the execution
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function executionRegister(array $input) : array
    {
        if (isset($input['options']['force']))
        {
            $input['options']['force'] = ($input['options']['force'] == '1');
        }

        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::EXECUTION_REGISTER, $input, $auth);
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
     * used to trigger multiple executions at once
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function executionTriggerMultiple(array $input) : array
    {
        if (isset($input['force']) === true)
        {
            $input['force'] = ($input['force'] == '1');
        }

        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::EXECUTION_TRIGGER_MULTIPLE, $input, $auth);
    }

    /**
     * Used to resume the execution
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function executionResume(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::EXECUTION_RESUME, $input, $auth);
    }

    /**
     * Used to hold the transaction
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function transactionHold(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::TRANSACTION_HOLD, $input, $auth);
    }

    /**
     * Used to release the transaction
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function transactionRelease(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::TRANSACTION_RELEASE, $input, $auth);
    }

    /**
     * Used to Update the channel status
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function channelStatusUpdate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::CHANNEL_STATUS_UPDATE, $input, $auth);
    }

    /**
     * Used to retry the settlement
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function settlementRetry(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::SETTLEMENT_RETRY, $input, $auth);
    }

    /**
     * This is used to get the channel state in the settlements service
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function getChannelState() : array
    {
        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::CHANNEL_STATUS_GET, [], $auth);
    }

    /**
     * this method returns the bank account request
     * @param $ba
     * @return array
     */
    public function getBankAccountCreateRequestForSettlementService($ba)
    {
        return [
            'merchant_id'         => $ba->getMerchantId(),
            'account_number'      => $ba->getAccountNumber(),
            'account_type'        => $ba->getAccountType() !== null ? $ba->getAccountType() : 'current',
            'ifsc_code'           => $ba->getIfscCode(),
            'beneficiary_name'    => $ba->getBeneficiaryName(),
            'beneficiary_address' => $ba->getBeneficiaryAddress1(),
            'beneficiary_city'    => $ba->getBeneficiaryCity(),
            'beneficiary_state'   => $ba->getBeneficiaryState(),
            'beneficiary_country' => $ba->getBeneficiaryCountry(),
            'beneficiary_email'   => $ba->getBeneficiaryEmail(),
            'beneficiary_mobile'  => $ba->getBeneficiaryMobile(),
            'accepted_currency'   => Currency::INR
        ];
    }

    /**
     * This is used to update the disable feature from the admin dashboard
     * @param $merchantId
     * @param $reason
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function toggleMerchantHold($merchantId, $reason, $mode = null) : array
    {
        $input = [
            'merchant_id'  => $merchantId,
            'feature_name' => 'disable',
            'status'       => $reason == null ? false : true,
            'reason'       => $reason
        ];

        $auth = $this->getAuth(self::SERVICE_DASHBOARD);

        return $this->makeRequest(self::MERCHANT_CONFIG_EDIT_FEATURE, $input, $auth, $mode);
    }
}
