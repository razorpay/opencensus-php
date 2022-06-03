<?php

namespace RZP\Services\Settlements;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount\Type;
use RZP\Exception\RuntimeException;

class Api extends Base
{
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * This is used to call the transaction release method via the API auth
     * @param array $txnIds
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function transactionRelease(array $txnIds) : array
    {
        return $this->makeRequest(self::TRANSACTION_RELEASE, [
            "ids" => $txnIds,
        ], self::SERVICE_API);
    }

    /**
     * This is used to call the transaction Hold method via the API auth
     * @param array $txnIds
     * @param string $reason
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function transactionHold(array $txnIds, string $reason) : array
    {
        return $this->makeRequest(self::TRANSACTION_HOLD, [
            "ids"    => $txnIds,
            "reason" => $reason,
        ], self::SERVICE_API);
    }

    /******************** Following routes are added for the migration purpose *********************/

    /**
     * Merchant Config Service Get
     * @param array  $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigGet(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::MERCHANT_CONFIG_GET, $input, self::SERVICE_API, $mode);
    }

    /**
     * migrateMerchantConfigCreate used to create default merchant config while migration
     * @param array $input
     * @param null $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function migrateMerchantConfigCreate(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::MERCHANT_CONFIG_CREATE, $input, self::SERVICE_API, $mode);
    }

    /**
     * migrateMerchantConfigUpdate used to update merchant config while migration
     * @param array $input
     * @param null $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function migrateMerchantConfigUpdate(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::MERCHANT_CONFIG_UPDATE, $input, self::SERVICE_API, $mode);
    }

    /**
     *
     * @param $input
     * @param $via
     * @param $mode
     * @return array|null
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function migrateBankAccount($input, $mode, $via = 'payout')
    {
        if ($input->getType() !== Type::MERCHANT)
        {
            return null;
        }

        $req = $this->getBankAccountCreateRequestForSettlementService($input, $via);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_BANK_ACCOUNT_REQUEST,
            [
                'merchant_id' => $input->getMerchantId(),
                'mode'        => $mode,
                'request_data'=> $req
            ]);

        (new Validator)->validateInput('create_bank_account', $req);

        return $this->makeRequest(self::BANK_ACCOUNT_CREATE, $req, self::SERVICE_API, $mode);
    }

    /**
     * ledgerCronActiveMtuCheck used to check if merchant is already added as ledger cron active mtu
     * @param array $input
     * @param null $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function ledgerCronActiveMtuCheck(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::LEDGER_RECON_ACTIVE_MTU_CHECK, $input, self::SERVICE_API, $mode);
    }

    /**
     * ledgeCronActiveMtuAdd used to add merchant to ledger cron active mtu
     * @param array $input
     * @param null $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function ledgeCronActiveMtuAdd(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::LEDGER_RECON_ACTIVE_MTU_ADD, $input, self::SERVICE_API, $mode);
    }

    /**
     * ledgeCronResultAdd used to add merchant discrepancy to ledger cron results
     * @param array $input
     * @param null $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function ledgeCronResultAdd(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::LEDGER_CRON_RESULT_ADD, $input, self::SERVICE_API, $mode);
    }

    /**
     * ledgeCronExecutionAdd used to add a new cron execution
     * @param array $input
     * @param null $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function ledgeCronExecutionAdd(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::LEDGER_CRON_EXECUTION_ADD, $input, self::SERVICE_API, $mode);
    }

    /**
     * ledgeCronExecutionUpdate used to update a cron execution
     * @param array $input
     * @param null $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function ledgeCronExecutionUpdate(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::LEDGER_CRON_EXECUTION_UPDATE, $input, self::SERVICE_API, $mode);
    }

    /**
     * ledgerReconActiveMtuUpdate used to update mtu details
     * @param array $input
     * @param null $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function ledgerReconActiveMtuUpdate(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::LEDGER_RECON_ACTIVE_MTU_UPDATE, $input, self::SERVICE_API, $mode);
    }


    /**
     * triggers optimizer externals settlemnts execution
     * @param array $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function optimizerExternalSettlementsExecute(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::OPTIMIZER_EXTERNAL_SETTLEMENTS_EXECUTION, $input, self::SERVICE_API, $mode);
    }

    /**
     * @param array $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function optimizerExternalSettlementsManualExecute(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::OPTIMIZER_EXTERNAL_SETTLEMENTS_MANUAL_EXECUTION, $input, self::SERVICE_API, $mode);
    }

    /**
     * Send Entity Alerts if per entity state time limit is breached.
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function checkForEntityAlerts(array $input) : array
    {
        return $this->makeRequest(self::CHECK_FOR_ENTITY_ALERTS, $input, self::SERVICE_API);
    }
}
