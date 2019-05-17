<?php

namespace RZP\Tests\Functional\Contacts;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FundAccountsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/FundAccountsTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testGetFundAccounts()
    {
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000000fa']);

        $this->startTest();
    }

    public function testFetchFundAccounts()
    {
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000001fa']);
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000002fa']);
        $this->fixtures->create('fund_account:vpa', ['id' => '100000000003fa']);

        $this->startTest();
    }

    public function testCreateFundAccountInactiveContact()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'active' => 0]);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccount()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);

        $expectedBankAccount = [
            'type'           => 'contact',
            'entity_id'      => '1000000contact',
            'ifsc_code'      => 'SBIN0007105',
            'account_number' => '111000111',
            'merchant_id'    => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedBankAccount, $bankAccount);
    }

    public function testCreateVpa()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        $vpa = $this->getLastEntity('vpa', true);

        $expectedVpaAttrs = [
            'entity_type' => 'contact',
            'entity_id'   => '1000000contact',
            'username'    => 'amitm',
            'handle'      => 'upi',
            'merchant_id' => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedVpaAttrs, $vpa);
    }

    public function testCreateCard()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->mockCardVault();

        $this->startTest();

        $card = $this->getLastEntity('card', true);

        $expectedCardAttrs = [
            'merchant_id'   => '10000000000000',
            'expiry_month'  => 4,
            'expiry_year'   => 2025,
        ];

        $this->assertArraySelectiveEquals($expectedCardAttrs, $card);
    }

    public function testCreateCardAndVpa()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWithoutContactOrCustomer()
    {
        $this->startTest();
    }

    public function testCreateFundAccountInvalidVpa()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountInvalidBankIfsc()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFromCustomer()
    {
        $this->fixtures->create('customer', ['id' => '1000facustomer']);

        $this->startTest();
    }

    public function testUpdateFundAccount()
    {
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000000fa']);

        $this->startTest();
    }

    public function testDeleteFundAccount()
    {
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000000fa']);

        $this->startTest();
    }
}
