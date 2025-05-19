<?php

namespace RZP\Models\Payout\BankingAccount;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant;
use RZP\Models\BankingAccount;
use RZP\Models\Feature\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\RazorxTreatment;

class Core extends Base
{
    public function getPayoutServiceBankingAccountByMerchantIdAndBalanceId(string $merchantId, string $balanceId)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_AND_BALANCE_ID_INIT,
            ['merchant_id' => $merchantId, 'balance_id' => $balanceId]
        );

        $payoutServiceBankingAccount = (new Repository)->getPayoutServiceBankingAccountByMerchantIdAndBalanceId($merchantId, $balanceId);

        if (count($payoutServiceBankingAccount) === 0)
        {
            return null;
        }

        $psBankingAccount = $payoutServiceBankingAccount[0];

        // converts the stdClass object into associative array.
        return get_object_vars($psBankingAccount);
    }

    public function getPayoutServiceBankingAccountByMerchantId(string $merchantId)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_ID_INIT,
            ['merchant_id' => $merchantId]
        );

        $payoutServiceBankingAccount = (new Repository)->getPayoutServiceBankingAccountByMerchantId($merchantId);

        if (count($payoutServiceBankingAccount) === 0)
        {
            return null;
        }

        $psBankingAccount = array();

        foreach ($payoutServiceBankingAccount as $bankingAccount)
        {
            // converts the stdClass object into associative array. and adding into the array.
            $psBankingAccount[] = get_object_vars($bankingAccount);
        }

        return $psBankingAccount;

    }

    public function createBankingAccountEntityIntoPayoutServiceDB(
        string $merchantId,
        string $balanceId,
        bool $payoutServiceEnabled)
    {
        /** @var \RZP\Models\Merchant\Balance\Entity $balance */

        $balance = $this->repo->balance->getBalanceEntityForBalanceId($balanceId);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_CREATE_INIT,
            ['merchant_id' => $merchantId, 'balance_id' => $balanceId, 'payout_service_enabled' => $payoutServiceEnabled]
        );


        try
        {
            $bankingAccount = (new BankingAccount\Service())-> fetchBankingAccountForAccountNumber($balance->getAccountNumber(), $merchantId);

        }
        catch (\Throwable $ex)
        {

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_FETCH_FROM_API_MONOLITH_FAILURE,
                ['merchant_id' => $merchantId, 'balance_id' => $balanceId, 'payout_service_enabled' => $payoutServiceEnabled]
            );

            throw $ex;
        }

        $data = ([
            Entity::ID                      => $bankingAccount[Entity::ID],
            Entity::MERCHANT_ID             => $bankingAccount[Entity::MERCHANT_ID],
            Entity::BALANCE_ID              => $bankingAccount[Entity::BALANCE_ID],
            Entity::ACCOUNT_NUMBER          => $bankingAccount[Entity::ACCOUNT_NUMBER],
            Entity::ACCOUNT_TYPE            => $bankingAccount[Entity::ACCOUNT_TYPE],
            Entity::CHANNEL                 => $bankingAccount[Entity::CHANNEL],
            Entity::FTS_FUND_ACCOUNT_ID     => $bankingAccount[Entity::FTS_FUND_ACCOUNT_ID],
            Entity::STATUS                  => $bankingAccount[Entity::STATUS],
            Entity::PAYOUT_SERVICE_ENABLED  => $payoutServiceEnabled,
            Entity::CREATED_AT              => $bankingAccount[Entity::ONBOARDED_TIME],
            Entity::UPDATED_AT              => Carbon::now(Timezone::IST)->getTimestamp(),

        ]);

        (new Repository)->insertBankingAccountEntityIntoPayoutServiceDB($data);

    }

    public function updatePayoutServiceEnabledFlagInPayoutServiceBankingAccount(string $merchantId, string $balanceId, bool $payoutServiceEnabled)
    {

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_UPDATE_INIT,
            ['merchant_id' => $merchantId, 'balance_id' => $balanceId, 'payout_service_enabled' => $payoutServiceEnabled]
        );

        $data = ([
            Entity::PAYOUT_SERVICE_ENABLED       => $payoutServiceEnabled,
            Entity::UPDATED_AT      => Carbon::now(Timezone::IST)->getTimestamp(),
        ]);

        (new Repository)->updateBankingAccountIntoPayoutServiceDB($merchantId, $balanceId, $data);
    }

    public function updatePayoutServiceEnabledFlagInPayoutServiceBankingAccountWithMerchantOnboardingTime(string $merchantId, string $balanceId, bool $payoutServiceEnabled)
    {

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_UPDATE_INIT,
            ['merchant_id' => $merchantId, 'balance_id' => $balanceId, 'payout_service_enabled' => $payoutServiceEnabled]
        );

        /** @var \RZP\Models\Merchant\Balance\Entity $balance */

        $balance = $this->repo->balance->getBalanceEntityForBalanceId($balanceId);

        try
        {
            $bankingAccount = (new BankingAccount\Service())-> fetchBankingAccountForAccountNumber($balance->getAccountNumber(), $merchantId);

        }
        catch (\Throwable $ex)
        {

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_FETCH_FROM_API_MONOLITH_FAILURE,
                ['merchant_id' => $merchantId, 'balance_id' => $balanceId, 'payout_service_enabled' => $payoutServiceEnabled]
            );

            throw $ex;
        }


        $data = ([
            Entity::CREATED_AT              => $bankingAccount[Entity::ONBOARDED_TIME],
            Entity::PAYOUT_SERVICE_ENABLED  => $payoutServiceEnabled,
            Entity::UPDATED_AT              => Carbon::now(Timezone::IST)->getTimestamp(),
        ]);

        (new Repository)->updateBankingAccountIntoPayoutServiceDB($merchantId, $balanceId, $data);
    }


    public function merchantMigratedToPayoutServiceByMerchantIdAndBalanceId(string $merchantId, string $balanceId)
    {
        $this->trace->info(
            TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_AND_BALANCE_ID_INIT,
            ['merchant_id' => $merchantId, 'balance_id' => $balanceId]
        );


        /** @var \RZP\Models\Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->find($merchantId);

        $requestPayload = [
            'id' => $merchantId,
            'experiment_name' => RazorxTreatment::PS_API_MERCHANT_MIGRATION_ON_BALANCE_ID,
            'request_data'  => json_encode(['id' =>  $merchantId])
        ];

        if ((new Merchant\Core())->isSplitzExperimentEnable($requestPayload,RazorxTreatment::VARIANT_ENABLE) !== true)
        {
            if (empty($merchant) || $merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === false)
            {

                $this->trace->info(
                    TraceCode::SPLITZ_PAYOUT_SERVICE_FEATURE_NOT_ENABLED,
                    ['merchant_id' => $merchantId]
                );

                return false;
            }

            return true;
        }


        if (empty($merchant) || $merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === false)
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_FEATURE_NOT_ENABLED,
                ['merchant_id' => $merchantId]
            );

            return false;
        }


        $payoutServiceBankingAccount = $this->getPayoutServiceBankingAccountByMerchantIdAndBalanceId($merchantId, $balanceId);

        if (empty($payoutServiceBankingAccount))
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_AND_BALANCE_ID_NOT_FOUND,
                ['merchant_id' => $merchantId, 'balance_id' => $balanceId]
            );

            return false;
        }

        $this->trace->info(
            TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_AND_BALANCE_ID,
            ['merchant_id' => $merchantId, 'balance_id' => $balanceId, 'payout_service_enabled' => $payoutServiceBankingAccount[Entity::PAYOUT_SERVICE_ENABLED]]
        );

        return $payoutServiceBankingAccount[Entity::PAYOUT_SERVICE_ENABLED] === 1;

    }

    public function merchantMigratedToPayoutServiceByMerchantIdAndBalanceIdAdminAction(string $merchantId, string $balanceId)
    {
        $this->trace->info(
            TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_AND_BALANCE_ID_INIT,
            ['merchant_id' => $merchantId, 'balance_id' => $balanceId]
        );


        /** @var \RZP\Models\Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->find($merchantId);

        if (empty($merchant) || $merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === false)
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_FEATURE_NOT_ENABLED,
                ['merchant_id' => $merchantId]
            );

            return false;
        }


        $payoutServiceBankingAccount = $this->getPayoutServiceBankingAccountByMerchantIdAndBalanceId($merchantId, $balanceId);

        if (empty($payoutServiceBankingAccount))
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_AND_BALANCE_ID_NOT_FOUND,
                ['merchant_id' => $merchantId, 'balance_id' => $balanceId]
            );

            return false;
        }

        $this->trace->info(
            TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_AND_BALANCE_ID,
            ['merchant_id' => $merchantId, 'balance_id' => $balanceId, 'payout_service_enabled' => $payoutServiceBankingAccount[Entity::PAYOUT_SERVICE_ENABLED]]
        );

        return $payoutServiceBankingAccount[Entity::PAYOUT_SERVICE_ENABLED] === 1;

    }

    public function merchantMigratedToPayoutServiceByMerchantId(string $merchantId)
    {
        $this->trace->info(
            TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_ID_INIT,
            ['merchant_id' => $merchantId]
        );

        /** @var \RZP\Models\Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->find($merchantId);

        $requestPayload = [
            'id' => $merchantId,
            'experiment_name' => RazorxTreatment::PS_API_MERCHANT_MIGRATION_ON_BALANCE_ID,
            'request_data'  => json_encode(['id' =>  $merchantId])
        ];

        if ((new Merchant\Core())->isSplitzExperimentEnable($requestPayload,RazorxTreatment::VARIANT_ENABLE) !== true)
        {
            if (empty($merchant) || $merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === false)
            {

                $this->trace->info(
                    TraceCode::SPLITZ_PAYOUT_SERVICE_FEATURE_NOT_ENABLED,
                    ['merchant_id' => $merchantId]
                );

                return false;
            }

            return true;
        }


        if (empty($merchant) || $merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === false)
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_FEATURE_NOT_ENABLED,
                ['merchant_id' => $merchantId]
            );

            return false;
        }

        $payoutServiceBankingAccount = $this->getPayoutServiceBankingAccountByMerchantId($merchantId);

        if (empty($payoutServiceBankingAccount))
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_ID_NOT_FOUND,
                ['merchant_id' => $merchantId]
            );

            return false;
        }

        foreach ($payoutServiceBankingAccount as $bankingAccount)
        {
            $this->trace->info(
                TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_ID,
                ['merchant_id' => $merchantId, 'balance_id' => $bankingAccount[Entity::BALANCE_ID],'payout_service_enabled' => $bankingAccount[Entity::PAYOUT_SERVICE_ENABLED]]
            );

            if ($bankingAccount[Entity::PAYOUT_SERVICE_ENABLED] === 1)
            {
                return true;
            }
        }

        return false;
    }

    public function currentAccountMerchantMigratedToPayoutServiceByMerchantId(string $merchantId)
    {
        $this->trace->info(
            TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_ID_INIT,
            ['merchant_id' => $merchantId]
        );


        /** @var \RZP\Models\Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->find($merchantId);

        if (empty($merchant) || $merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === false)
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_FEATURE_NOT_ENABLED,
                ['merchant_id' => $merchantId]
            );

            return false;
        }

        $payoutServiceBankingAccount = $this->getPayoutServiceBankingAccountByMerchantId($merchantId);

        if (empty($payoutServiceBankingAccount))
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_ID_NOT_FOUND,
                ['merchant_id' => $merchantId]
            );

            return false;
        }



        foreach ($payoutServiceBankingAccount as $bankingAccount)
        {
            $this->trace->info(
                TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_ID,
                [
                    'merchant_id' => $merchantId,
                    'balance_id' => $bankingAccount[Entity::BALANCE_ID],
                    'payout_service_enabled' => $bankingAccount[Entity::PAYOUT_SERVICE_ENABLED],
                    'account_type'=> $bankingAccount[Entity::ACCOUNT_TYPE]
                ]);

            if ($bankingAccount[Entity::ACCOUNT_TYPE] === Entity::ACCOUNT_TYPE_DIRECT && $bankingAccount[Entity::PAYOUT_SERVICE_ENABLED] === 1)
            {
                return true;
            }
        }

        return false;
    }

    public function virtualAccountMerchantMigratedToPayoutServiceByMerchantId(string $merchantId)
    {
        $this->trace->info(
            TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_ID_INIT,
            ['merchant_id' => $merchantId]
        );


        /** @var \RZP\Models\Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->find($merchantId);

        if (empty($merchant) || $merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === false)
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_FEATURE_NOT_ENABLED,
                ['merchant_id' => $merchantId]
            );

            return false;
        }

        $payoutServiceBankingAccount = $this->getPayoutServiceBankingAccountByMerchantId($merchantId);

        if (empty($payoutServiceBankingAccount))
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_ID_NOT_FOUND,
                ['merchant_id' => $merchantId]
            );

            return false;
        }

        foreach ($payoutServiceBankingAccount as $bankingAccount)
        {

            $this->trace->info(
                TraceCode::MERCHANT_MIGRATION_TO_PAYOUT_SERVICE_BY_MERCHANT_ID,
                [
                    'merchant_id' => $merchantId,
                    'balance_id' => $bankingAccount[Entity::BALANCE_ID],
                    'payout_service_enabled' => $bankingAccount[Entity::PAYOUT_SERVICE_ENABLED],
                    'account_type'=> $bankingAccount[Entity::ACCOUNT_TYPE]
                ]);


            if ($bankingAccount[Entity::ACCOUNT_TYPE] === Entity::ACCOUNT_TYPE_SHARED && $bankingAccount[Entity::PAYOUT_SERVICE_ENABLED] === 1)
            {
                return true;
            }
        }

        return false;
    }

    public function enablePayoutServiceOnBalanceIdForMerchant(string $merchantId, string $balanceId)
    {
        $this->trace->info(
            TraceCode::MIGRATING_MERCHANT_ON_BALANCE_ID_TO_PAYOUT_SERVICE,
            ['merchant_id' => $merchantId, 'balance_id' => $balanceId]
        );

        $bankingAccount = $this->getPayoutServiceBankingAccountByMerchantIdAndBalanceId($merchantId, $balanceId);

        if (empty($bankingAccount))
        {
            $this->createBankingAccountEntityIntoPayoutServiceDB($merchantId, $balanceId, true);
        }
        else
        {
            $this->updatePayoutServiceEnabledFlagInPayoutServiceBankingAccountWithMerchantOnboardingTime($merchantId, $balanceId, true);

        }
    }

    public function merchantAllBalanceIdsMigratedToAPIMonolithOrNot (string $merchantId, string $balanceId)
    {
        $payoutServiceBankingAccount = $this->getPayoutServiceBankingAccountByMerchantId($merchantId);

        if (empty($payoutServiceBankingAccount))
        {

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_BANKING_ACCOUNT_GET_BY_MERCHANT_ID_NOT_FOUND,
                ['merchant_id' => $merchantId]
            );

            return true;
        }

        foreach ($payoutServiceBankingAccount as $bankingAccount)
        {
            $this->trace->info(
                TraceCode::CHECKING_ALL_BALANCE_IDS_OF_MERCHANT_MIGRATED_TO_API_MONOLITH_OR_NOT,
                ['merchant_id' => $merchantId, 'balance_id' => $bankingAccount[Entity::BALANCE_ID],'payout_service_enabled' => $bankingAccount[Entity::PAYOUT_SERVICE_ENABLED]]
            );

            if ($bankingAccount[Entity::BALANCE_ID] != $balanceId && $bankingAccount[Entity::PAYOUT_SERVICE_ENABLED] === 1)
            {
                return false;
            }
        }
        return true;
    }
}
