<?php

use Carbon\Carbon;
use RZP\Models\BankTransfer\Entity as BankTransfer;
use RZP\Tests\TestCase;

class BankTransferEntityTest extends TestCase
{
    protected $bt;

    public function setUp()
    {
        parent::setUp();
        $this->bt = new BankTransfer();
    }

    public function testCanaraBankAccountNonIMPS()
    {
        $this->bt->build([
            BankTransfer::PAYER_IFSC    => "CNRB1234567",
            BankTransfer::PAYER_ACCOUNT => "000000000000123",
            BankTransfer::AMOUNT        => 10,
            BankTransfer::PAYEE_ACCOUNT => "1234567890",
            BankTransfer::PAYEE_IFSC    => "RAZO1234567",
            BankTransfer::MODE          => "NEFT",
            BankTransfer::REQ_UTR       => "1234",
            BankTransfer::TIME          => Carbon::now()
        ]);

        $this->assertSame("123", $this->bt->getPayerAccount(), "Leading 0s should be stripped");
    }

    public function testCanaraBankAccountIMPS()
    {
        $this->bt->build([
            BankTransfer::PAYER_IFSC    => "CNB1234567890",
            BankTransfer::PAYER_ACCOUNT => "000000000000123",
            BankTransfer::AMOUNT        => 10,
            BankTransfer::PAYEE_ACCOUNT => "1234567890",
            BankTransfer::PAYEE_IFSC    => "RAZO1234567",
            BankTransfer::MODE          => "imps",
            BankTransfer::REQ_UTR       => "1234",
            BankTransfer::TIME          => Carbon::now()
        ]);

        $this->assertSame("123", $this->bt->getPayerAccount(), "Leading 0s should be stripped");
    }

    public function testOtherAccount()
    {
        $this->bt->build([
            BankTransfer::PAYER_IFSC    => "CUCX1234567",
            BankTransfer::PAYER_ACCOUNT => "000000000000123",
            BankTransfer::AMOUNT        => 10,
            BankTransfer::PAYEE_ACCOUNT => "1234567890",
            BankTransfer::PAYEE_IFSC    => "RAZO1234567",
            BankTransfer::MODE          => "NEFT",
            BankTransfer::REQ_UTR       => "1234",
            BankTransfer::TIME          => Carbon::now()
        ]);

        $this->assertSame("000000000000123", $this->bt->getPayerAccount(), "Leading 0s should not be stripped");
    }
}