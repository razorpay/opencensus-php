<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
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

        $account = (new Merchant\Core)->createSubMerchant(
                        $input,
                        $parentMerchant,
                        true,
                        true);

        $merchantDetailsInput    = $this->getMerchantDetailsFromInput($input);
        $bankAccountDetailsInput = $this->getBankAccountDetailsFromInput($input[Entity::BANK_ACCOUNT] ?? []);

        (new BankAccount\Core)->createOrChangeBankAccount($bankAccountDetailsInput, $account);
        (new Merchant\Detail\Core)->saveMerchantDetails($merchantDetailsInput, $account);

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
        $businessName = $input[Entity::ACCOUNT_DETAILS][Entity::BUSINESS_NAME];
        $businessType = $input[Entity::ACCOUNT_DETAILS][Entity::BUSINESS_TYPE];

        $merchantDetails = [
            Detail\Entity::SUBMIT => '1',
            Entity::BUSINESS_NAME => $businessName,
            Entity::BUSINESS_TYPE => Detail\BusinessType::getIndexFromKey($businessType),
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
        $bankAccountDetailsKeys = [
            BankAccount\Entity::IFSC_CODE,
            BankAccount\Entity::ACCOUNT_NUMBER,
            BankAccount\Entity::BENEFICIARY_NAME,
            BankAccount\Entity::BENEFICIARY_ADDRESS1,
            BankAccount\Entity::BENEFICIARY_ADDRESS2,
            BankAccount\Entity::BENEFICIARY_ADDRESS3,
            BankAccount\Entity::BENEFICIARY_ADDRESS4,
            BankAccount\Entity::BENEFICIARY_EMAIL,
            BankAccount\Entity::BENEFICIARY_MOBILE,
            BankAccount\Entity::BENEFICIARY_CITY,
            BankAccount\Entity::BENEFICIARY_STATE,
            BankAccount\Entity::BENEFICIARY_COUNTRY,
            BankAccount\Entity::BENEFICIARY_PIN,
        ];

        return array_only($input, $bankAccountDetailsKeys);
    }
}
