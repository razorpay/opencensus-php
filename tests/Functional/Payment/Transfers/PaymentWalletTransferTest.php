<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Constants\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Payment\Transfers\TransferTrait;

class PaymentWalletTransferTest extends TestCase
{
    use PaymentTrait;
    use TransferTrait;

    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentWalletTransferTestData.php';

        parent::setUp();

        $this->fixtures->create('payment:authorized');

        $this->payment = $this->getLastEntity('payment', false);

        $this->ba->privateAuth();
    }

    public function testCaptureAndTransferToInvalidCustomerId()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $this->startTest();
    }

    public function testCreateWalletWithNonIndianContact()
    {
        $customer = $this->fixtures->create('customer', ['contact' => '+9293003939']);

        $this->fixtures->merchant->addFeatures(['openwallet']);

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

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $amount = $this->payment['amount'];

        $this->startTest();
    }

    public function testTransferToWalletFundsOnHold()
    {
        //
        // this been added as there is some issues in test framework
        // which resets the the mode to test
        // where as here we are operating on live mode.
        //
        $this->app['rzp.mode'] = 'live';

        $this->fixtures->merchant->holdFunds();

        // Merchant needs to be activated to make live requests
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $this->payment = $this->fixtures->on('live')->create('payment:captured')->toArrayPublic();

        $this->startTest(null, null, 'live');
    }

    public function testTransferToExistingCustomerWithNoExistingWallet()
    {
        $customer = $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures(['openwallet']);

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

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $oldBalanceAmount = $customerBalance->getBalance();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertSame($customerPublicId, $customerBalance['customer_id']);

        $this->assertSame($oldBalanceAmount + $amount, $customerBalance['balance']);

        $this->checkLastTransferEntity($customerPublicId, 'customer', $amount);
    }

    public function testTransferAndVerifyPricing()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => 14000]);

        $this->fixtures->merchant->addFeatures(['openwallet']);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, 50000);

        $transfer = $this->startTest()['items'][0];

        $expectedTransfer = [
            'amount'      => 50000,
            'fees'        => 1180,
            'tax'         => 180,
        ];

        $this->assertArraySelectiveEquals($expectedTransfer, $transfer);

        $txn = $this->getTransferTxn($transfer['id']);

        // 2% fee plan defined - standard pricing
        $expectedTxn = [
            'amount'      => 50000,
            'fee'         => 1180,
            'tax'         => 180,
            'debit'       => 51180,
            'credit'      => 0
        ];

        $this->assertArraySelectiveEquals($expectedTxn, $txn);
    }

    public function testTransferCustomerUsageFirstTxn()
    {
        $customerValues = [
            'balance'       => 100,
            'monthly_usage' => 100,
        ];

        $customerBalance = $this->fixtures->create('customer:customer_balance', $customerValues);

        $this->fixtures->merchant->addFeatures(['openwallet']);

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

    protected function getTransferTxn(string $entityId)
    {
        $entity = Entity::getEntityClass('transfer');

        $entity::verifyIdAndSilentlyStripSign($entityId);

        $txn = $this->getEntities('transaction', ['entity_id' => $entityId], true);

        $this->assertEquals(1, count($txn['items']));

        return $txn['items'][0];
    }
}
