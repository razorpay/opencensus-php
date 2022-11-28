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

    const CERT_HEADER = "MIIEijCCA3KgAwIBAgISAzgKAtod4gDjTIBJ8WjAsm2QMA0GCSqGSIb3DQEBCwUAMEoxCzAJBgNVBAYTAlVTMRYwFAYDVQQKEw1MZXQncyBFbmNyeXB0MSMwIQYDVQQDExpMZXQncyBFbmNyeXB0IEF1dGhvcml0eSBYMzAeFw0yMDAxMjAxMTM1MDhaFw0yMDA0MTkxMTM1MDhaMBkxFzAVBgNVBAMTDm1lLmNhcHRuZW1vLmluMFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEKiHRLPycuRmk4Axg4iVsx%2FLCsy4HMsGY9eaKDQf1CdOBlCesfiV2nFV2uDDEoCSXew7pdT6euOqXxof5AK7KO6OCAmQwggJgMA4GA1UdDwEB%2FwQEAwIHgDAdBgNVHSUEFjAUBggrBgEFBQcDAQYIKwYBBQUHAwIwDAYDVR0TAQH%2FBAIwADAdBgNVHQ4EFgQU91WgMLm1jARQ9lvpUhbn4qWj4dwwHwYDVR0jBBgwFoAUqEpqYwR93brm0Tm3pkVl7%2FOo7KEwbwYIKwYBBQUHAQEEYzBhMC4GCCsGAQUFBzABhiJodHRwOi8vb2NzcC5pbnQteDMubGV0c2VuY3J5cHQub3JnMC8GCCsGAQUFBzAChiNodHRwOi8vY2VydC5pbnQteDMubGV0c2VuY3J5cHQub3JnLzAZBgNVHREEEjAQgg5tZS5jYXB0bmVtby5pbjBMBgNVHSAERTBDMAgGBmeBDAECATA3BgsrBgEEAYLfEwEBATAoMCYGCCsGAQUFBwIBFhpodHRwOi8vY3BzLmxldHNlbmNyeXB0Lm9yZzCCAQUGCisGAQQB1nkCBAIEgfYEgfMA8QB2ALIeBcyLos2KIE6HZvkruYolIGdr2vpw57JJUy3vi5BeAAABb8LzFagAAAQDAEcwRQIhAOahbBazK8ZbNoxS0G%2Fp3O1isv2uC2Hw1mdGecZX6ht%2BAiAa8pGGRBot6eOcxpKsERwsLfiV7yMh4mpjmqDRFFbh8AB3AG9Tdqwx8DEZ2JkApFEV%2F3cVHBHZAsEAKQaNsgiaN9kTAAABb8LzFgYAAAQDAEgwRgIhAP00xmaJSXTUACvcIiyLo0JBcdjFxA87vvJVkNCigV8EAiEAwyiAmV7u61b3KiKzUndQFbxHDVkNHOC%2B80i6CTaf11wwDQYJKoZIhvcNAQELBQADggEBAG8pLvzL7fX4Fjsy4SMlr1QNJh4XDf1Qk89ZOSs6BosDakC8AdhB1%2FP1jV7FFh%2FImJFC8FOqGpOtdNlaqX%2Bb5ehVnttWByl3VrMtXg2RluYGJTel0hoGutfwkP602jdp3NAJN%2BKApFSXEAK3viXevycBBtHjVxZ4aXrkXARJxOqRXFXvdSs3ouWg0JjjpBsO0NnKmL9GkxAAmmw2CYv1WJSRNKDQkwfwFaL6n6caZN6N4Eg%2FTBZDCPn2zFIz3vWNvJJQsjjg5VJtovK2MqGOnb1qGKqCXjDX4HHsNhyilQaqxFLs7KBl22Am%2Bo2%2BuVBsTZT5wjIWDfzHHxvqB%2BaEcUU%3D,MIIEkjCCA3qgAwIBAgIQCgFBQgAAAVOFc2oLheynCDANBgkqhkiG9w0BAQsFADA%2FMSQwIgYDVQQKExtEaWdpdGFsIFNpZ25hdHVyZSBUcnVzdCBDby4xFzAVBgNVBAMTDkRTVCBSb290IENBIFgzMB4XDTE2MDMxNzE2NDA0NloXDTIxMDMxNzE2NDA0NlowSjELMAkGA1UEBhMCVVMxFjAUBgNVBAoTDUxldCdzIEVuY3J5cHQxIzAhBgNVBAMTGkxldCdzIEVuY3J5cHQgQXV0aG9yaXR5IFgzMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnNMM8FrlLke3cl03g7NoYzDq1zUmGSXhvb418XCSL7e4S0EFq6meNQhY7LEqxGiHC6PjdeTm86dicbp5gWAf15Gan%2FPQeGdxyGkOlZHP%2FuaZ6WA8SMx%2Byk13EiSdRxta67nsHjcAHJyse6cF6s5K671B5TaYucv9bTyWaN8jKkKQDIZ0Z8h%2FpZq4UmEUEz9l6YKHy9v6Dlb2honzhT%2BXhq%2Bw3Brvaw2VFn3EK6BlspkENnWAa6xK8xuQSXgvopZPKiAlKQTGdMDQMc2PMTiVFrqoM7hD8bEfwzB%2FonkxEz0tNvjj%2FPIzark5McWvxI0NHWQWM6r6hCm21AvA2H3DkwIDAQABo4IBfTCCAXkwEgYDVR0TAQH%2FBAgwBgEB%2FwIBADAOBgNVHQ8BAf8EBAMCAYYwfwYIKwYBBQUHAQEEczBxMDIGCCsGAQUFBzABhiZodHRwOi8vaXNyZy50cnVzdGlkLm9jc3AuaWRlbnRydXN0LmNvbTA7BggrBgEFBQcwAoYvaHR0cDovL2FwcHMuaWRlbnRydXN0LmNvbS9yb290cy9kc3Ryb290Y2F4My5wN2MwHwYDVR0jBBgwFoAUxKexpHsscfrb4UuQdf%2FEFWCFiRAwVAYDVR0gBE0wSzAIBgZngQwBAgEwPwYLKwYBBAGC3xMBAQEwMDAuBggrBgEFBQcCARYiaHR0cDovL2Nwcy5yb290LXgxLmxldHNlbmNyeXB0Lm9yZzA8BgNVHR8ENTAzMDGgL6AthitodHRwOi8vY3JsLmlkZW50cnVzdC5jb20vRFNUUk9PVENBWDNDUkwuY3JsMB0GA1UdDgQWBBSoSmpjBH3duubRObemRWXv86jsoTANBgkqhkiG9w0BAQsFAAOCAQEA3TPXEfNjWDjdGBX7CVW%2Bdla5cEilaUcne8IkCJLxWh9KEik3JHRRHGJouM2VcGfl96S8TihRzZvoroed6ti6WqEBmtzw3Wodatg%2BVyOeph4EYpr%2F1wXKtx8%2FwApIvJSwtmVi4MFU5aMqrSDE6ea73Mj2tcMyo5jMd6jmeWUHK8so%2FjoWUoHOUgwuX4Po1QYz%2B3dszkDqMp4fklxBwXRsW10KXzPMTZ%2BsOPAveyxindmjkW8lGy%2BQsRlGPfZ%2BG6Z6h7mjem0Y%2BiWlkYcV4PIWL1iwBi8saCbGS5jN2p8M%2BX%2BQ7UNKEkROb3N6KOqkqm57TH2H3eDJAkSnh6%2FDNFu0Qg%3D%3D";

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

    public function testCreateVAFromCheckoutForOffline()
    {
        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $this->ba->privateAuth();

        $resp = $this->starttest();

        $terminalCreteData = [
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

    protected function setUpOfflinePayment()
    {
        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $this->ba->privateAuth();

        $terminalCreteData = [
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                  =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $terminal   = $this->fixtures->create(
            'terminal', $terminalCreteData);

        $data = $this->testData['testCreateVAFromCheckoutForOffline'];

        $resp = $this->starttest($data);

        $this->fixtures->merchant->enableOffline();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder($resp['id'], ['customer_id' => $this->customer['id'],'receivers' => ['offline_challan']]);
        $challan_number = $virtualAccount['receivers'][0]['challan_number'];

        $content = $this->createPricingPlan();

        $this->testData[__FUNCTION__]['request'] =  [
            'method'  => 'post',
            'content' => [
                'payment_method'      => 'offline',
            ],
        ];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'offline',
            ],
        ];

        $this->testData[__FUNCTION__]['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->ba->adminAuth();
        $resp = $this->startTest();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $resp['plan_id']]);

        return $challan_number;

    }


    public function testHdfcOfflinePaymentCredit()
    {
        $challan_number = $this->setUpOfflinePayment();

        $data = [
            'reference_number' => '123',
            'micr_code' => '456',
        ];

        $pdData = [
            'name' => 'paridhi',
        ];

        $content = [
            'challan_no' =>  $challan_number,
            'amount' => 1000,
            'mode' => 'cash',
            'status' => 'processed',
            'payment_date' => '28-jan-2022',
            'payment_time' => '21:30:45',
            'payment_instrument_details' => $data,
            'payer_details' => $pdData,
            'client_code'  =>  '12345678',
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $content,
        ];

        $offlineChallan = (new OfflineChallanRepo)->fetchByChallanNumber($challan_number);

        $offlineChallan->setStatus('validated');

        (new OfflineChallanRepo)->saveOrfail($offlineChallan);

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['HTTP_X-Forwarded-Tls-Client-Cert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challan_number,
                'status' => 0
            ],
        ];

        $this->startTest();
        $virtualAccountData = DB::select('select * from virtual_accounts')[0];

        $this->assertEquals('closed', $virtualAccountData->status);

        $offlinePayment = DB::select('select * from offline_payments')[0];

        $this->assertEquals('captured', $offlinePayment->status);

        $offlineChallan = DB::select('select * from offline_challans')[0];

        $this->assertEquals($virtualAccountData->id, $offlineChallan->virtual_account_id);

    }

    public function testHdfcOfflinePaymentCreditWithoutPaymentDetails()
    {
        $challan_number = $this->setUpOfflinePayment();

        $content = [
            'challan_no' =>  $challan_number,
            'amount' => 1000,
            'mode' => 'cash',
            'status' => 'processed',
            'payment_date' => '28-jan-2022',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $content,
        ];

        $offlineChallan = (new OfflineChallanRepo)->fetchByChallanNumber($challan_number);

        $offlineChallan->setStatus('validated');

        (new OfflineChallanRepo)->saveOrfail($offlineChallan);

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['HTTP_X-Forwarded-Tls-Client-Cert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challan_number,
                'status' => 0
            ],
        ];

        $this->startTest();
    }

    public function testHdfcOfflinePaymentCreditAmountValidationFail()
    {
        $challan_number = $this->setUpOfflinePayment();

        $data = [
            'reference_number' => '123',
            'micr_code' => '456',
        ];

        $pdData = [
            'name' => 'paridhi',
        ];

        $content = [
            'challan_no' =>  $challan_number,
            'amount' => 10,
            'mode' => 'hdd',
            'status' => 'processed',
            'payment_date' => '28-jan-2022',
            'payment_time' => '21:30:45',
            'payment_instrument_details' => $data,
            'payer_details' => $pdData,
            'client_code'  =>  '12345678',
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $content,
        ];

        $offlineChallan = (new OfflineChallanRepo)->fetchByChallanNumber($challan_number);

        $offlineChallan->setStatus('validated');

        (new OfflineChallanRepo)->saveOrfail($offlineChallan);

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['HTTP_X-Forwarded-Tls-Client-Cert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challan_number,
                'status' => 1,
                'error' => [
                    'code' => 'BAD_REQ_ER'
                ]
            ],
        ];

        $this->startTest();
    }

    public function testHdfcOfflinePaymentCreditChallanValidationFail()
    {
        $challan_number = $this->setUpOfflinePayment();

        $data = [
            'reference_number' => '123',
            'micr_code' => '456',
        ];


        $pdData = [
            'name' => 'paridhi',
        ];


        $content = [
            'challan_no' =>  $challan_number,
            'amount' => 1000,
            'mode' => 'hdd',
            'status' => 'processed',
            'payment_date' => '28-jan-2022',
            'payment_time' => '21:30:45',
            'payment_instrument_details' => $data,
            'payer_details' => $pdData,
            'client_code'  =>  '12345678',
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $content,
        ];

        $offlineChallan = (new OfflineChallanRepo)->fetchByChallanNumber($challan_number);

        (new OfflineChallanRepo)->saveOrfail($offlineChallan);

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['HTTP_X-Forwarded-Tls-Client-Cert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challan_number,
                'status' => 1,
                'error' => [
                    'code' => 'BAD_REQ_ER'
                ]
            ],
        ];

        $this->startTest();
    }

}
