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

        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->create('merchant:marketplace_account');

        $this->ba->privateAuth();
    }

    public function testTransferToInvalidOrUnlinkedId()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->startTest();
    }

    public function testTransferWithFeatureNotEnabled()
    {
        $this->startTest();
    }

    public function testMultipleTransfersOnSameAccountId()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $testData = $this->testData[__FUNCTION__];

        $this->ba->privateAuth();

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $this->startTest();
    }
}
