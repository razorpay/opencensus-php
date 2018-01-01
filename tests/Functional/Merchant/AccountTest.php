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

        $testData['request']['url'] = '/beta/accounts/' . $accountId . '/bank-accounts';

        $this->startTest($testData);

        $testData = $this->testData['fetchSettlementDestinations'];

        $testData['request']['url'] = '/beta/accounts/' . $accountId . '/settlement-destinations';

        $this->startTest($testData);
    }
}
