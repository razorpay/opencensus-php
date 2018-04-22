<?php

namespace RZP\Models\Merchant\Account;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;

class Core extends Merchant\Core
{
    /**
     * Creates a linked account and activates it
     *
     * @param array           $input
     * @param Merchant\Entity $parentMerchant
     *
     * @return Entity
     */
    public function createAccount(array $input, Merchant\Entity $parentMerchant): Entity
    {
        //
        // When a linked account is created, mainly, 2 functions are executed -
        // 1. createSubMerchant
        // 2. saveMerchantDetails
        //
        // The first function creates a merchant entity and other supporting
        // entities like MerchantDetail, ScheduleTask, Method, etc. It also creates
        // a BankAccount entity in the Test database with dummy values so that the
        // merchant can start the integration using the test mode immediately.
        //
        // The second function accepts the actual bank account details of the merchant
        // and runs the createOrChangeBankAccount function call. This function creates
        // or updates the bankAccount entity in the database corresponding to the mode
        // that is extracted from the basic auth key used. Hence, if the key used
        // corresponds to live mode, a BankAccount entity will be created in the live
        // mode, but if it is used in the test mode, the entity that is already created
        // with the dummy data will be updated with the actual data and no entity will
        // be created in the Live mode,
        //
        // Hence, forcing the input mode to be live mode here, if not already.
        //
        $this->setMode(Mode::LIVE);

        (new Validator)->validateInput('create', $input);

        $merchantDetailsInput    = $this->getMerchantDetailsFromInput($input);
        $bankAccountDetailsInput = $this->getBankAccountDetailsFromInput($input[Entity::BANK_ACCOUNT] ?? []);
        $merchantDetailsInput    = array_merge($merchantDetailsInput, $bankAccountDetailsInput);

        $account = $this->repo->transactionOnLiveAndTest(function () use (
            $input,
            $parentMerchant,
            $merchantDetailsInput)
        {
            $account = (new Merchant\Core)->createSubMerchant(
                $input,
                $parentMerchant,
                true,
                true);

            (new Merchant\Detail\Core)->saveMerchantDetails($merchantDetailsInput, $account);

            return $account;

        });

        $this->trace->info(
            TraceCode::ACCOUNT_CREATED,
            [
                'account_id' => $account->getId(),
                'parent_id'  => $parentMerchant->getId(),
                'input'      => $input,
            ]);

        return $account->reload();
    }

    /**
     * Extracts and returns the merchant details fields from the input.
     *
     * @param array $input
     *
     * @return array
     */
    protected function getMerchantDetailsFromInput(array $input): array
    {
        $accountDetails = $input[Entity::ACCOUNT_DETAILS];

        $businessName = $accountDetails[Entity::BUSINESS_NAME];
        $businessType = $accountDetails[Entity::BUSINESS_TYPE];

        $merchantDetails = [
            Detail\Entity::BUSINESS_NAME => $businessName,
            Detail\Entity::BUSINESS_TYPE => Detail\BusinessType::getIndexFromKey($businessType),

            // TODO: take `submit` from input for the next version
            Detail\Entity::SUBMIT        => '1',
        ];

        return $merchantDetails;
    }

    /**
     * Extracts and returns the bank account details fields from the input.
     *
     * @param array $input
     *
     * @return array
     */
    protected function getBankAccountDetailsFromInput(array $input): array
    {
        $whitelistedBankAccountKeys = [
            Entity::IFSC_CODE,
            Entity::ACCOUNT_NUMBER,
            Entity::BENEFICIARY_NAME,
        ];

        $bankAccountDetails = array_only($input, $whitelistedBankAccountKeys);

        $bankAccountToDetailAttributesMap = Entity::$bankAccountToDetailAttributesMap;

        $merchantDetails = [];

        foreach($bankAccountDetails as $key => $value)
        {
            $merchantDetails[$bankAccountToDetailAttributesMap[$key]] = $value;
        }

        return $merchantDetails;
    }
}
