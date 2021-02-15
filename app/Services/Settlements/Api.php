<?php

namespace RZP\Services\Settlements;

use RZP\Exception;
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
    public function migrateBankAccount($input, $via, $mode)
    {
        if ($input->getType() !== Type::MERCHANT)
        {
            return null;
        }

        $req = $this->getBankAccountCreateRequestForSettlementService($input, $via);

        return $this->makeRequest(self::BANK_ACCOUNT_CREATE, $req, self::SERVICE_API, $mode);
    }
}
