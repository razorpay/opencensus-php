<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Detail;

class Core extends Base\Core
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

        $merchantDetails = $this->getMerchantDetailsFromInput($input);

        $bankAccountDetails = $this->getBankAccountDetailsFromInput($input[Entity::BANK_ACCOUNT]);

        (new BankAccount\Core)->createOrChangeBankAccount($bankAccountDetails, $account);

        (new Merchant\Detail\Core)->saveMerchantDetails($merchantDetails, $account);

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
        // @todo: Get BUSINESS_TYPE from input

        $merchantDetails = [
            Detail\Entity::SUBMIT => '1',
            Entity::BUSINESS_NAME => $input[Entity::ACCOUNT_DETAILS][Entity::BUSINESS_NAME],
            Entity::BUSINESS_TYPE => 1,
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
        $bankAccountDetails = [
            BankAccount\Entity::IFSC_CODE            => $input[BankAccount\Entity::IFSC_CODE],
            BankAccount\Entity::ACCOUNT_NUMBER       => $input[BankAccount\Entity::ACCOUNT_NUMBER],
            BankAccount\Entity::BENEFICIARY_NAME     => $input[BankAccount\Entity::BENEFICIARY_NAME],
            BankAccount\Entity::BENEFICIARY_ADDRESS1 => $input[BankAccount\Entity::BENEFICIARY_ADDRESS1],
            BankAccount\Entity::BENEFICIARY_ADDRESS2 => $input[BankAccount\Entity::BENEFICIARY_ADDRESS2],
            BankAccount\Entity::BENEFICIARY_ADDRESS3 => $input[BankAccount\Entity::BENEFICIARY_ADDRESS3],
            BankAccount\Entity::BENEFICIARY_ADDRESS4 => $input[BankAccount\Entity::BENEFICIARY_ADDRESS4],
            BankAccount\Entity::BENEFICIARY_EMAIL    => $input[BankAccount\Entity::BENEFICIARY_EMAIL],
            BankAccount\Entity::BENEFICIARY_MOBILE   => $input[BankAccount\Entity::BENEFICIARY_MOBILE],
            BankAccount\Entity::BENEFICIARY_CITY     => $input[BankAccount\Entity::BENEFICIARY_CITY],
            BankAccount\Entity::BENEFICIARY_STATE    => $input[BankAccount\Entity::BENEFICIARY_STATE],
            BankAccount\Entity::BENEFICIARY_COUNTRY  => $input[BankAccount\Entity::BENEFICIARY_COUNTRY],
            BankAccount\Entity::BENEFICIARY_PIN      => $input[BankAccount\Entity::BENEFICIARY_PIN],
        ];

        return $bankAccountDetails;
    }
}
