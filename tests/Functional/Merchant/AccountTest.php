<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Mail\Merchant\AccountChange as BankAccountChangeMail;

class AccountTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AccountTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->privateAuth();
    }

    public function testRetrieveAccount()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->startTest();
    }

    public function testRetrieveAccounts()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->startTest();
    }

    public function testCreateLinkedAccount()
    {
        $account = $this->startTest();

        $lastAccount = $this->getLastEntity('merchant', true);

        $this->assertEquals($account['id'], 'acc_' . $lastAccount['id']);

        $this->assertEquals('10000000000000', $lastAccount['parent_id']);
    }

    /**
     * Tests settlement destinations for linked accounts as well as regular merchant accounts.
     */
    public function testSettlementDestinations()
    {
        $this->settlementDestinations('10000000000000', false);

        $this->settlementDestinations('100000Razorpay', true);
    }

    private function settlementDestinations(string $accountId, bool $isLinkedAccount)
    {
        Mail::fake();

        $testData = $this->testData['addSettlementDestination'];

        $testData['request']['url'] = '/beta/accounts/' . $accountId . '/bank-accounts';

        $testData['response']['content']['merchant_id'] = $accountId;

        $this->startTest($testData);

        if ($isLinkedAccount === true)
        {
            Mail::assertNotSent(BankAccountChangeMail::class);
        }
        else
        {
            Mail::assertSent(BankAccountChangeMail::class, function ($mail) use ($testData)
            {
                $this->assertArraySelectiveEquals($testData['response']['content'], $mail->viewData);

                return true;
            });
        }

        $testData = $this->testData['fetchSettlementDestinations'];

        $testData['request']['url'] = '/beta/accounts/' . $accountId . '/settlement-destinations';

        $testData['response']['content'][0]['merchant_id'] = $accountId;

        $this->startTest($testData);
    }
}
