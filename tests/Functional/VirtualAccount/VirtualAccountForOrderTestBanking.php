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

class VirtualAccountForOrderTestBanking  extends TestCase
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

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');

    }

    public function testCreateVAFromCheckoutForOfflineMethod()
    {
        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $this->ba->privateAuth();

        $resp = $this->starttest();

        $terminalCreteData = [
            "id"                       => 'Oc3KkqYe4LjpdA',
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

    public function testCreateVirtualAccountWithCloseByForOfflineChallan()
    {
        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $this->ba->privateAuth();

        $resp = $this->starttest();

        $terminalCreteData = [
            "id"                       => 'Oc3KkqYe4LjpdA',
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'     => '12345678',
            'gateway_secure_secret'   => '12345',
            'offline'                 =>  1,
            'merchant_id'             =>  '10000000000000',
        ];

        $this->fixtures->create('terminal', $terminalCreteData);

        $this->fixtures->merchant->enableOffline();

        // Generate close_by timestamp (at least 15 minutes ahead of current time)
        $closeBy = Carbon::now()->addMinutes(20)->getTimestamp();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder(
            $resp['id'],
            [
                'customer_id' => $this->customer['id'],
                'receivers'   => ['offline_challan'],
                'close_by'    => $closeBy,
            ]
        );

        $this->assertEquals('offline_challan', $virtualAccount['receivers'][0]['entity']);

        $offlineData = [
            'property_id'    => '12345',
            'property_value' => 'abc',
        ];

        $this->assertEquals($offlineData, $virtualAccount['order']['customer_additional_info']);

        $offlineData = DB::select('select * from offline_challans')[0];

        $this->assertEquals($offlineData->id, $virtualAccount['receivers'][0]['id']);

        $virtualAccountData = DB::select('select * from virtual_accounts')[0];

        $this->assertEquals($offlineData->id, $virtualAccountData->offline_challan_id);

        // Assert that close_by is correctly set
        $this->assertEquals($closeBy, $virtualAccountData->close_by);
    }
    protected function setUpOfflinePayment()
    {
        $this->fixtures->merchant->addFeatures(['offline_checkout']);

        $this->ba->privateAuth();

        $terminalCreteData = [
            'id'                       => 'Oc3KkqYe4LjpdA',
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                  =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $terminal   = $this->fixtures->create(
            'terminal', $terminalCreteData);

        $data = $this->testData['testCreateVAFromCheckoutForOfflineMethod'];

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

        $paymentData = [
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
            'content' => $paymentData,
        ];

        $offlineChallan = (new OfflineChallanRepo)->fetchByChallanNumber($challan_number);

        $offlineChallan->setStatus('validated');

        (new OfflineChallanRepo)->saveOrfail($offlineChallan);

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

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

        $payment = $this->getDbLastEntity('payment');

        // Verifying the mock response of smart routing request by terminal id

        $this->assertEquals('Oc3KkqYe4LjpdA', $payment['terminal_id']);

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

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challan_number,
                'status' => 0
            ],
        ];

        $this->startTest();

        $payment = $this->getDbLastEntity('payment');

        // Verifying the mock response of smart routing request by terminal id

        $this->assertEquals('Oc3KkqYe4LjpdA', $payment['terminal_id']);

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

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

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

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

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
