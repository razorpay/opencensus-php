<?php

namespace RZP\Tests\Functional\VirtualAccount;

use DB;
use Carbon\Carbon;
use RZP\Models\Customer;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\VirtualAccount\Status;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Error\ErrorCode;
use RZP\Models\OfflineChallan\Repository as OfflineChallanRepo;

class VirtualAccountForOrderTest extends TestCase
{
    use PaymentTrait;
    use VirtualAccountTrait;
    use DbEntityFetchTrait;

    const CERT_HEADER = "-----BEGIN%20CERTIFICATE-----%0A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%0A-----END%20CERTIFICATE-----%0A-----BEGIN%20CERTIFICATE-----%0AMIIFUTCCBDmgAwIBAgIQB5g2A63jmQghnKAMJ7yKbDANBgkqhkiG9w0BAQsFADBhMQswCQYDVQQGEwJVUzEVMBMGA1UEChMMRGlnaUNlcnQgSW5jMRkwFwYDVQQLExB3d3cuZGlnaWNlcnQuY29tMSAwHgYDVQQDExdEaWdpQ2VydCBHbG9iYWwgUm9vdCBDQTAeFw0yMDA3MTYxMjI1MjdaFw0yMzA1MzEyMzU5NTlaMFkxCzAJBgNVBAYTAlVTMRUwEwYDVQQKEwxEaWdpQ2VydCBJbmMxMzAxBgNVBAMTKlJhcGlkU1NMIFRMUyBEViBSU0EgTWl4ZWQgU0hBMjU2IDIwMjAgQ0EtMTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBANpuQ1VVmXvZlaJmxGVYotAMFzoApohbJAeNpzN+49LbgkrMLv2tblII8H43vN7UFumxV7lJdPwLP22qa0sV9cwCr6QZoGEobda+4pufG0aSfHQCQhulaqKpPcYYOPjTwgqJA84AFYj8l/IeQ8n01VyCurMIHA478ts2G6GGtEx0ucnEfV2QHUL64EC2yh7ybboo5v8nFWV4lx/xcfxoxkFTVnAIRgHrH2vUdOiV9slOix3z5KPs2rK2bbach8Sh5GSkgp2HRoS/my0tCq1vjyLJeP0aNwPd3rk5O8LiffLev9j+UKZo0tt0VvTLkdGmSN4h1mVY6DnGfOwp1C5SK0MCAwEAAaOCAgswggIHMB0GA1UdDgQWBBSkjeW+fHnkcCNtLik0rSNY3PUxfzAfBgNVHSMEGDAWgBQD3lA1VtFMu2bwo+IbG8OXsj3RVTAOBgNVHQ8BAf8EBAMCAYYwHQYDVR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUFBwMCMBIGA1UdEwEB/wQIMAYBAf8CAQAwNAYIKwYBBQUHAQEEKDAmMCQGCCsGAQUFBzABhhhodHRwOi8vb2NzcC5kaWdpY2VydC5jb20wewYDVR0fBHQwcjA3oDWgM4YxaHR0cDovL2NybDMuZGlnaWNlcnQuY29tL0RpZ2lDZXJ0R2xvYmFsUm9vdENBLmNybDA3oDWgM4YxaHR0cDovL2NybDQuZGlnaWNlcnQuY29tL0RpZ2lDZXJ0R2xvYmFsUm9vdENBLmNybDCBzgYDVR0gBIHGMIHDMIHABgRVHSAAMIG3MCgGCCsGAQUFBwIBFhxodHRwczovL3d3dy5kaWdpY2VydC5jb20vQ1BTMIGKBggrBgEFBQcCAjB+DHxBbnkgdXNlIG9mIHRoaXMgQ2VydGlmaWNhdGUgY29uc3RpdHV0ZXMgYWNjZXB0YW5jZSBvZiB0aGUgUmVseWluZyBQYXJ0eSBBZ3JlZW1lbnQgbG9jYXRlZCBhdCBodHRwczovL3d3dy5kaWdpY2VydC5jb20vcnBhLXVhMA0GCSqGSIb3DQEBCwUAA4IBAQAi49xtSOuOygBycy50quCThG45xIdUAsQCaXFVRa9asPaB/jLINXJL3qV9J0Gh2bZM0k4yOMeAMZ57smP6JkcJihhOFlfQa18aljd+xNc6b+GX6oFcCHGr+gsEyPM8qvlKGxc5T5eHVzV6jpjpyzl6VEKpaxH6gdGVpQVgjkOR9yY9XAUlFnzlOCpqsm7r2ZUKpDfrhUnVzX2nSM15XSj48rVBBAnGJWkLPijlACd3sWFMVUiKRz1C5PZyel2l7J/W4d99KFLSYgoy5GDmARpwLc//fXfkr40nMY8ibCmxCsjXQTe0fJbtrrLLyWQlk9VDV296EI/kQOJNLVEkJ54P%0A-----END%20CERTIFICATE-----%0A";

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/VirtualAccountTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.smart_routing.mock', true);

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

