<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Payment\Transfers\TransferTrait;

class PaymentMarketplaceTransferTests extends TestCase
{
    use PaymentTrait;
    use TransferTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentMarketplaceTransferTestData.php';

        parent::setUp();

        $this->fixtures->create('payment:authorized');

        $this->payment = $this->getLastEntity('payment', false);

        $this->ba->privateAuth();
    }

    public function testAccTransferToInvalidOrUnlinkedId()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->startTest();
    }

    public function testAccTransferWithFeatureNotEnabled()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->create('merchant:marketplace_account');

        $this->startTest();
    }
}
