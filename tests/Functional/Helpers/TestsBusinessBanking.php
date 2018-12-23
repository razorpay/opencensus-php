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

    protected function createPayout()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->createContact();
        $this->createFundAccount();

        $testData = [
            'request'  => [
                'method'  => 'POST',
                'url'     => '/payouts',
                'content' => [
                    'account_number'  => '2224440041626905',
                    'amount'          => 1000,
                    'currency'        => 'INR',
                    'fund_account_id' => $this->fundAccount['id'],
                    'purpose'         => 'refund',
                    'notes'           => [
                        'abc' => 'xyz',
                    ],
                ],
            ],
            'response' => [
                'content' => []
            ],
        ];

        $this->ba->privateAuth();
        $this->runRequestResponseFlow($testData);

        $payout = $this->getLastEntity('payout', true);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('txn_' . $payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        $this->payout= $payout;
        $this->transaction = $txn;
        $this->ba->publicAuth();
    }

    protected function createContact()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000010contact', 'email' => 'contact@razorpay.com', 'contact' => '8888888888', "name" => 'test user']);

        $this->contact = $contact;
    }

    protected function createFundAccount()
    {
        $testdata = [
            'request' => [
                'url' => '/fund_accounts',
                'method' => 'post',
                'content' => [
                    'account_type' => "bank_account",
                    'contact_id'   => $this->contact->getPublicId(),
                    'details' => [
                        'beneficiary_name' => "test",
                        'ifsc_code' => 'SBIN0007105',
                        'account_number' => '111000',
                    ],
                ],
            ],
            'response' => [
                'content' => [
                ],
            ],
        ];
        $this->ba->privateAuth();
        $this->fundAccount = $this->runRequestResponseFlow($testdata);
    }
}
