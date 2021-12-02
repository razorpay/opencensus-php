<?php

namespace RZP\Tests\Functional\VirtualAccount;

use Carbon\Carbon;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\VirtualAccount\Status;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use RZP\Tests\Functional\TestCase;

class VirtualAccountForOrderTest extends TestCase
{
    use PaymentTrait;
    use VirtualAccountTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/VirtualAccountTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');
    }

    public function testSingleVirtualAccountForMultipleOrders()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);
        $order1 = $this->fixtures->create('order');

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1, ['customer_id' => $this->customer['id']]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals($order1->getAmountDue(), $virtualAccountEntity1['amount_expected']);
        $this->assertEquals(Status::ACTIVE, $virtualAccountEntity1['status']);
        $this->assertEquals($order1->getId(), $virtualAccountEntity1['entity_id']);
        $this->assertEquals('order', $virtualAccountEntity1['entity_type']);

        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $this->customer['id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals($order2->getAmountDue(), $virtualAccountEntity2['amount_expected']);
        $this->assertEquals(Status::ACTIVE, $virtualAccountEntity2['status']);
        $this->assertEquals($order2->getId(), $virtualAccountEntity2['entity_id']);
        $this->assertEquals('order', $virtualAccountEntity2['entity_type']);
    }

    public function testVirtualAccountPaymentForMultipleOrder()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);

        $order1 = $this->fixtures->create('order');

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1, ['customer_id' => $this->customer['id']]);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => $order1['amount_due'] / 100]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(Status::PAID, $virtualAccountEntity1['status']);
        $this->assertEquals($virtualAccount1['amount_expected'], $virtualAccountEntity1['amount_paid']);

        $order = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals(Status::PAID, $order['status']);

        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $this->customer['id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount2['status']);

        $this->payVirtualAccount($virtualAccount2['id'], ['amount' => $order2['amount_due'] / 100]);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(Status::PAID, $virtualAccountEntity2['status']);
        $this->assertEquals($virtualAccount2['amount_expected'], $virtualAccountEntity2['amount_paid']);

        $order = $this->getEntityById('order', $order2['id'], true);
        $this->assertEquals(Status::PAID, $order['status']);
    }

    public function testCreateVAFromCheckoutForClosedVA()
    {
        $order1 = $this->fixtures->create('order');

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1, ['customer_id' => $this->customer['id']]);

        $this->closeVirtualAccount($virtualAccount1['id']);

        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $this->customer['id']]);

        $this->assertNotEquals($virtualAccount1['id'], $virtualAccount2['id']);
    }

    public function testCreateVirtualAccountForInvalidCustomer()
    {
        $order = $this->fixtures->create('order');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($order) {

            $this->createVirtualAccountForOrder($order, ['customer_id' => 'cust_qwerty']);
        });
    }

    public function testPayVirtualAccountForOlderUnpaidOrder()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);
        $order1 = $this->fixtures->create('order');

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1, ['customer_id' => $this->customer['id']]);

        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $this->customer['id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount2['status']);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => $order1['amount_due'] / 100]);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(Status::ACTIVE, $virtualAccountEntity2['status']);

        $order = $this->getEntityById('order', $order2['id'], true);
        $this->assertEquals('created', $order['status']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testCreateVirtualAccountForSameCustomerWithUnpaidOrderCheckoutVaWithCustomerFeatureIsNotEnabled()
    {
        $order1 = $this->fixtures->create('order');

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1, ['customer_id' => $this->customer['id']]);

        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $this->customer['id']]);

        $this->assertNotEquals($virtualAccount1['id'], $virtualAccount2['id']);

        $this->assertNotNull($virtualAccount1['customer_id']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount1['status']);
        $this->assertNotNull($virtualAccount2['customer_id']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount2['status']);

        $orderOneData = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals('created', $orderOneData['status']);

        $orderTwoData = $this->getEntityById('order', $order2['id'], true);
        $this->assertEquals('created', $orderTwoData['status']);
    }

    public function testPayVirtualAccountForOrderPartialPayment()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);

        $order1 = $this->fixtures->create('order', ['partial_payment' => true]);

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1, ['customer_id' => $this->customer['id']]);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => 5000]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(Status::ACTIVE, $virtualAccountEntity1['status']);

        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment1['method']);
        $this->assertEquals('captured', $payment1['status']);
        $this->assertEquals('order_' . $order1['id'], $payment1['order_id']);;

        $order2 = $this->fixtures->create('order', ['partial_payment' => true]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $this->customer['id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount2['status']);

        $this->payVirtualAccount($virtualAccount2['id'], ['amount' => 5000]);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(Status::ACTIVE, $virtualAccountEntity2['status']);

        $payment2 = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment2['method']);
        $this->assertEquals('captured', $payment2['status']);
        $this->assertEquals('order_' . $order2['id'], $payment2['order_id']);;
    }
}
