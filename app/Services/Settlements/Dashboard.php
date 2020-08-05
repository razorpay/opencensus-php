<?php

namespace RZP\Services\Settlements;

use RZP\Exception;
use RZP\Error\ErrorCode;

class Dashboard extends Base
{
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
    CONST BANK_ACCOUNT_UPDATE       = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Update';
    const BANK_ACCOUNT_DELETE       = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Delete';

    const EXECUTION_TRIGGER         = '/twirp/rzp.settlements.execution.v1.ExecutionService/Trigger';

    const BODY                  = 'body';
    const CODE                  = 'code';

    const SERVICE               = 'dashboard';

    public function __construct($app)
    {
        parent::__construct($app);

        $this->setAdminHeader();
    }

    /**
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function fetch(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::FETCH_URI, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];

    }

    /**
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function fetchMultiple(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::FETCH_MULTIPLE_URI, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];

    }

    /**
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function scheduleCreate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::SCHEDULE_CREATE_URI, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];

    }

    /**
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function scheduleGet(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::SCHEDULE_GET_URI, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];

    }

    /**
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function scheduleGetIds(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::SCHEDULE_GET_IDS_URI, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];

    }

    /**
     * Merchant Config Service Get
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function merchantConfigGet(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::MERCHANT_CONFIG_GET, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];
    }

    /**
     * Merchant Config Service Create
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function merchantConfigCreate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::MERCHANT_CONFIG_CREATE, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];
    }

    /**
     * Merchant Config Service Update
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function merchantConfigUpdate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::MERCHANT_CONFIG_UPDATE, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];
    }

    /**
     * Bank Account Service Create
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function bankAccountCreate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::BANK_ACCOUNT_CREATE, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];
    }

    /**
     * Bank Account Service Update
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function bankAccountUpdate(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::BANK_ACCOUNT_UPDATE, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];
    }

    /**
     * Bank Account Service Get
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function bankAccountGet(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::BANK_ACCOUNT_GET, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];
    }

    /**
     * Bank Account Service Delete
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function bankAccountDelete(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::BANK_ACCOUNT_DELETE, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];
    }

    /**
     * Trigger execution upon receiving reminder
     * @param array  $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function executionReminder(array $input) : array
    {
        $auth = $this->getAuth(self::SERVICE);

        $response = $this->makeRequest(self::EXECUTION_TRIGGER, $input, $auth);

        $this->handleResponseCodes($response);

        return $response[self::BODY];
    }

    /**
     * @param array  $response
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    protected function handleResponseCodes(array $response)
    {
        $code = $response[self::CODE];
        $body = $response[self::BODY];

        if (in_array($code, [200, 400, 401, 500], true) === false)
        {
            throw new Exception\RuntimeException(
                'Unexpected response code received from Settlements.',
                [
                    'status_code'   => $code,
                    'response_body' => $body,
                ]);
        }

        if ($code !== 200)
        {
            throw new Exception\TwirpException($body);
        }
    }
}
