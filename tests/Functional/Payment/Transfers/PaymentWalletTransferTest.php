<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Payment\Transfers\TransferTrait;

class PaymentWalletTransferTest extends TestCase
{
    use PaymentTrait;
    use TransferTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentWalletTransferTestData.php';

        parent::setUp();

        $this->fixtures->create('payment:authorized');

        $this->payment = $this->getLastEntity('payment', false);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $this->ba->privateAuth();
    }

    public function testCaptureAndTransferToInvalidCustomerId()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->merchant->addFeatures(['b2bwallet']);

        $this->startTest();
    }

    public function testCreateWalletWithNonIndianContact()
    {
        $customer = $this->fixtures->create('customer', ['contact' => '+9293003939']);

        $customerPublicId = $customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();
    }

    public function testCustomerTransferB2bNotEnabled()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->startTest();
    }

    public function testCaptureAndTransferToUnknownCustomerId()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->merchant->addFeatures(['b2bwallet']);

        $amount = $this->payment['amount'];

        $this->startTest();
    }

    public function testTransferToExistingCustomerWithNoExistingWallet()
    {
        $customer = $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures(['b2bwallet']);

        $customerPublicId = $customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertSame($customerPublicId, $customerBalance['customer_id']);

        $this->assertSame($amount, $customerBalance['balance']);
    }

    public function testTransferAndVerifyCustomerBalance()
    {
        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => 14000]);

        $this->fixtures->merchant->addFeatures(['b2bwallet']);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $customerId = $customerBalance->customer->getPublicId();

        $oldBalanceAmount = $customerBalance->getBalance();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertSame($customerPublicId, $customerBalance['customer_id']);

        $this->assertSame($oldBalanceAmount + $amount, $customerBalance['balance']);

        $this->checkLastTransferEntity('cust_' . $customerId, 'customer', $amount);
    }

    public function testTransferCustomerUsageFirstTxn()
    {
        $customerValues = [
            'balance'       => 100,
            'monthly_usage' => 100,
        ];

        $customerBalance = $this->fixtures->create('customer:customer_balance', $customerValues);

        $this->fixtures->merchant->addFeatures(['b2bwallet']);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->assertNull($this->getLastEntity('customer_transaction', true));

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $expected = [
            'balance'       => $amount + 100,
            'monthly_usage' => $amount,
        ];

        $this->assertArraySelectiveEquals($expected, $customerBalance);
    }
}
