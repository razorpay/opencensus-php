<?php

namespace RZP\Tests\Functional\Helpers;

/**
 * Consists reusable methods to help with business banking related tests.
 */
trait TestsBusinessBanking
{

    /**
     * @var \RZP\Models\Merchant\Balance\Entity|null
     */
    protected $bankingBalance;

    /**
     * @var \RZP\Models\VirtualAccount\Entity|null
     */
    protected $virtualAccount;

    /**
     * @var \RZP\Models\BankAccount\Entity|null
     */
    protected $bankAccount;

    /**
     * Setup merchant for business banking.
     *
     * @param bool $skipFeatureAddition
     * @param int  $balance
     */
    protected function setUpMerchantForBusinessBanking(bool $skipFeatureAddition = false, int $balance = 0)
    {
        // Activate merchant with business_banking flag set to true.
        $this->fixtures->merchant->edit('10000000000000', ['business_banking' => 1]);
        $this->fixtures->merchant->activate();

        // Creates banking balance
        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType($balance);

        // Creates virtual account, its bank account receiver on new banking balance.
        $virtualAccount = $this->fixtures->create('virtual_account');
        $bankAccount    = $this->fixtures->create(
            'bank_account',
            [
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626905',
                'ifsc_code'      => 'RAZRB000000',
            ]);
        $virtualAccount->bankAccount()->associate($bankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();
        // Updates banking balance's account number after bank account creation.
        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

        // Enables required features on merchant
        if ($skipFeatureAddition === false)
        {
            $this->fixtures->merchant->addFeatures(['virtual_accounts']);
        }

        // Additionally, creates a terminal for bank transfer on banking balance.
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Sets instance member variable to be re-usable in other test methods for assertions.
        $this->bankingBalance = $bankingBalance;
        $this->virtualAccount = $virtualAccount;
        $this->bankAccount    = $bankAccount;
    }
}
