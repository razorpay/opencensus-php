<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Models\Payout;
use RZP\Services\RazorXClient;
use RZP\Models\Settlement\Channel;
use RZP\Models\Merchant\Balance\AccountType;

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
     * @var \RZP\Models\Contact\Entity|null
     */
    protected $contact;

    /**
     * @var \RZP\Models\Transaction\Entity|null
     */
    protected $transaction;

    /**
     * @var \RZP\Models\Payout\Entity|null
     */
    protected $payout;

    /**
     * Setup merchant for business banking.
     *
     * @param bool $skipFeatureAddition
     * @param int $balance
     * @param string $balanceType
     * @param string $channel
     */
    protected function setUpMerchantForBusinessBanking(
        bool $skipFeatureAddition = false,
        int $balance = 0,
        string $balanceType = AccountType::SHARED,
        $channel = null)
    {
        // Activate merchant with business_banking flag set to true.
        $this->fixtures->merchant->edit('10000000000000', ['business_banking' => 1]);
        $this->fixtures->merchant->activate();

        // Creates banking balance

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balance, '10000000000000',$balanceType, $channel);

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
            $this->fixtures->merchant->addFeatures(['virtual_accounts', 'payout']);
        }

        // Additionally, creates a terminal for bank transfer on banking balance.
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Sets instance member variable to be re-usable in other test methods for assertions.
        $this->bankingBalance = $bankingBalance;
        $this->virtualAccount = $virtualAccount;
        $this->bankAccount    = $bankAccount;
    }

    protected function setUpMerchantForBusinessBankingLive(
        bool $skipFeatureAddition = false,
        int $balance = 0,
        string $balanceType = AccountType::SHARED,
        $channel = Channel::YESBANK)
    {
        // Activate merchant with business_banking flag set to true.
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);
        $this->fixtures->on('live')->merchant->activate();

        // Creates banking balance
        $bankingBalance = $this->fixtures->on('live')->merchant->createBalanceOfBankingType(
            $balance, '10000000000000',$balanceType, $channel);

        // Creates virtual account, its bank account receiver on new banking balance.
        $virtualAccount = $this->fixtures->on('live')->create('virtual_account');
        $bankAccount    = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'id'             => '1000000lcustba',
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
            $this->fixtures->on('live')->merchant->addFeatures(['virtual_accounts', 'payout']);
        }

        // Sets instance member variable to be re-usable in other test methods for assertions.
        $this->bankingBalance = $bankingBalance;
        $this->virtualAccount = $virtualAccount;
        $this->bankAccount    = $bankAccount;
    }

    protected function createPayout()
    {
        $this->createContact();

        $this->createFundAccount();

        $this->payout = $this->fixtures->create(
            'payout',
            [
                'purpose'           => 'refund',
                'fund_account_id'   => $this->fundAccount['id'],
                'notes'             => [
                    'abc' => 'xyz',
                ],
                'amount'            => 1000,
                'currency'          => 'INR',
                'balance_id'        => $this->bankingBalance->getId(),
            ]);

        $this->transaction = $this->getDbLastEntity('transaction');
    }

    protected function reversePayout(Payout\Entity $payout)
    {
        // TODO: Fix this shit with proper fixtures
        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'     => 'failed',
            'failure_reason' => '',
        ]);
    }

    public function createContact()
    {
        $this->contact = $this->fixtures->create(
            'contact',
            [
                'id'      => '1000010contact',
                'email'   => 'contact@razorpay.com',
                'contact' => '8888888888',
                'name'    => 'test user'
            ]);
    }

    protected function createFundAccount()
    {
        $this->fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $this->contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);
    }

    protected function createVpaFundAccount(array $attributes = [])
    {
        $this->contact === null ? $this->createContact() : $this->contact ;

        $defaultAttributes = [
            'source_id'   => $this->contact->getId(),
            'source_type' => 'contact',
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        return $this->fixtures->fund_account->createVpa($attributes);
    }

    protected function createFAVBankingPricingPlan()
    {
        $pricingPlan = [
            'plan_name'           => 'FAV Plan',
            'percent_rate'        => 290,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'plan_id'             => '1hDYlICobzOCYt',
            'product'             => 'banking',
            'feature'             => 'fund_account_validation',
            'payment_method'      => 'bank_account',
            'account_type'        => 'shared'
        ];

        $this->fixtures->create('pricing', $pricingPlan);
    }

    protected function mockRazorxTreatment(string $channel = 'yesbank',
                                           string $ftsEnabled = 'off',
                                           string $webhookViaStork = 'off',
                                           string $defaultBehaviour = 'off')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                function ($mid, $feature, $mode) use ($channel, $ftsEnabled, $defaultBehaviour)
                {
                    if (ends_with($feature, 'mode_payout_filter'))
                    {
                        return strtolower($channel);
                    }

                    if (starts_with($feature, 'fts_'))
                    {
                        return strtolower($ftsEnabled);
                    }

                    return strtolower($defaultBehaviour);
                }));

        $this->app->razorx->method('getCachedTreatment')
                          ->willReturn(strtolower($webhookViaStork));
    }

    protected function createWorkflowFeature(array $attributes = [])
    {
        $defaultAttributes = [
            'name'        => 'payout_workflows',
            'entity_id'   => '10000000000000',
            'entity_type' => 'merchant',
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        return $this->fixtures->create('feature', $attributes);
    }
}
