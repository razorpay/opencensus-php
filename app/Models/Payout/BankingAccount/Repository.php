<?php

namespace RZP\Models\Payout\BankingAccount;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANKING_ACCOUNTS;

    public function getPayoutServiceBankingAccountByMerchantIdAndBalanceId(string $merchantId, string $balanceId)
    {
        $tableName = Table::BANKING_ACCOUNTS;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }


        $tableResult =  \DB::connection($this->getPayoutsServiceConnection())
            ->select("select * from $tableName where merchant_id = '$merchantId' and balance_id = '$balanceId'");

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_AND_BALANCE_ID,
            $tableResult
        );

        return $tableResult;
    }

    public function getPayoutServiceBankingAccountByMerchantId(string $merchantId)
    {
        $tableName = Table::BANKING_ACCOUNTS;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }


        $tableResult =  \DB::connection($this->getPayoutsServiceConnection())
            ->select("select * from $tableName where merchant_id = '$merchantId'");

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_ID,
            $tableResult
        );

        return $tableResult;
    }

    public function insertBankingAccountEntityIntoPayoutServiceDB($data)
    {
        $tableName = Table::BANKING_ACCOUNTS;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_CREATE,
            $data
        );

        $this->newQueryWithConnection($this->getPayoutsServiceConnection())
            ->from($tableName)
            ->insert($data);
    }

    public function updateBankingAccountIntoPayoutServiceDB(string $merchantId, string $balanceId, $data)
    {
        $tableName = Table::BANKING_ACCOUNTS;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_UPDATE,
            ['data' => $data, 'merchant_id' => $merchantId, 'balance_id' => $balanceId]
        );


        $this->newQueryWithConnection($this->getPayoutsServiceConnection())
            ->from($tableName)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::BALANCE_ID, $balanceId)
            ->update($data);
    }

}
