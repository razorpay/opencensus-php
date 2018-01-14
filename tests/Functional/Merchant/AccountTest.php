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

    public function testCreateLinkedAccount()
    {
        $this->fixtures->merchant->activate('10000000000000');

        //
        // Test account creation in - Test database (Live mode under the testing environment)
        // For more details, refer to Merchant/Account/Core::createAccount() function.
        //
        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $account = $this->startTest();

        $lastAccount = $this->getLastEntity('merchant', true);

        $accountId = Account\Entity::getSignedId($lastAccount['id']);

        $this->assertEquals($account['id'], $accountId);

        $this->assertEquals('10000000000000', $lastAccount['parent_id']);

        $this->assertNotNull($account['fund_transfer']['destination']);
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

    public function testSettlementDestinations()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $accountId = Account\Entity::getSignedId($merchant['id']);

        Mail::fake();

        $testData = $this->testData['addSettlementDestination'];

        $testData['request']['url'] = '/beta/accounts/' . $accountId . '/bank_accounts';

        $this->startTest($testData);

        Mail::assertNotSent(BankAccountChangeMail::class);

        $testData = $this->testData['fetchSettlementDestinations'];

        $testData['request']['url'] = '/beta/accounts/' . $accountId . '/settlement_destinations';

        $this->startTest($testData);
    }
}
