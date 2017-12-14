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
        $this->fixtures->create('merchant:marketplace_account');

        $this->startTest();
    }

    public function testRetrieveAccounts()
    {
        $this->fixtures->create('merchant:marketplace_account');

        $this->startTest();
    }

    public function testCreateLinkedAccount()
    {
        $account = $this->startTest();

        $lastAccount = $this->getLastEntity('merchant', true);

        $this->assertEquals($account['id'], 'acc_' . $lastAccount['id']);

        $this->assertEquals('10000000000000', $lastAccount['parent_id']);
    }

    public function testAddSettlementDestination()
    {
        Mail::fake();

        $this->startTest();

        Mail::assertSent(BankAccountChangeMail::class, function ($mail)
        {
            $testData = $this->testData['testAddSettlementDestination']['response']['content'];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }
}
