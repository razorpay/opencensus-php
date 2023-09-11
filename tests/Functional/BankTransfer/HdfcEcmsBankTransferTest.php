<?php

namespace Functional\BankTransfer;

use DB;
use Mail;
use Cache;
use RZP\Models\Order;
use RZP\Models\Feature;
use RZP\Models\Pricing\Fee;
use RZP\Services\RazorXClient;
use RZP\Models\VirtualAccount;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class HdfcEcmsBankTransferTest extends TestCase
{
    use AttemptTrait;
    use CreatesInvoice;
    use FileHandlerTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use AttemptReconcileTrait;
    use ReconTrait;

    protected $virtualAccountId;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/BankTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->createAccount('BankAccountMer');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->merchant->edit('BankAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['virtual_accounts'], 'BankAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('BankAccountMer', 'bank_transfer');
        $this->fixtures->on('live')->merchant->edit('BankAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->createTerminals();

        $this->bankAccount = $this->createVirtualAccount();

        $this->enableRazorXTreatmentForBanKTransferDisableGateway();
    }

    protected function createTerminals()
    {
        // Creating Fallback Terminals,
        // Fallback Terminals are those terminals which are created with just Root
        // and are assigned to the Shared Merchant to get unexpected payments.
        $terminalAttributes = [ 'id' => 'GENERICBANKACC', 'gateway_merchant_id2' => '', 'enabled' => false ];
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $terminalAttributes = ['id' => 'GENERICABNKACC', 'gateway_merchant_id2' => '', 'enabled' => false];
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal_alpha_num', $terminalAttributes);

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal_alpha_num');

        $terminalAttributes = [ 'id' => 'GENERICBANKACC', 'gateway_merchant_id2' => '', 'enabled' => false, 'gateway' => Gateway::BT_YESBANK ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $terminalAttributes = [ 'gateway' => Gateway::BT_YESBANK ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:vpa_shared_terminal_icici');

        $this->fixtures->on('test');
    }

    public function enableRazorXTreatmentForBanKTransferDisableGateway()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::BANK_TRANSFER_DISABLE_GATEWAY))
                {
                    return 'on';
                }
                return 'control';
            });
    }


    public function testHdfcEcmsBankTransferCallback()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UniqueID']);
        $this->assertEquals(1000000, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(1000000, $payment['amount']);
        $this->assertEquals('bt_hdfc_ecms', $payment['gateway']);
    }

    public function testHdfcFailVAOnValidation()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ["amount" => 2000]);

        $virtualAccount = $this->createVirtualAccountForOrder($order)['receivers'][0];

        $this->fixtures->create('feature', [
            'name' => Feature\Constants::FAIL_VA_ON_VALIDATION,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);


        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['account_number'];

        $this->ba->hdfcEcmsAuth();


        $responseData = $this->runRequestResponseFlow($testData);

        $updatedVirtualAccount = $this->getLastEntity('virtual_account', true);

        $this->assertEquals(VirtualAccount\Status::CLOSED, $updatedVirtualAccount['status']);
    }

    public function testHdfcFailVAOnValidationWithoutFeatureFlag()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ["amount" => 2000]);

        $virtualAccount = $this->createVirtualAccountForOrder($order)['receivers'][0];

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['account_number'];

        $this->ba->hdfcEcmsAuth();


        $responseData = $this->runRequestResponseFlow($testData);

        $updatedVirtualAccount = $this->getLastEntity('virtual_account', true);

        $this->assertEquals(VirtualAccount\Status::ACTIVE, $updatedVirtualAccount['status']);
    }

    public function testHdfcEcmsBankTransferCallbackBadRequest()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferDuplicateTransaction()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $request = $testData['request'];

        $request['content']['UniqueID'] = '02081900018';

        $this->makeRequestAndGetContent($request);

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferTransactionNotFound()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $testData = $this->testData[__FUNCTION__];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackAlreadyProcessed()
    {

        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');


        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $request = $testData['request'];

        $this->makeRequestAndGetContent($request);

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackExpiry()
    {
        $this->fixtures->create('feature', [
            'name' => Feature\Constants::SET_VA_DEFAULT_EXPIRY,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);

        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');


        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackMaxAmountThresholdExceeded()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1000010]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureHigherAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureHigherAmountFailure()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureLowerAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::ACCEPT_LOWER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureLowerAmountFailure()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::ACCEPT_LOWER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureHigherAndLowerAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');
        $this->fixtures->merchant->addFeatures(Feature\Constants::ACCEPT_LOWER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureHigherWithPartialPayment()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ['partial_payment' => true]);

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature/
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');
        $virtualAccount = $this->createVirtualAccountForOrder($order)['receivers'][0];

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['account_number'];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
        }

    public function testHdfcEcmsBankTransferCallbackPartialPaymentExceedsOrderAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ['partial_payment' => true]);

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature/
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $virtualAccount = $this->createVirtualAccountForOrder($order);

        $this->fixtures->order->edit($order['id'], ['amount_paid' => 1150000]);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['receivers'][0]['account_number'];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackPartialPaymentExceedsThresholdAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ['partial_payment' => true]);

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature/
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $virtualAccount = $this->createVirtualAccountForOrder($order);

        $this->fixtures->order->edit($order['id'], ['amount_paid' => 115000]);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['receivers'][0]['account_number'];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsSingleVAwithFeatureExcessPayment()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT);

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');

        $order1 = $this->fixtures->create('order');

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1);
        $this->fixtures->base->editEntity(
            'virtual_account',
            $virtualAccount1['id'],
            [
                'customer_id' => '100000customer'
            ]);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => ($order1['amount_due'] / 100) + 100]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity1['status']);
        $this->assertEquals($virtualAccount1['amount_expected']+10000, $virtualAccountEntity1['amount_paid']);

        $order = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);

        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $virtualAccountEntity1['customer_id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);
        $this->assertEquals(VirtualAccount\Status::ACTIVE, $virtualAccount2['status']);

        $this->payVirtualAccount($virtualAccount2['id'], ['amount' => $order2['amount_due'] / 100]);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity2['status']);
        $this->assertEquals($virtualAccount2['amount_expected'], $virtualAccountEntity2['amount_paid']);

        $order = $this->getEntityById('order', $order2['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);
    }

    public function testHdfcEcmsSingleVAwithFeatureLowerPayment()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);
        $this->fixtures->merchant->addFeatures(Feature\Constants::ACCEPT_LOWER_AMOUNT);

        $order1 = $this->fixtures->create('order');

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1);
        $this->fixtures->base->editEntity(
            'virtual_account',
            $virtualAccount1['id'],
            [
                'customer_id' => '100000customer'
            ]);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => ($order1['amount_due'] / 100) - 100]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity1['status']);
        $this->assertEquals($virtualAccount1['amount_expected'] - 10000, $virtualAccountEntity1['amount_paid']);

        $order = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);

        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $virtualAccountEntity1['customer_id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);
        $this->assertEquals(VirtualAccount\Status::ACTIVE, $virtualAccount2['status']);

        $this->payVirtualAccount($virtualAccount2['id'], ['amount' => $order2['amount_due'] / 100]);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity2['status']);
        $this->assertEquals($virtualAccount2['amount_expected'], $virtualAccountEntity2['amount_paid']);

        $order = $this->getEntityById('order', $order2['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);
    }

    public function testHdfcEcmsSingleVAwithFeatureHigherPaymentAndPartialPayment()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT);

        $order1 = $this->fixtures->create('order',['partial_payment' => true]);

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1);
        $this->fixtures->base->editEntity(
            'virtual_account',
            $virtualAccount1['id'],
            [
                'customer_id' => '100000customer'
            ]);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => ($order1['amount_due'] / 100) - 100]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::ACTIVE, $virtualAccountEntity1['status']);
        $this->assertEquals($virtualAccount1['amount_expected'] - 10000, $virtualAccountEntity1['amount_paid']);

        $order = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals(Order\Status::ATTEMPTED, $order['status']);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => 1000]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity1['status']);

        $order = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals(Order\Status::PAID, $order['status']);


        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $virtualAccountEntity1['customer_id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);
        $this->assertEquals(VirtualAccount\Status::ACTIVE, $virtualAccount2['status']);

        $this->payVirtualAccount($virtualAccount2['id'], ['amount' => $order2['amount_due'] / 100]);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity2['status']);
        $this->assertEquals($virtualAccount2['amount_expected'], $virtualAccountEntity2['amount_paid']);

        $order = $this->getEntityById('order', $order2['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);
    }

    protected function getHdfcEcmsVaBankAccount()
    {
        $order = $this->fixtures->create('order');

        $bankAccount = $this->createVirtualAccountForOrder($order)['receivers'][0];

        return $bankAccount['account_number'];
    }
}