    public function testCreateVAFromCheckoutForOffline()
    {
        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $this->ba->privateAuth();

        $resp = $this->starttest();

        $terminalCreteData = [
            'id'                       => 'Oc3KkqYe4LjpdA',
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                  =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreteData);

        $this->fixtures->merchant->enableOffline();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder($resp['id'], ['customer_id' => $this->customer['id'],'receivers' => ['offline_challan']]);

        $this->assertEquals('offline_challan', $virtualAccount['receivers'][0]['entity']);

        $offlineData = [
        'property_id' => '12345',
        'property_value' => 'abc',
         ];

        $this->assertEquals($offlineData, $virtualAccount['order']['customer_additional_info']);

        $offlineData = DB::select('select * from offline_challans')[0];

        $this->assertEquals($offlineData->id, $virtualAccount['receivers'][0]['id']);

        $virtualAccountData = DB::select('select * from virtual_accounts')[0];

        $this->assertEquals($offlineData->id, $virtualAccountData->offline_challan_id);
    }

    public function testCreateVAFromCheckoutForOfflineWithoutMethod()
    {
        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $data = $this->testData["testCreateVAFromCheckoutForOffline"];
        $this->ba->privateAuth();

        $resp = $this->starttest($data);


        try
        {
            $this->createVirtualAccountForOfflineOrder($resp['id'], ['customer_id' => $this->customer['id'],'receivers' => ['offline_challan']]);
        }
        catch (\Exception $e)
        {
            $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_OFFLINE_NOT_ENABLED_FOR_MERCHANT, $e->getCode());
        }

    }


    public function testCheckCustomerInfoReturned() {
        // customer info should be returned when receivers => offline_challan
        $terminalCreteData = [
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                  =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreteData);

        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $this->ba->privateAuth();

        $resp = $this->starttest();

        $this->fixtures->merchant->enableOffline();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder($resp['id'], ['customer_id' => 'cust_100000customer','receivers' => ['offline_challan']]);

        $this->assertArrayHasKey( Customer\Entity::CONTACT, $virtualAccount);

        $this->assertArrayHasKey( Customer\Entity::EMAIL, $virtualAccount);
    }


    public function testCheckCustomerInfoNotReturned() {
        // customer info should not be returned when recievers is not offline_challan
        $terminalCreteData = [
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                  =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreteData);

        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $this->ba->privateAuth();

        $resp = $this->starttest();

        $this->fixtures->merchant->enableOffline();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder($resp['id'], ['customer_id' => 'cust_100000customer']);

        $this->assertArrayNotHasKey( Customer\Entity::CONTACT, $virtualAccount);

        $this->assertArrayNotHasKey( Customer\Entity::EMAIL, $virtualAccount);
    }



    public function testCreateVAFromCheckoutForOfflineNoReceiver()
    {
        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $data = $this->testData["testCreateVAFromCheckoutForOffline"];

        $this->ba->privateAuth();

        $resp = $this->starttest($data);

        $this->fixtures->merchant->enableOffline();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder($resp['id'], ['customer_id' => $this->customer['id']]);

        $this->assertEquals('bank_account', $virtualAccount['receivers'][0]['entity']);
    }

    public function testCreateVAFromCheckoutForOfflineWithoutMetadata()
    {
        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $this->ba->privateAuth();

        $resp = $this->starttest();

        $this->fixtures->merchant->enableOffline();


        try
        {
            $this->createVirtualAccountForOfflineOrder($resp['id'], ['customer_id' => $this->customer['id'],'receivers' => ['offline_challan']]);
        }
        catch (\Exception $e)
        {
            $this->assertEquals(ErrorCode::BAD_REQUEST_CUSTOMER_ADDITIONAL_INFO_NOT_PROVIDED, $e->getCode());
        }
    }

}
