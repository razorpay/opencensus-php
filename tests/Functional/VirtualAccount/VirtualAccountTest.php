<?php

namespace RZP\Tests\Functional\VirtualAccount;

use Hash;
use Cache;
use Queue;
use Mockery;
use Carbon\Carbon;
use RZP\Models\Admin;
use RZP\Jobs\Context;
use RZP\Services\Mock;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Models\Settings;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer;
use RZP\Models\OfflinePayment;
use RZP\Models\Terminal\Type;
use RZP\Models\Payout\Metric;
use RZP\Models\VirtualAccount;
use RZP\Services\Mozart;
use RZP\Services\RazorXClient;
use RZP\Gateway\Mozart\Action;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Customer\Entity;
use RZP\Models\Payment\Gateway;
use RZP\Services\SmartRouting;
use RZP\Services\SplitzService;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Models\Settlement\Channel;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\VirtualAccount\Core;
use RZP\Models\Base\PublicCollection;
use RZP\Models\VirtualAccount\Status;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\ServerErrorException;
use RZP\Error\PublicErrorDescription;
use RZP\Models\VirtualAccount\Constant;
use RZP\Models\VirtualAccount\Provider;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Jobs\RblVirtualAccountForBanking;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Helpers\Heimdall;
use RZP\Models\Admin\Service as AdminService;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Gateway\Mozart\BTRblBanking\ErrorCode;
use RZP\Models\QrCode\Repository as QrCodeRepo;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\BankAccount\Constants as BankAccountConstants;
use RZP\Models\OfflineChallan\Repository as OfflineChallanRepo;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class VirtualAccountTest extends TestCase
{
    protected $t1;
    protected $t2;
    protected $fixtures;
    private $vpaTerminal;
    use PaymentTrait;
    use TestsMetrics;
    use TestsWebhookEvents;
    use VirtualAccountTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use HeimdallTrait;
    use PartnerTrait;
    Use MocksSplitz;

    const CERT_HEADER = "-----BEGIN%20CERTIFICATE-----%0AMIIGBzCCBO+gAwIBAgIQCOA8kQomwh9JXkKqhqWdsTANBgkqhkiG9w0BAQsFADBZMQswCQYDVQQGEwJVUzEVMBMGA1UEChMMRGlnaUNlcnQgSW5jMTMwMQYDVQQDEypSYXBpZFNTTCBUTFMgRFYgUlNBIE1peGVkIFNIQTI1NiAyMDIwIENBLTEwHhcNMjEwMTE1MDAwMDAwWhcNMjIwMjE1MjM1OTU5WjAaMRgwFgYDVQQDEw9jbDIubXRscy5yenAuaW8wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN5MerPFbUI2h/h+Kno8bzM8zQ4NIJQ4QC+kdb5fC8T2iJp9Dnt2OAAr/P9l33X99gXRQ2GQqmIQcDZYKtkeU7ebRrHP0/JFzKLzSLLGr0guvJFVn/GJM5QBRBewlo1VZZv0ZU72e08I4+1/TG1eeW2Rkiey4DPQ8cfoJNX3VhQdMwI/BS342cyubLmJ+wD9/jr1o28SsF85fX+IpXeu2xoCvdAgCbxQqnSpChQEjdLRbtNkqVil0YYDkPKz9XiQjrw9WJCE8LnwICOgyBVe6dyIkbMXDwuQhyM/dJIoTpMYvpRX1ZwIXWMj/0y2YRf/4KOuaxtyT2BqP5a8ONFbV9AgMBAAGjggMIMIIDBDAfBgNVHSMEGDAWgBSkjeW+fHnkcCNtLik0rSNY3PUxfzAdBgNVHQ4EFgQU315GdzlKFuEJUDhZjMtzbrEIILcwGgYDVR0RBBMwEYIPY2wyLm10bHMucnpwLmlvMA4GA1UdDwEB/wQEAwIFoDAdBgNVHSUEFjAUBggrBgEFBQcDAQYIKwYBBQUHAwIwgZsGA1UdHwSBkzCBkDBGoESgQoZAaHR0cDovL2NybDMuZGlnaWNlcnQuY29tL1JhcGlkU1NMVExTRFZSU0FNaXhlZFNIQTI1NjIwMjBDQS0xLmNybDBGoESgQoZAaHR0cDovL2NybDQuZGlnaWNlcnQuY29tL1JhcGlkU1NMVExTRFZSU0FNaXhlZFNIQTI1NjIwMjBDQS0xLmNybDA+BgNVHSAENzA1MDMGBmeBDAECATApMCcGCCsGAQUFBwIBFhtodHRwOi8vd3d3LmRpZ2ljZXJ0LmNvbS9DUFMwgYUGCCsGAQUFBwEBBHkwdzAkBggrBgEFBQcwAYYYaHR0cDovL29jc3AuZGlnaWNlcnQuY29tME8GCCsGAQUFBzAChkNodHRwOi8vY2FjZXJ0cy5kaWdpY2VydC5jb20vUmFwaWRTU0xUTFNEVlJTQU1peGVkU0hBMjU2MjAyMENBLTEuY3J0MAkGA1UdEwQCMAAwggEEBgorBgEEAdZ5AgQCBIH1BIHyAPAAdgApeb7wnjk5IfBWc59jpXflvld9nGAK+PlNXSZcJV3HhAAAAXcFMj65AAAEAwBHMEUCIQC/gLF6vk1SyZS1Eea8RVHUejaqLBks6CntjdQzxg0g7AIgFuZG7X9n44IE/6JWa0dKx8WCMGQqadqmhuTEd1yfxiYAdgAiRUUHWVUkVpY/oS/x922G4CMmY63AS39dxoNcbuIPAgAAAXcFMj8PAAAEAwBHMEUCIAt0koM6qrshwZ1+kKd+2melUpXYiYJf+kHtYTp2Wa4rAiEA3vbbAjddbscwtR0LSMhieLf31CZlv8zhx8Fce4rcnR8wDQYJKoZIhvcNAQELBQADggEBADDHOmTsFvb0BI2cJ8KxFhYXVeKIPSVDIbvJB7jAZ0AKohGn6kyTGPyvt4bGoAgpdh/kEbcI7YWgWbS8PG14AQev7TZOijuur13YENqNuKVLlCfzICx+r9RvOWKbNxIJeHPEOcJU2o0hliIk6nEGZJTupjkGw/hrq17F6Q0C+Gs1AFrsnApxOXskB/D+0n8j74jBNeVQsuerosTRqhG1S6rKecHhEH4Vxksfpr0MZHW409VSH4G7hEsytnoY53I/Y8SWmzBxwYcaxFBn2cafV/j3bvBPezl3KaCUuxu2VJq7mmgotimU0gFNwMWe++p3h2nlCp1+DNqnXlY+apJUazY=%0A-----END%20CERTIFICATE-----%0A-----BEGIN%20CERTIFICATE-----%0AMIIFUTCCBDmgAwIBAgIQB5g2A63jmQghnKAMJ7yKbDANBgkqhkiG9w0BAQsFADBhMQswCQYDVQQGEwJVUzEVMBMGA1UEChMMRGlnaUNlcnQgSW5jMRkwFwYDVQQLExB3d3cuZGlnaWNlcnQuY29tMSAwHgYDVQQDExdEaWdpQ2VydCBHbG9iYWwgUm9vdCBDQTAeFw0yMDA3MTYxMjI1MjdaFw0yMzA1MzEyMzU5NTlaMFkxCzAJBgNVBAYTAlVTMRUwEwYDVQQKEwxEaWdpQ2VydCBJbmMxMzAxBgNVBAMTKlJhcGlkU1NMIFRMUyBEViBSU0EgTWl4ZWQgU0hBMjU2IDIwMjAgQ0EtMTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBANpuQ1VVmXvZlaJmxGVYotAMFzoApohbJAeNpzN+49LbgkrMLv2tblII8H43vN7UFumxV7lJdPwLP22qa0sV9cwCr6QZoGEobda+4pufG0aSfHQCQhulaqKpPcYYOPjTwgqJA84AFYj8l/IeQ8n01VyCurMIHA478ts2G6GGtEx0ucnEfV2QHUL64EC2yh7ybboo5v8nFWV4lx/xcfxoxkFTVnAIRgHrH2vUdOiV9slOix3z5KPs2rK2bbach8Sh5GSkgp2HRoS/my0tCq1vjyLJeP0aNwPd3rk5O8LiffLev9j+UKZo0tt0VvTLkdGmSN4h1mVY6DnGfOwp1C5SK0MCAwEAAaOCAgswggIHMB0GA1UdDgQWBBSkjeW+fHnkcCNtLik0rSNY3PUxfzAfBgNVHSMEGDAWgBQD3lA1VtFMu2bwo+IbG8OXsj3RVTAOBgNVHQ8BAf8EBAMCAYYwHQYDVR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUFBwMCMBIGA1UdEwEB/wQIMAYBAf8CAQAwNAYIKwYBBQUHAQEEKDAmMCQGCCsGAQUFBzABhhhodHRwOi8vb2NzcC5kaWdpY2VydC5jb20wewYDVR0fBHQwcjA3oDWgM4YxaHR0cDovL2NybDMuZGlnaWNlcnQuY29tL0RpZ2lDZXJ0R2xvYmFsUm9vdENBLmNybDA3oDWgM4YxaHR0cDovL2NybDQuZGlnaWNlcnQuY29tL0RpZ2lDZXJ0R2xvYmFsUm9vdENBLmNybDCBzgYDVR0gBIHGMIHDMIHABgRVHSAAMIG3MCgGCCsGAQUFBwIBFhxodHRwczovL3d3dy5kaWdpY2VydC5jb20vQ1BTMIGKBggrBgEFBQcCAjB+DHxBbnkgdXNlIG9mIHRoaXMgQ2VydGlmaWNhdGUgY29uc3RpdHV0ZXMgYWNjZXB0YW5jZSBvZiB0aGUgUmVseWluZyBQYXJ0eSBBZ3JlZW1lbnQgbG9jYXRlZCBhdCBodHRwczovL3d3dy5kaWdpY2VydC5jb20vcnBhLXVhMA0GCSqGSIb3DQEBCwUAA4IBAQAi49xtSOuOygBycy50quCThG45xIdUAsQCaXFVRa9asPaB/jLINXJL3qV9J0Gh2bZM0k4yOMeAMZ57smP6JkcJihhOFlfQa18aljd+xNc6b+GX6oFcCHGr+gsEyPM8qvlKGxc5T5eHVzV6jpjpyzl6VEKpaxH6gdGVpQVgjkOR9yY9XAUlFnzlOCpqsm7r2ZUKpDfrhUnVzX2nSM15XSj48rVBBAnGJWkLPijlACd3sWFMVUiKRz1C5PZyel2l7J/W4d99KFLSYgoy5GDmARpwLc//fXfkr40nMY8ibCmxCsjXQTe0fJbtrrLLyWQlk9VDV296EI/kQOJNLVEkJ54P%0A-----END%20CERTIFICATE-----%0A";

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/VirtualAccountTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.smart_routing.mock', true);

        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->createMerchantWithDetails(Org::HDFC_ORG, '10000000000035', ['name' => 'Test Account']);

        $this->fixtures->create('terminal:hdfc_ecms_bank_account_dedicated_terminal');

        $this->fixtures->methods->createDefaultMethods(['merchant_id'    => '10000000000035']);

        $this->fixtures->merchant->enableMethod('10000000000035', 'bank_transfer');

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures('bharat_qr');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->t1 = $this->fixtures->create('terminal:bharat_qr_terminal');

        $this->t2 = $this->fixtures->create('terminal:bharat_qr_terminal_upi');

        $this->ba->privateAuth();

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal_alpha_num');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test');

        $this->vpaTerminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');
    }

    public function testCreateHdfcEcmsVirtualAccount()
    {
        $this->fixtures->create('feature', [
            'name' => Feature\Constants::SET_VA_DEFAULT_EXPIRY,
            'entity_id' => '6dLbNSpv5XbCOG',
            'entity_type' => 'org',
        ]);

        $key = $this->fixtures->create('key', ['merchant_id' => '10000000000035']);

        $order = $this->fixtures->create('order', ['merchant_id' => '10000000000035']);

        $this->ba->publicAuth($key->getPublicId());

        $response = $this->createVirtualAccountForOrder($order);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals($order->getAmountDue(), $virtualAccount['amount_expected']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);
        $this->assertEquals($order->getId(), $virtualAccount['entity_id']);
        $this->assertEquals('order', $virtualAccount['entity_type']);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals($virtualAccount['id'], 'va_' . $bankAccount['entity_id']);

        $closeBy = Carbon::now(Timezone::IST)->addMinutes(Constant::ECMS_CHALLAN_DEFAULT_EXPIRY_IN_MINUTES)->toDateString();

        $vaCloseByDate = Carbon::createFromTimestamp($virtualAccount['close_by'], Timezone::IST)->toDateString();

        $this->assertEquals($closeBy, $vaCloseByDate);

        // Test create ecms VA with merchant level VA expiry setting
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000035');

        $this->ba->proxyAuth('rzp_test_10000000000035', $merchantUser['id']);

        $this->runRequestResponseFlow($this->testData['testVirtualAccountExpirySetting']);

        $order = $this->fixtures->create('order', ['merchant_id' => '10000000000035']);

        $this->ba->publicAuth($key->getPublicId());

        $this->createVirtualAccountForOrder($order);

        $expiryOffset = $this->testData['testVirtualAccountExpirySetting']['request']['content']['va_expiry_offset'];

        $virtualAccount = $this->getLastEntity('virtual_account', true);

        $closeBy =  Carbon::now(Timezone::IST)->addMinutes($expiryOffset)->toDateString();

        $vaCloseByDate = Carbon::createFromTimestamp($virtualAccount['close_by'], Timezone::IST)->toDateString();

        $this->assertEquals($closeBy, $vaCloseByDate);
    }

    public function testVirtualAccountExpirySetting()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000035');

        $this->ba->proxyAuth('rzp_test_10000000000035', $merchantUser['id']);

        $this->startTest();
    }

    public function testVirtualAccountMerchantChallanExpirySetting()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000035');

        $this->ba->proxyAuth('rzp_test_10000000000035', $merchantUser['id']);

        $this->startTest();
    }

    public function testVirtualAccountExpirySettingFetch()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000035');

        $this->ba->proxyAuth('rzp_test_10000000000035', $merchantUser['id']);

        $request = $this->testData['testVirtualAccountExpirySetting']['request'];

        $this->makeRequestAndGetContent($request);

        $request = $this->testData['testVirtualAccountExpirySettingFetch']['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(24, $response["expiry"]);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(-1, $response["expiry"]);
    }

    public function testVirtualAccountExpirySettingForAdminDashboard()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testVirtualAccountExpirySettingForAdminDashboardNegative()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }


    public function testVirtualAccountExpirySettingForAdminDashboardNegative2()
    {

        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $this->orgId = $this->org->getId();

        $this->hostName = 'testing.testing.com';

        $this->orgHostName = $this->fixtures->create('org_hostname', [
            'org_id'        => $this->orgId,
            'hostname'      => $this->hostName,
        ]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testVirtualAccountMerchantChallanExpirySettingForAdminDashboard()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testVirtualAccountMerchantChallanExpirySettingForAdminDashboardNegative()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testVirtualAccountExpirySettingFetchForAdminDashboard()
    {
        $this->ba->adminAuth();

        $request = $this->testData['testVirtualAccountExpirySettingForAdminDashboard']['request'];

        $request['content']['va_expiry_offset'] = 12;

        $this->makeRequestAndGetContent($request);

        $this->startTest();
    }

    // createVirtualAccountForOrder
    public function testVirtualAccountExpiryFromConfig()
    {
        $this->ba->adminAuth();

        $request = $this->testData['testVirtualAccountExpirySettingForAdminDashboard']['request'];

        $request['content']['va_expiry_offset'] = 30;

        $order = $this->fixtures->create('order');

        $this->makeRequestAndGetContent($request);

        $request = $this->testData['testVirtualAccountExpirySettingFetchForAdminDashboard']['request'];

        $res = $this->makeRequestAndGetContent($request);

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::SET_VA_DEFAULT_EXPIRY,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $virtualAccount = $this->createVirtualAccountForOrder($order);

        $minuteDiff = ($virtualAccount["close_by"] - $currentTime) / 60;

        $this->assertTrue($minuteDiff < 31 and $minuteDiff > 29);

    }

    public function testVirtualAccountMerchantChallanExpirySettingFetchForAdminDashboard()
    {
        $this->ba->adminAuth();

        $request = $this->testData['testVirtualAccountExpirySettingForAdminDashboard']['request'];

        $request['content'][Constant::VA_EXPIRY_OFFSET_MERCHANT_CHALLAN] = 16;

        $this->makeRequestAndGetContent($request);

        $this->startTest();
    }
    /**
     * Asserts that the entity origin for a VA created through the partner auth is set to application.
     * Also asserts that the payment created for such a VA (irrespective of the receiver type) has the entity origin
     * set to the same application.
     */
    public function testCreateVirtualAccountPartnerAuth()
    {
        list($virtualAccount, $submerchantId, $client) = $this->createVirtualAccountPartnerAuth();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $virtualAccount);

        // Assert that the entity origin for the VA is set to application
        $this->verifyEntityOrigin($virtualAccount['id'], 'application', $client->getApplicationId());

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        // Assert that entity origin for the last payment created (through a bank transfer) is set to application.
        $payment = $this->getLastEntity('payment', true);
        $this->verifyEntityOrigin($payment['id'], 'application', $client->getApplicationId());
    }

    public function testCreateVirtualAccount()
    {
        $response = $this->createVirtualAccount();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');
    }

    public function testCreateVirtualAccountForCollectxWithReceiverBankAccount()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

        (new AdminService())->setConfigKeys([ConfigKey::COLLECTX_SERIES_PREFIX => [
            '10000000000000' => 'COLLECTX'
        ]]);

        $this->fixtures->create(
            'balance',
            [
                'type'             => 'banking',
                'merchant_id'      => '10000000000000',
                'balance'          => 0,
                'account_type'     => 'direct'
            ]);

        $terminalAttributes = [
            'gateway'               => Gateway::BT_AXIS,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => 'COLLECTX',
            'gateway_merchant_id2'  => 'TKES',
            'type'                  => [
                Type::NON_RECURRING       => '1',
                Type::NUMERIC_ACCOUNT     => '1',
            ]
        ];
        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        $response = $this->startTest();

        $expectedResponse = $this->testData[__FUNCTION__]['response']['content'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $accountNumber = $response['receivers'][0]['account_number'];

        $this->assertTrue(strpos($accountNumber, 'COLLECTX') == 0);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');
    }

    public function testCreateVirtualAccountForCollectxWithReceiverVpa()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

        $this->fixtures->create(
            'balance',
            [
                'type'             => 'banking',
                'merchant_id'      => '10000000000000',
                'balance'          => 0,
                'account_type'     => 'direct'
            ]);

        $terminalAttributes = [
            'id'                            => '10000000000001',
            'gateway'                       => Gateway::BT_AXIS,
            'merchant_id'                   => '10000000000000',
            'gateway_merchant_id'           => 'COLLECTX',
            'gateway_merchant_id2'          => 'TKES',
            'virtual_upi_root'              => 'test.',
            'virtual_upi_merchant_prefix'   => 'vpatest',
            'virtual_upi_handle'            => 'axis',
            'type'                          => [
                Type::NON_RECURRING       => '1',
                Type::NUMERIC_ACCOUNT     => '1',
            ]
        ];

        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        $this->fixtures->create('virtual_vpa_prefix', [
            'merchant_id'   => '10000000000000',
            'prefix'        => 'COLLECTX',
            'terminal_id'    => '10000000000001']);

        $response = $this->startTest();

        $expectedResponse = $this->testData[__FUNCTION__]['response']['content'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $vpaAddress = $response['receivers'][0]['address'];

        $vpaHandle = $response['receivers'][0]['handle'];

        $this->assertTrue(strpos($vpaAddress, 'test.collectx') == 0);

        $this->assertEquals('axis', $vpaHandle);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');
    }

    public function testCreateVirtualAccountForCollectx_RblMigratedMerchantVACreationBlock()
    {
        $input = [
            "id" => '10000000000000',
            'experiment_name' => RazorxTreatment::COLLECTX_RBL_MERCHANTS_VA_CREATION_BLOCK,
            'request_data' => json_encode(['id'=>'10000000000000'])
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

        $response = $this->startTest();

        $expectedResponse = $this->testData[__FUNCTION__]['response']['content'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $accountNumber = $response['receivers'][0]['account_number'];

        $this->assertTrue(strpos($accountNumber, 'COLLECTX') == 0);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');
    }

    public function testCreateVirtualAccountForBankingWithVPAReceiver()
    {
        $response = $this->createVirtualAccount(vpa: true);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');
    }

    public function testCreateVirtualAccountWithQrCodeReceiver() {

        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::SC_STOP_QR_AS_RECEIVER_FOR_VIRTUAL_ACCOUNT))
                {
                    return 'on';
                }
                return 'control';
            });

        $nextTenDays =  Carbon::today(Timezone::IST)->addDays(10)->timestamp;

        $virtualAccountFeature = $this->getDbLastEntity('feature');

        $this->fixtures->edit('feature', $virtualAccountFeature->getId(), ['created_at' => $nextTenDays]);

        $this->startTest();
    }

    public function testCreateVirtualAccountRBLJSW()
    {
        $terminalAttributes = [
            'gateway'               => Gateway::BT_RBL_JSW,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => 'VAJSW',
            'type'                  => [
                Type::NON_RECURRING             => '1',
                Type::NUMERIC_ACCOUNT     => '1',
            ]
        ];
        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        $this->createVirtualAccount([], true);

        $jswAccount = $this->getDbLastEntity('bank_account');
        $this->assertEquals('VAJSW', substr($jswAccount['account_number'], 0, 5));
        $this->assertEquals('RATN0000001', $jswAccount['ifsc_code']);
    }

    public function testCreateVirtualAccountRBL()
    {
        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::BT_RBL_CREATE_VIRTUAL_ACCOUNT))
                {
                    return 'on';
                }
                return 'control';
            });

        $terminalAttributes = [
            'gateway'               => Gateway::RBL,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => '2223',
            'type'                  => [
                Type::NON_RECURRING      => '1',
                Type::NUMERIC_ACCOUNT    => '1',
            ]
        ];

        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        $this->createVirtualAccount([], true);

        $virtualAccount = $this->getDbLastEntity('virtual_account');
        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertNotNull( $virtualAccount['bank_account_id']);
        $this->assertEquals($bankAccount['id'], $virtualAccount['bank_account_id']);

        $this->assertEquals('RATN0VAAPIS', $bankAccount['ifsc_code']);
        $this->assertEquals(BankAccountConstants::BANK_ACCOUNT_CREATE_SYNCED, $bankAccount['is_gateway_sync']);
    }

    public function testCreateVirtualAccountRBLWithWrongJsonFormat()
    {
        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $this->app['config']->set('rbl_create_virtual_account.error_code', 'ER001');

        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::BT_RBL_CREATE_VIRTUAL_ACCOUNT))
                {
                    return 'on';
                }
                return 'control';
            });

        $terminalAttributes = [
            'gateway'               => Gateway::RBL,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => '2223',
            'type'                  => [
                Type::NON_RECURRING      => '1',
                Type::NUMERIC_ACCOUNT    => '1',
            ]
        ];

        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        $this->createVirtualAccount([], true);

        $virtualAccount = $this->getDbLastEntity('virtual_account');
        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertNotNull( $virtualAccount['bank_account_id']);
        $this->assertEquals($bankAccount['id'], $virtualAccount['bank_account_id']);
        $this->assertEquals('RATN0VAAPIS', $bankAccount['ifsc_code']);
        $this->assertEquals(BankAccountConstants::BANK_ACCOUNT_NOT_SYNCED, $bankAccount['is_gateway_sync']);
    }

    public function testCreateVirtualAccountWithCustomAccountNumberLengthForMerchant()
    {
        $settingInput =  [
            [
                'key' => 'account_number_length',
                'value' => '17',
                'merchant_id' => '10000000000000',
            ]
        ];

        $this->addCustomAccountNumberSetting($settingInput);

        $response = $this->createVirtualAccount();

        $expectedResponse = $this->testData['testCreateVirtualAccount'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals(17, strlen($bankAccount['account_number']));
    }

    public function testGetVirtualAccountConfig()
    {
        $terminalAttributes = [
            'gateway'               => Gateway::RBL,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => '2223',
            'gateway_merchant_id2'  => '00',
            'type'                  => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
            ]
        ];

        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);
        $response = $this->getVirtualAccountConfig();

        $this->assertEquals('222300', $response['bank_account']['prefix']);
        $this->assertEquals(true, $response['bank_account']['isDescriptorEnabled']);
        $this->assertEquals(16, $response['bank_account']['accountNumberLength']);
        $this->assertEquals('rzr.payto00000', $response['vpa']['prefix']);
        $this->assertEquals('icici', $response['vpa']['handle']);
        $this->assertEquals(true, $response['vpa']['isDescriptorEnabled']);
    }

    public function testGetVirtualAccountConfigForMerchantAccountNumberLengthCustomization()
    {
        $terminalAttributes = [
            'gateway'               => Gateway::RBL,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => '2223',
            'gateway_merchant_id2'  => '00',
            'type'                  => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
            ]
        ];

        $settingInput =  [
            [
                'key' => 'account_number_length',
                'value' => '17',
                'merchant_id' => '10000000000000',
            ]
        ];

        $this->fixtures->merchant->activate();

        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        $this->addCustomAccountNumberSetting($settingInput);

        $response = $this->getVirtualAccountConfig();

        $this->assertEquals('222300', $response['bank_account']['prefix']);
        $this->assertEquals(true, $response['bank_account']['isDescriptorEnabled']);
        $this->assertEquals(17, $response['bank_account']['accountNumberLength']);
        $this->assertEquals('rzr.payto00000', $response['vpa']['prefix']);
        $this->assertEquals('icici', $response['vpa']['handle']);
        $this->assertEquals(true, $response['vpa']['isDescriptorEnabled']);
    }

    public function testCreateVirtualAccountForOrgMerchantFeatureFlag()
    {
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $response = $this->createVirtualAccount();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');
    }

    public function testCreateVirtualAccount401ForOrgMerchantFeatureFlag()
    {
        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::WHITE_LABELLED_VA,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $response = $this->createVirtualAccount();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchVirtualAccountForOrgMerchantFeatureFlag()
    {
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $response = $this->createVirtualAccount();

        $response = $this->fetchVirtualAccount($response['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchVirtualAccount401ForOrgMerchantFeatureFlag()
    {
        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::WHITE_LABELLED_VA,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $response = $this->fetchVirtualAccount('random_id_virt');

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchMultipleVirtualAccountForOrgMerchantFeatureFlag()
    {
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->createVirtualAccount(['name' => 'First VA']);
        $this->createVirtualAccount(['name' => 'Second VA']);

        $response = $this->fetchVirtualAccounts();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchMultipleVirtualAccount401ForOrgMerchantFeatureFlag()
    {
        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::WHITE_LABELLED_VA,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $response = $this->fetchVirtualAccounts();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchPaymentsForVirtualAccountForOrgMerchantFeatureFlag()
    {
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $virtualAccount = $this->createVirtualAccount();

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $response = $this->fetchVirtualAccountPayments($virtualAccount['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchPaymentsForVirtualAccount401ForOrgMerchantFeatureFlag()
    {
        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::WHITE_LABELLED_VA,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $response = $this->fetchVirtualAccountPayments('random_id_virt');

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testCreateVirtualAccountWithOrderIdFeatureEnabled()
    {
        $this->fixtures->merchant->addFeatures(['order_id_mandatory']);

        $response = $this->createVirtualAccount();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');

        $order = $this->getDbEntity('order',
                                          [
                                              'merchant_id' => '10000000000000',
                                          ], 'live');

        $this->assertNull($order);

        $order = $this->getDbEntity('order',
                                    [
                                        'merchant_id' => '10000000000000',
                                    ], 'test');

        $this->assertNull($order);
    }

    public function testCreateVirtualAccountWithCloseBy()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 1000;

        $this->testData[__FUNCTION__]['close_by'] = $closeTimeStamp;

        $input = ['close_by' => $closeTimeStamp];

        $response = $this->createVirtualAccount($input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');
    }

    public function testCreateVirtualAccountWithInvalidCloseBy()
    {
        $this->startTest();
    }

    public function testVaOfflineQRGeneration()
    {
        $this->fixtures->merchant->addFeatures(['offline_payments']);

        $response = $this->createOfflineQrVA();

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('receivers', $response);
    }

    protected function createOfflineQrVA()
    {
        $request = $this->testData[__FUNCTION__];

        return $this->makeRequestAndGetContent($request);
    }

    public function testVaOfflineBharatQrPaymentProcess()
    {
        $this->markTestSkipped();

        $this->fixtures->merchant->addFeatures(['offline_payments']);

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->gateway = 'hitachi';

        $va = $this->createOfflineQrVA();

        $this->ba->directAuth();

        $qrCodeId = substr($va['receivers'][0]['id'], 3);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId,  null, ['F004' => '000000000100']);

        // This method tests if the request that contains plain text as input is getting handled properly
        $request = [
            'url'       => '/payment/callback/bharatqr/hitachi',
            'raw'       => http_build_query($content),
            'method'    => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = (array) simplexml_load_string(trim($xmlResponse));

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('hitachi', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('Random Name', $card['name']);
    }

    public function testGetVaPayments()
    {
        $this->createVirtualAccount();

        $va = $this->getDbLastEntity('virtual_account');

        $this->payVirtualAccount('va_'.$va['id'], ['amount' => 50]);

        $dbPayment = $this->getDbLastPayment();

        $this->ba->proxyAuth();
        $request = [
            'url' => '/virtual_accounts/va_'.$va->getId().'/payments',
            'method' => 'get'
        ];

        $res = $this->makeRequestAndGetContent($request);

        $this->assertEquals('pay_'.$dbPayment->getId(), $res['items'][0]['id']);
    }

    public function testPayVirtualAccountWithPastCloseBy()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 1000;

        $this->testData[__FUNCTION__]['close_by'] = $closeTimeStamp;

        $input = ['close_by' => $closeTimeStamp];

        $virtualAccount = $this->createVirtualAccount($input);

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $virtualAccount = $this->getLastEntity('virtual_account', true);

        $this->assertEquals(5000, $virtualAccount['amount_paid']);

        $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $this->fixtures->edit(
            "virtual_account",
            $virtualAccount['id'],
            ['close_by' => $closeTimeStamp - 2000]
        );

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $virtualAccount = $this->getLastEntity('virtual_account', true);

        $this->assertEquals(10000, $virtualAccount['amount_paid']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['virtual_account_id'], $virtualAccount['id']);

        $this->assertEquals(false, $bankTransfer['expected']);

        $this->assertEquals('VIRTUAL_ACCOUNT_DUE_TO_BE_CLOSED', $bankTransfer['unexpected_reason']);
    }

    public function testVirtualAccountClosedAt()
    {
        $virtualAccount = $this->createVirtualAccount();

        $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);

        $this->closeVirtualAccount($virtualAccount['id']);

        $virtualAccount = $this->getDbLastEntity('virtual_account');

        $this->assertNotNull($virtualAccount->getClosedAt());

        $this->assertEquals(Status::CLOSED, $virtualAccount->getStatus());
    }

    public function testCloseVAPostMigration()
    {
        $terminalAttributes = [
            'gateway'               => Gateway::BT_YESBANK,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => '2223',
            'type'                  => [
                Type::NON_RECURRING     => '1',
                Type::NUMERIC_ACCOUNT   => '1',
            ]
        ];

        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);
        $virtualAccount = $this->createVirtualAccount([], true);

        $bankAccount1 =  $this->getDbLastEntity('bank_account');

        $bankAccount2 = $this->fixtures->create(
            'bank_account',
            [
                'merchant_id'       => '10000000000000',
                'entity_id'         =>  substr($virtualAccount['id'], 3, strlen($virtualAccount['id'])),
                'type'              => 'virtual_account',
                'account_number'    =>  $bankAccount1->getAccountNumber(),
                'ifsc_code'         => 'RATN0VAAPIS',
            ]
        );

        $this->fixtures->edit(
            'virtual_account',
            substr($virtualAccount['id'], 3, strlen($virtualAccount['id'])),
            ['bank_account_id_2' => $bankAccount2->getId()]
        );

        $this->closeVirtualAccount($virtualAccount['id']);
        $virtualAccount = $this->getDbLastEntity('virtual_account');

        $this->assertEquals(Status::CLOSED, $virtualAccount['status']);

        $bankAccount1 = $this->getTrashedDbEntityById('bank_account', $bankAccount1->getId());
        $bankAccount2 = $this->getTrashedDbEntityById('bank_account', $bankAccount2->getId());

        $this->assertNotNull($bankAccount1['deleted_at']);
        $this->assertNotNull($bankAccount2['deleted_at']);

        $this->assertEquals(Provider::IFSC[Provider::YESBANK], $bankAccount1->getIfscCode());
        $this->assertEquals(Provider::IFSC[Provider::RBL], $bankAccount2->getIfscCode());
    }

    public function testVirtualAccountCloseByCron()
    {
        $closeTimeStamp = Carbon::now()->timestamp - 1000;

        $virtualAccount1 = $this->createVirtualAccount();

        $virtualAccount2 = $this->createVirtualAccount();

        $virtualAccount3 = $this->createVirtualAccount();

        $this->fixtures->edit(
            "virtual_account",
            $virtualAccount2['id'],
            ['close_by' => $closeTimeStamp, 'status' => 'paid']
        );

        $this->fixtures->edit(
            "virtual_account",
            $virtualAccount3['id'],
            ['close_by' => $closeTimeStamp]
        );

        $response = $this->closeVirtualAccountsByCloseBy();

        $this->assertEquals($response['success'] , 1);

        $this->assertEquals($response['failure'] , 0);

        $virtualAccount1 = $this->getDbEntityById('virtual_account', $virtualAccount1['id']);

        $this->assertEquals($virtualAccount1->getStatus(), 'active');

        $virtualAccount2 = $this->getDbEntityById('virtual_account' , $virtualAccount2['id']);

        $this->assertEquals($virtualAccount2->getStatus(), 'paid');

        $virtualAccount3 = $this->getDbEntityById('virtual_account' , $virtualAccount3['id']);

        $this->assertEquals($virtualAccount3->getStatus(), 'closed');
    }

    private function verifyEntityOrigin($entityId, $originType, $originId)
    {
        // Entity origin for merchant auth has been removed and is not getting stored
        return;

        $this->fixtures->stripSign($entityId);

        $entityOrigin = $this->getDbEntity('entity_origin', ['entity_id' => $entityId]);

        $this->assertEquals($originType, $entityOrigin['origin_type']);

        $this->assertEquals($originId, $entityOrigin['origin_id']);
    }

    public function testEditBulkVirtualAccounts()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 5000;

        $this->fixtures->merchant->addFeatures(['va_edit_bulk']);

        $dt = Carbon::createFromTimestamp($closeTimeStamp, Timezone::IST)
                ->format('d-m-Y H:i');

        $dt2 = Carbon::createFromTimestamp($closeTimeStamp, Timezone::IST)
                ->format('d-m-Y H:i:s');

        $virtualAccount1 = $this->createVirtualAccount();
        $virtualAccount2 = $this->createVirtualAccount();

        $this->ba->batchAuth();

        $testData = $this->testData[__FUNCTION__];

        $va_id = $virtualAccount1['id'];
        $va_id2 = $virtualAccount2['id'];

        $testData['request']['content'][0]['virtual_account_id'] = $va_id;
        $testData['request']['content'][0]['close_by'] = $dt;
        $testData['request']['content'][2]['virtual_account_id'] = $va_id2;
        $testData['request']['content'][2]['close_by'] = $dt2;

        $this->startTest($testData);

    }

    public function testEditBulkVirtualAccountsWithoutFeature()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 5000;

        $dt = Carbon::createFromTimestamp($closeTimeStamp, Timezone::IST)
                ->format('d-m-Y H:i');


        $virtualAccount1 = $this->createVirtualAccount();

        $this->ba->batchAuth();

        $testData = $this->testData[__FUNCTION__];

        $va_id = $virtualAccount1['id'];

        $testData['request']['content'][0]['virtual_account_id'] = $va_id;
        $testData['request']['content'][0]['close_by'] = $dt;

        $this->startTest($testData);
    }

    public function testCreateVirtualAccountForOrder()
    {
        $order = $this->fixtures->create('order');

        $closeBy = Carbon::now(Timezone::IST)->addSeconds(VirtualAccount\Validator::DEFAULT_CLOSE_BY_DIFF+100)->getTimestamp();

        $response = $this->createVirtualAccountForOrder($order, ['close_by' => $closeBy]);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals($order->getAmountDue(), $virtualAccount['amount_expected']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);
        $this->assertEquals($order->getId(), $virtualAccount['entity_id']);
        $this->assertEquals('order', $virtualAccount['entity_type']);
        $this->assertEquals($closeBy, $virtualAccount['close_by']);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals($virtualAccount['id'], 'va_' . $bankAccount['entity_id']);

        $originalVirtualAccountId = $virtualAccount['id'];

        // Another request does not create a new VA
        $this->createVirtualAccountForOrder($order);
        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals($originalVirtualAccountId, $virtualAccount['id']);

        $lastBankAccount = $this->getLastEntity('bank_account', true);

        $this->closeVirtualAccount($virtualAccount['id']);

        $updatedLastBankAccount = $this->getLastEntity('bank_account', true);

        // Because Bank Account is deleted when VA is closed
        $this->assertNotEquals($lastBankAccount['id'], $updatedLastBankAccount['id']);

        // If the old VA is closed, then another request would create a new one
        $this->createVirtualAccountForOrder($order);
        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertNotEquals($originalVirtualAccountId, $virtualAccount['id']);
    }

    public function testFetchOrderWithVirtualAccountExpand()
    {
        $order = $this->fixtures->create('order');

        $this->createVirtualAccountForOrder($order);

        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals($order->getId(), $virtualAccount['entity_id']);
        $this->assertEquals('order', $virtualAccount['entity_type']);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals($virtualAccount['id'], 'va_' . $bankAccount['entity_id']);

        $this->testData[__FUNCTION__]['request']['url'] = '/orders/order_' . $order['id'];

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals($response['id'], 'order_' . $order['id']);
        $this->assertEquals($response['virtual_account']['id'], $virtualAccount['id']);
        $this->assertEquals($response['virtual_account']['receivers'][0]['id'], $bankAccount['id']);
    }

    public function testFetchOrderWithoutVirtualAccountExpand()
    {
        $order = $this->fixtures->create('order');
        $this->testData[__FUNCTION__]['request']['url'] = '/orders/order_' . $order['id'];

        $this->startTest();
    }

    public function testFetchOrderWithVirtualAccountNoExpand()
    {
        $order = $this->fixtures->create('order');

        $this->createVirtualAccountForOrder($order);

        $this->testData[__FUNCTION__]['request']['url'] = '/orders/order_' . $order['id'];

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateVirtualAccountForOrderCustomerFeeBearer()
    {
        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        foreach ([FeeBearer::CUSTOMER, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {
            $order = $this->fixtures->create('order');

            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $response = $this->createVirtualAccountForOrder($order);

            $expectedResponse = $this->testData[__FUNCTION__];

            $this->assertArraySelectiveEquals($expectedResponse, $response);

            $virtualAccount = $this->getLastEntity('virtual_account', true);
            $this->assertEquals($order->getAmount(), $virtualAccount['amount_expected']);
            $this->assertEquals($order->getId(), $virtualAccount['entity_id']);
        }

    }

    public function testCreateVirtualAccountForOrderWithCloseBy()
    {
        $order = $this->fixtures->create('order');

        $closeTimeStamp = Carbon::now()->timestamp + 1000;

        $response = $this->createVirtualAccountForOrder($order, ['close_by' => $closeTimeStamp]);

        $virtualAccount = $this->getLastEntity('virtual_account', true);

        $this->assertEquals($response['close_by'], $virtualAccount['close_by']);
    }

    public function testCreateVirtualAccountInvalidReceiverTypes()
    {
        $this->startTest();
    }

    public function testCreateVirtualAccountWithBankAccountNameOption()
    {
        $this->startTest();
    }

    public function testCreateVirtualAccountValidationFailure()
    {
        // This is to check that validation rules on receiver attribute should stop after the first validation failure.
        // In this specific case custom validation will not run. Validation will bail after array validation failure.
        // If custom validation was still running then 2nd argument passed to it would have been invalid.
        $this->startTest();
    }

    public function testCreateVirtualAccountCrypto()
    {
        // Currently BharatQR generation is also blocked in the same flow.
        $this->fixtures->merchant->edit('10000000000000', ['category2' => 'cryptocurrency']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->createVirtualAccount();
        });
    }

    public function testCreateVirtualAccountWithBharatQr()
    {
        $this->markTestSkipped();

        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
        ]);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $qrCode = $this->getLastEntity('qr_code', true);

        $qrCodeEntity = (new QrCodeRepo())->findOrFailPublic(substr($qrCode['id'], 3));

        $this->assertEquals($qrCode['id'], 'qr_' . $qrCode['reference']);

        $this->assertMatchesRegularExpression('^http://dwarf.razorpay.in/^', $qrCode['short_url']);

        $tlvArray = $this->getTagMappedValues($qrCodeEntity->getQrString());

        $masterCardValue = $tlvArray['04'];

        $visaValue = $tlvArray['02'];

        $this->assertEquals(15, strlen($masterCardValue));

        $this->assertEquals(16, strlen($visaValue));

        $masterCardAcquirerCode = substr($masterCardValue, 0, 6);

        $visaAcquirerCode = substr($visaValue, 0, 6);

        $this->assertEquals('528734', $visaAcquirerCode);

        $this->assertEquals('428734', $masterCardAcquirerCode);

        $this->assertEquals(1, $qrCodeEntity['mpans_tokenized']);

        $qrStringWithTokenizedMpans = (new \RZP\Models\QrCode\Entity())::getQrStringWithTokenizedMpans($qrCodeEntity->getQrString());

        // asserting that actual string stored in db have tokenized mpans
        $this->assertEquals($qrStringWithTokenizedMpans, $qrCodeEntity['qr_string']);
    }

    public function testCreateVirtualAccountWithReference()
    {
        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_10000000000000', $user->getId());

        $this->fixtures->merchant->activate();

        $input = [
            'receivers'  => [
                'types' => ['qr_code'],
                'qr_code'       => [
                    'reference' => 'abc'
                ]
            ]
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => $input,
        ];

        $this->expectException(\Rzp\Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('reference is/are not required and should not be sent');

        $this->makeRequestAndGetContent($request);
    }

    public function testCreateVirtualAccountWithBharatQrWithNoTerminal()
    {
        $this->fixtures->terminal->disableTerminal($this->t1['id']);

        $this->fixtures->terminal->disableTerminal($this->t2['id']);

        $this->expectException(\RZP\Exception\LogicException::class);
        $this->expectExceptionMessage('No identifiers found for the merchant');

        $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
        ]);
    }

    public function testCreateVirtualAccountWithBharatQrWithNoMethodsEnabled()
    {
        $this->fixtures->merchant->disableMethod('10000000000000', 'credit_card');
        $this->fixtures->merchant->disableMethod('10000000000000', 'debit_card');
        $this->fixtures->merchant->disableMethod('10000000000000', 'prepaid_card');
        $this->fixtures->merchant->disableMethod('10000000000000', 'upi');

        $this->expectException(\RZP\Exception\LogicException::class);
        $this->expectExceptionMessage('No identifiers found for the merchant');

        $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
        ]);
    }

    public function testCreateVirtualAccountWithBharatQrWithUpiDisabled()
    {
        $this->fixtures->merchant->disableMethod('10000000000000', 'upi');

        $this->expectException(\RZP\Exception\LogicException::class);

        $this->expectExceptionMessage('No identifiers found for the merchant');

        $this->createVirtualAccount(['receiver_types'  => 'qr_code']);
    }

    public function testCreateVirtualAccountWithBharatQrWithOneTerminal()
    {
        $this->fixtures->terminal->disableTerminal($this->t1['id']);

        $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
        ]);

        $qrCode = $this->getLastEntity('qr_code', true);

        $tlvArray = $this->getTagMappedValues($qrCode['qr_string']);

        $this->assertArrayNotHasKey('02', $tlvArray);

        $this->assertArrayNotHasKey('04', $tlvArray);

        $this->assertArrayNotHasKey('06', $tlvArray);
    }

    public function testTransactionRollback()
    {
        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
        ]);

        $card = $this->getLastEntity('card', true);

        $this->assertNotEquals('222100', $card['iin']);
        $this->assertNotEquals('423156', $card['iin']);
        //$this->assertNotEquals('508500', $card['iin']);
    }

    public function testCreateVirtualAccountWithBharatQrAndEmptyMpan()
    {
        $this->fixtures->terminal->edit($this->t1['id'], ['mc_mpan' => null]);

        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
        ]);

        $qrCode = $this->getLastEntity('qr_code', true);

        $qrString = $qrCode['qr_string'];

        $tlvArray = $this->getTagMappedValues($qrString);

        $this->assertArrayNotHasKey('04', $tlvArray);
    }

    public function testCreateVirtualAccountWithBharatQrWithAmount()
    {
        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
            'amount_expected' => 10000,
        ]);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $qrCode = $this->getLastEntity('qr_code', true);

        $qrString = $qrCode['qr_string'];

        $tlvArray = $this->getTagMappedValues($qrString);

        $this->assertEquals($tlvArray['54'], '100.00');
    }

    public function testDownloadQrCode()
    {
        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
            'amount_expected' => 10000,
        ]);

        $qrCodeId = $response['receivers'][0]['id'];

        $request = [
            'method'  => 'GET',
            'url'     => '/t/qrcode/' . $qrCodeId,
        ];

        $this->ba->directAuth();

        $response = $this->sendRequest($request);

        $this->assertContentTypeForResponse('image/png', $response);
    }

    public function testDownloadQrInLiveMode()
    {
        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
            'amount_expected' => 10000,
        ]);

        $qrCodeId = $response['receivers'][0]['id'];

        $request = [
            'method'  => 'GET',
            'url'     => '/l/qrcode/' . $qrCodeId,
        ];

        $this->ba->directAuth();

        $this->expectException(BadRequestException::class);

        $this->sendRequest($request);
    }

    /**
     * testDownloadQrInLiveModeRegenerationLogicEdgeCase tests the following scenario-
     * 0. The QR was successfully created in the 1st place.
     * 1. Someone tries to download the QR in live mode.
     * 2. The first call to UFH to fetch the file fails with a 5xx. We expect an exception to be raised.
     * 3. We proceed with regenerating and re-uploading the QR code.
     * 4. The QR code should be now successfully returned.
     *
     * This test case has been return to check for an edge case where merchant context was not getting properly
     * populated in the above-mentioned scenario.
     * Refer- https://razorpay.slack.com/archives/C03RY88T214/p1684256312554559?thread_ts=1684255797.766869&cid=C03RY88T214
    */
    public function testDownloadQrInLiveModeRegenerationLogicEdgeCase()
    {
        // Setup ------------------------------
        $this->fixtures->merchant->activate();

        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
            'amount_expected' => 10000,
        ], mode: 'live');

        $qrCodeId = $response['receivers'][0]['id'];

        $request = [
            'method' => 'GET',
            'url'    => '/l/qrcode/' . $qrCodeId,
        ];

        $this->ba->directAuth();
        // Setup ends --------------------------

        // Set UFH mocks
        $ufhServiceMock = Mockery::mock(Mock\UfhService::class, [$this->app])->makePartial();

        // To be thrown to replicate that UFH is down
        $exception = new ServerErrorException('Unavailable', 'SERVER_ERROR');

        // We only want the above exception to be thrown once.
        // This shall happen when we try to fetch the file from UFH for the first time in the QR download flow.
        // Hence, maintaining a count
        $count = 0;

        // If $exception has never been thrown, throw it and increment count
        // If it has been thrown once, return the mock data
        $ufhServiceMock->shouldReceive('fetchFiles')
            ->andReturnUsing(function() use (&$count, $exception, $qrCodeId) {
                if ($count == 0) {
                    ++$count;
                    throw $exception;
                }
                else {
                    ++$count;
                    return [
                        'entity'  => 'collection',
                        'count'   => 1,
                        'items'   => [
                            [
                                'id'            => 'file_10RandomFileId',
                                'type'          => $type ?? 'explanation_letter',
                                'entity_type'   => 'qr_code',
                                'entity_id'     => $qrCodeId,
                                'name'          => 'QrCode.jpg',
                                'location'      => 'random/qrcode/location',
                                'bucket'        => 'test_bucket',
                                'mime'          => 'image/jpeg',
                                'extension'     => 'jpg',
                                'merchant_id'   => '10000000000000',
                                'store'         => 's3',
                            ],
                        ],
                    ];
                }
            });

        $this->app->instance('ufh.service', $ufhServiceMock);

        $response = $this->sendRequest($request);

        // Assert that a QR image was successfully received in the response.
        $this->assertContentTypeForResponse('image/png', $response);
    }

    public function testDownloadQrInTestMode()
    {
        $this->fixtures->merchant->activate();

        $attributes =  [
            'name'            => 'Test virtual account',
            'description'     => 'VA for tests',
            'amount_expected' => 10000,
            'receivers'       => [
                'types' => [
                    'qr_code',
                ],
            ],
            'notes'           => [
                'a' => 'b',
            ],
        ];

        // Question for Reviewer: Why do we use live mode here for test mode case?
        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => $attributes,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $qrCodeId = $response['receivers'][0]['id'];

        $request = [
            'method'  => 'GET',
            'url'     => '/t/qrcode/' . $qrCodeId,
        ];

        $this->ba->directAuth();

        $this->expectException(BadRequestException::class);

        $this->sendRequest($request);
    }

    /**
     * testDownloadQrInTestModeRegenerationLogicEdgeCase tests the following scenario-
     * 0. The QR was successfully created in the 1st place.
     * 1. Someone tries to download the QR in live mode.
     * 2. The first call to UFH to fetch the file fails with a 5xx. We expect an exception to be raised.
     * 3. We proceed with regenerating and re-uploading the QR code.
     * 4. The QR code should be now successfully returned.
     *
     * This test case has been return to check for an edge case where merchant context was not getting properly
     * populated in the above-mentioned scenario.
     * Refer- https://razorpay.slack.com/archives/C03RY88T214/p1684256312554559?thread_ts=1684255797.766869&cid=C03RY88T214
     */
    public function testDownloadQrInTestModeRegenerationLogicEdgeCase()
    {
        // Setup ------------------------------
        $this->fixtures->merchant->activate();

        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
            'amount_expected' => 10000,
        ]);

        $qrCodeId = $response['receivers'][0]['id'];

        $request = [
            'method' => 'GET',
            'url'    => '/t/qrcode/' . $qrCodeId,
        ];

        $this->ba->directAuth();
        // Setup ends --------------------------

        // Set UFH mocks
        $ufhServiceMock = Mockery::mock(Mock\UfhService::class, [$this->app])->makePartial();

        // To be thrown to replicate that UFH is down
        $exception = new ServerErrorException('Unavailable', 'SERVER_ERROR');

        // We only want the above exception to be thrown once.
        // This shall happen when we try to fetch the file from UFH for the first time in the QR download flow.
        // Hence, maintaining a count
        $count = 0;

        // If $exception has never been thrown, throw it and increment count
        // If it has been thrown once, return the mock data
        $ufhServiceMock->shouldReceive('fetchFiles')
                       ->andReturnUsing(function() use (&$count, $exception, $qrCodeId) {
                           if ($count == 0) {
                               ++$count;
                               throw $exception;
                           }
                           else {
                               ++$count;
                               return [
                                   'entity'  => 'collection',
                                   'count'   => 1,
                                   'items'   => [
                                       [
                                           'id'            => 'file_10RandomFileId',
                                           'type'          => $type ?? 'explanation_letter',
                                           'entity_type'   => 'qr_code',
                                           'entity_id'     => $qrCodeId,
                                           'name'          => 'QrCode.jpg',
                                           'location'      => 'random/qrcode/location',
                                           'bucket'        => 'test_bucket',
                                           'mime'          => 'image/jpeg',
                                           'extension'     => 'jpg',
                                           'merchant_id'   => '10000000000000',
                                           'store'         => 's3',
                                       ],
                                   ],
                               ];
                           }
                       });

        $this->app->instance('ufh.service', $ufhServiceMock);

        $response = $this->sendRequest($request);

        // Assert that a QR image was successfully received in the response.
        $this->assertContentTypeForResponse('image/png', $response);
    }

    public function testCreateVirtualAccountWithDescriptor()
    {
        $this->createVirtualAccount();

        $vba = $this->getLastEntity('bank_account', true);
        // Root and handle from numeric shared terminal will be used.
        $this->assertMatchesRegularExpression('/11122200[0-9]{8}$/', $vba['account_number']);

        $this->createVirtualAccount([], false);

        $vba = $this->getLastEntity('bank_account', true);
        // Root and handle from alpha numeric shared terminal will be used.
        $this->assertStringStartsWith('RZRPAY', $vba['account_number']);

        $terminalAttributes = [
            'gateway'               => Gateway::BT_DASHBOARD,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => 'ROHI',
            'gateway_merchant_id2'  => 'TKES',
            'type'                  => [
                Type::NON_RECURRING             => '1',
                Type::ALPHA_NUMERIC_ACCOUNT     => '1',
            ]
        ];
        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        $this->createVirtualAccount([], false, 'hwani123');

        $vba = $this->getLastEntity('bank_account', true);
        // Terminal is associated so root from there and given descriptor will be used.
        $this->assertEquals('ROHITKESHWANI123', $vba['account_number']);

        $this->createVirtualAccount([], true);

        $vba = $this->getLastEntity('bank_account', true);
        // Alpha Numeric terminal is associated, but numeric accounts can still be created using shared terminal
        $this->assertMatchesRegularExpression('/11122200[0-9]{8}$/', $vba['account_number']);
    }

    public function testCreateVirtualAccountOldFormat()
    {
        // With shared terminal
        $response = $this->createVirtualAccountOldFormat();

        $vba = $this->getLastEntity('bank_account', true);
        // No Terminal is associated so default shared terminal root is used with random descriptor
        $this->assertStringStartsWith('11122200', $vba['account_number']);

        // With shared terminal
        $terminalAttributes = [
            'gateway'               => Gateway::BT_DASHBOARD,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => '222333',
            'gateway_merchant_id2'  => '01',
            'type'                  => [
                Type::NON_RECURRING       => '1',
                Type::NUMERIC_ACCOUNT     => '1',
            ]
        ];
        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        // Terminal is associated so this terminal's root is used with random descriptor
        $response = $this->createVirtualAccountOldFormat();

        $vba = $this->getLastEntity('bank_account', true);
        $this->assertStringStartsWith('22233301', $vba['account_number']);

        // Note: Custom descriptor is not supported anymore in old format and only Numeric bank accounts can be created.
    }

    public function testVirtualAccountCreateRequestUpdate()
    {
        $data = $this->testData[__FUNCTION__];

        // New format
        // receivers[types][]=bank_account
        $this->createVirtualAccount();
        $vba = $this->getLastEntity('bank_account', true);
        $this->assertStringStartsWith('11122200', $vba['account_number']);

        // Sending descriptor throws error, can only be used with direct terminal.
        $this->runRequestResponseFlow($data['descriptorWithNumeric'], function() {
            $this->createVirtualAccount([], true, '12345678');
        });
    }

    public function testCreateVirtualAccountDescriptorInvalidLength()
    {
        $terminalAttributes = [
            'gateway'               => Gateway::BT_DASHBOARD,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => 'RZRP',
            'gateway_merchant_id2'  => 'hand',
            'type'                  => [
                Type::NON_RECURRING             => '1',
                Type::ALPHA_NUMERIC_ACCOUNT     => '1',
            ]
        ];
        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        // passed descriptor length is wrong as root + handle + descriptor should be 16
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $response = $this->createVirtualAccount([], true, '0123456789');
        });
    }

    public function testCreateVirtualAccountWithIdenticalDescriptor()
    {
        $terminalAttributes = [
            'gateway'               => Gateway::BT_DASHBOARD,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => 'RZRP',
            'gateway_merchant_id2'  => 'hand',
            'type'                  => [
                Type::NON_RECURRING             => '1',
                Type::ALPHA_NUMERIC_ACCOUNT     => '1',
            ]
        ];
        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        $this->createVirtualAccount([],false, 'samedesc');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->createVirtualAccount([],false, 'samedesc');
        });
    }

    public function testCreateVirtualAccountWithIdenticalDescriptorAfterClosing()
    {
        $terminalAttributes = [
            'gateway'               => Gateway::BT_DASHBOARD,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => 'RZRP',
            'gateway_merchant_id2'  => 'hand',
            'type'                  => [
                Type::NON_RECURRING             => '1',
                Type::ALPHA_NUMERIC_ACCOUNT     => '1',
            ]
        ];
        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);

        $virtualAccount =  $this->createVirtualAccount([],false, 'samedesc');

        $this->closeVirtualAccount($virtualAccount['id']);

        $data = $this->testData['testCreateVirtualAccountWithIdenticalDescriptor'];

        $this->runRequestResponseFlow($data, function() {
            $this->createVirtualAccount([],false, 'samedesc');
        });
    }

    public function testFetchVirtualAccount()
    {
        $response = $this->createVirtualAccount();

        $response = $this->fetchVirtualAccount($response['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchVirtualAccountByCustomerEmail()
    {
        $this->createVirtualAccount(['customer_id' => 'cust_100000customer']);

        $response = $this->fetchVirtualAccounts(['email' => 'test@razorpay.com']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchVirtualAccounts()
    {
        $this->createVirtualAccount(['name' => 'First VA']);
        $this->createVirtualAccount(['name' => 'Second VA']);

        $response = $this->fetchVirtualAccounts();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchVirtualAccountsByReceiverType()
    {
        $this->createVirtualAccount([], true, null, false);
        $this->createVirtualAccount([], true, null, false);
        $this->createVirtualAccount([], true, null, true);
        $this->createVirtualAccount([], true, null, false, true);

        $response = $this->fetchVirtualAccountsForDashboard();
        $this->assertEquals(4, $response['count']);

        $response = $this->fetchVirtualAccountsForDashboard([
            'receiver_type' => 'bank_account'
        ]);
        $this->assertEquals(2, $response['count']);

        $response = $this->fetchVirtualAccountsForDashboard([
            'receiver_type' => 'qr_code'
        ]);
        $this->assertEquals(1, $response['count']);

        $response = $this->fetchVirtualAccountsForDashboard([
            'receiver_type' => 'vpa'
        ]);
        $this->assertEquals(1, $response['count']);

        $response = $this->fetchVirtualAccountsForDashboard([
            'receiver_type' => 'bank_account,vpa'
        ]);
        $this->assertEquals(3, $response['count']);
    }

    public function testEditVirtualAccount()
    {
        // Via close Virtual Account API
        $virtualAccount = $this->createVirtualAccount();

        $lastBankAccount = $this->getLastEntity('bank_account', true);

        $response = $this->closeVirtualAccount($virtualAccount['id']);

        $updatedLastBankAccount = $this->getLastEntity('bank_account', true);

        // Because Bank Account is deleted when VA is closed
        $this->assertNotEquals($lastBankAccount['id'], $updatedLastBankAccount['id']);

        $this->assertEquals(Status::CLOSED, $response['status']);

        // Via edit Virtual Account API
        $virtualAccount = $this->createVirtualAccount();

        $lastBankAccount = $this->getLastEntity('bank_account', true);

        $this->expectException(\Rzp\Exception\ExtraFieldsException::class);

        $this->expectExceptionMessage('status is/are not required and should not be sent');

        $this->closeVirtualAccountViaEdit($virtualAccount['id']);
    }

    public function testVirtualAccountPay()
    {
        $virtualAccount = $this->createVirtualAccount([
            'amount_expected' => 10000,
        ]);

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(5000, $virtualAccount['amount_paid']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);


        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);
        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(10000, $virtualAccount['amount_paid']);
        $this->assertEquals(Status::PAID, $virtualAccount['status']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

        // Assert that the entity origin for the VA is set to merchant
        $this->verifyEntityOrigin($virtualAccount['id'], 'merchant', '10000000000000');

        // Assert that the entity origin for the VA payment is also set to merchant
        $payment = $this->getLastEntity('payment', true);
        $this->verifyEntityOrigin($payment['id'], 'merchant', '10000000000000');
    }

    public function testVirtualAccountForOrderPay()
    {

        foreach ([FeeBearer::PLATFORM, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {
            $order = $this->fixtures->create('order');

            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $virtualAccount = $this->createVirtualAccountForOrder($order);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 10000]);
            $virtualAccount = $this->getLastEntity('virtual_account', true);
            $this->assertEquals(1000000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::PAID, $virtualAccount['status']);

            $bankTransfer = $this->getLastEntity('bank_transfer', true);
            $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('paid', $order['status']);

            // Payment is automatically captured
            $payment =  $this->getLastEntity('payment', true);
            $this->assertEquals('bank_transfer', $payment['method']);
            $this->assertEquals('captured', $payment['status']);
            $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        }
    }

    public function testVirtualAccountForOrderPartialPayment()
    {
        foreach ([FeeBearer::PLATFORM, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {
            $order = $this->fixtures->create('order', ['partial_payment' => true]);

            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $virtualAccount = $this->createVirtualAccountForOrder($order);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 10000]);
            $virtualAccount = $this->getLastEntity('virtual_account', true);
            $this->assertEquals(1000000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::PAID, $virtualAccount['status']);

            $bankTransfer = $this->getLastEntity('bank_transfer', true);
            $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('paid', $order['status']);

            // Payment is automatically captured
            $payment =  $this->getLastEntity('payment', true);
            $this->assertEquals('bank_transfer', $payment['method']);
            $this->assertEquals('captured', $payment['status']);
            $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        }

    }

    public function testVirtualAccountForOrderPartialPaymentExcessAmount()
    {
        $this->fixtures->merchant->addFeatures(['excess_order_amount']);

        foreach ([FeeBearer::PLATFORM, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {
            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $order = $this->fixtures->create('order', ['partial_payment' => true]);

            $virtualAccount = $this->createVirtualAccountForOrder($order);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 20000]);
            $virtualAccount = $this->getLastEntity('virtual_account', true);
            $this->assertEquals(2000000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::PAID, $virtualAccount['status']);

            $bankTransfer = $this->getLastEntity('bank_transfer', true);
            $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('paid', $order['status']);

            // Payment is automatically captured
            $payment =  $this->getLastEntity('payment', true);
            $this->assertEquals('bank_transfer', $payment['method']);
            $this->assertEquals('captured', $payment['status']);
            $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        }
    }

    public function testVirtualAccountForOrderPartialPaymentPartialAmount()
    {
        foreach ([FeeBearer::PLATFORM, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {
            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $order = $this->fixtures->create('order', ['partial_payment' => true]);

            $virtualAccount = $this->createVirtualAccountForOrder($order);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 5000]);
            $virtualAccount = $this->getLastEntity('virtual_account', true);
            $this->assertEquals(500000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);

            $bankTransfer = $this->getLastEntity('bank_transfer', true);
            $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('attempted', $order['status']);

            // Payment is automatically captured
            $payment =  $this->getLastEntity('payment', true);
            $this->assertEquals('bank_transfer', $payment['method']);
            $this->assertEquals('captured', $payment['status']);
            $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        }

    }

    public function testVirtualAccountForOrderPartialPaymentMultiple()
    {
        $this->fixtures->merchant->addFeatures(['excess_order_amount']);

        foreach ([FeeBearer::PLATFORM, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {
            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $order = $this->fixtures->create('order', ['partial_payment' => true]);

            $virtualAccount = $this->createVirtualAccountForOrder($order);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 5000]);
            $virtualAccount = $this->getLastEntity('virtual_account', true);

            $this->assertEquals(500000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);

            $bankTransfer = $this->getLastEntity('bank_transfer', true);
            $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('attempted', $order['status']);

            // Payment is automatically captured
            $payment =  $this->getLastEntity('payment', true);
            $this->assertEquals('bank_transfer', $payment['method']);
            $this->assertEquals('captured', $payment['status']);
            $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 20000]);

            $virtualAccount = $this->getLastEntity('virtual_account', true);

            $virtualAccount = $this->getLastEntity('virtual_account', true);

            $this->assertEquals(2500000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::PAID, $virtualAccount['status']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('paid', $order['status']);
        }

    }

    public function testVirtualAccountForOrderPayCustomerFeeBearer()
    {
        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        foreach ([FeeBearer::CUSTOMER, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {
            $order = $this->fixtures->create('order');

            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $virtualAccount = $this->createVirtualAccountForOrder($order);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 10059]);
            $virtualAccount = $this->getLastEntity('virtual_account', true);
            $this->assertEquals(1000000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::PAID, $virtualAccount['status']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('paid', $order['status']);

            // Payment is automatically captured
            $payment =  $this->getLastEntity('payment', true);
            $this->assertEquals('bank_transfer', $payment['method']);
            $this->assertEquals(1005900, $payment['amount']);
            $this->assertEquals('captured', $payment['status']);
        }

    }

    public function testVirtualAccountForOrderPayCustomerFeeBearerPartialPayment()
    {
        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);


        foreach ([FeeBearer::CUSTOMER, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {
            $order = $this->fixtures->create('order', ['partial_payment' => true]);

            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $virtualAccount = $this->createVirtualAccountForOrder($order);


            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 10059]);
            $virtualAccount = $this->getLastEntity('virtual_account', true);
            $this->assertEquals(1000000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::PAID, $virtualAccount['status']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('paid', $order['status']);

            // Payment is automatically captured
            $payment =  $this->getLastEntity('payment', true);
            $this->assertEquals('bank_transfer', $payment['method']);
            $this->assertEquals(1005900, $payment['amount']);
            $this->assertEquals('captured', $payment['status']);
        }

    }

    public function testVirtualAccountForOrderPayCustomerFeeBearerPartialExcessPayment()
    {
        $this->fixtures->merchant->addFeatures(['excess_order_amount']);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);


        foreach ([FeeBearer::CUSTOMER, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {

            $order = $this->fixtures->create('order', ['partial_payment' => true]);

            $virtualAccount = $this->createVirtualAccountForOrder($order);

            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 20059]);
            $virtualAccount = $this->getLastEntity('virtual_account', true);
            $this->assertEquals(2000000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::PAID, $virtualAccount['status']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('paid', $order['status']);

            // Payment is automatically captured
            $payment =  $this->getLastEntity('payment', true);
            $this->assertEquals('bank_transfer', $payment['method']);
            $this->assertEquals(2005900, $payment['amount']);
            $this->assertEquals('captured', $payment['status']);
        }

    }

    public function testFetchVirtualAccountsMultiple()
    {
        $this->markTestSkipped('The flakiness in the testcase needs to be fixed. Skipping as its impacting dev-productivity.');

        // Creates virtual account on primary balance.
        $this->createVirtualAccount(['description' => 'Testing VA fetch after ES sync', 'customer_id' => 'cust_100000customer']);

        $input = $this->testData[__FUNCTION__]['input'];

        $expectedOutput = $this->testData[__FUNCTION__]['output'];

        $this->assertArraySelectiveEquals($expectedOutput, $this->fetchVirtualAccounts($input['input1']));

        $this->assertArraySelectiveEquals($expectedOutput, $this->fetchVirtualAccounts($input['input2']));
    }

    public function testFetchVirtualAccountsMultipleByPayeeAccount()
    {
        $virtualAccount1 = $this->createVirtualAccount(['name' => 'bank account search','receivers' => ['types' => ['bank_account']]]);

        $virtualAccount2 = $this->createVirtualAccount(['name' => 'vpa search','receivers' => ['types' => ['vpa']]]);

        $input = $this->testData[__FUNCTION__]['input'];

        $expectedOutput = $this->testData[__FUNCTION__]['output'];

        $accountNumber = $virtualAccount1['receivers'][0]['account_number'];

        $input['input1']['payee_account'] = $accountNumber;

        $this->assertArraySelectiveEquals($expectedOutput['output1'], $this->fetchVirtualAccounts($input['input1']));

        $input['input1']['payee_account'] = substr($accountNumber,0,8);

        $this->assertArraySelectiveEquals($expectedOutput['output1'], $this->fetchVirtualAccounts($input['input1']));

        $address = $virtualAccount2['receivers'][0]['address'];

        $input['input2']['payee_account'] = $address;

        $this->assertArraySelectiveEquals($expectedOutput['output2'], $this->fetchVirtualAccounts($input['input2']));

        $input['input2']['payee_account'] = substr($address, 0, 9);

        $this->assertArraySelectiveEquals($expectedOutput['output2'], $this->fetchVirtualAccounts($input['input2']));
    }

    public function testVirtualAccountForOrderPayCustomerFeeBearerPartialMultiplePayment()
    {
        $this->fixtures->merchant->addFeatures(['excess_order_amount']);

        foreach ([FeeBearer::CUSTOMER, FeeBearer::DYNAMIC] as $merchantFeeBearer)
        {
            $order = $this->fixtures->create('order', ['partial_payment' => true]);

            $virtualAccount = $this->createVirtualAccountForOrder($order);

            $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => $merchantFeeBearer]);

            $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 5059]);

            $virtualAccount = $this->getLastEntity('virtual_account', true);

            $this->assertEquals(500000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);

            $bankTransfer = $this->getLastEntity('bank_transfer', true);
            $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('attempted', $order['status']);

            // Payment is automatically captured
            $payment =  $this->getLastEntity('payment', true);
            $this->assertEquals('bank_transfer', $payment['method']);
            $this->assertEquals('captured', $payment['status']);
            $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

            $this->payVirtualAccount($virtualAccount['id'], ['amount' => 20059]);

            $virtualAccount = $this->getLastEntity('virtual_account', true);

            $virtualAccount = $this->getLastEntity('virtual_account', true);

            $this->assertEquals(2500000, $virtualAccount['amount_paid']);
            $this->assertEquals(Status::PAID, $virtualAccount['status']);

            $order = $this->getLastEntity('order', true);
            $this->assertEquals('paid', $order['status']);
        }
    }


    public function testVirtualAccountForOrderPayAndRefund()
    {
        $order = $this->fixtures->create('order');

        $virtualAccount = $this->createVirtualAccountForOrder($order);

        // Make a payment with wrong order amount
        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $virtualAccount = $this->getLastEntity('virtual_account', true);
        // Will see about this later
        // $this->assertEquals(0, $virtualAccount['amount_paid']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

        // Order status will not change
        $order = $this->getLastEntity('order', true);
        $this->assertEquals('created', $order['status']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals($payment['id'], $refund['payment_id']);

        // Make a payment with right order amount
        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 10000]);
        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(1005000, $virtualAccount['amount_paid']);

        $this->assertEquals(Status::PAID, $virtualAccount['status']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals('paid', $order['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
    }

    public function testFetchPaymentsForVirtualAccount()
    {
        $virtualAccount = $this->createVirtualAccount();

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $response = $this->fetchVirtualAccountPayments($virtualAccount['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchPaymentsForVirtualAccountForQrCode()
    {
        $virtualAccount = $this->createVirtualAccount([], true, null, true);

        $qrCodeId = substr($virtualAccount['receivers'][0]['id'], 3);

        $mockServer = $this->app['gateway']->server('hitachi');

        $this->makeRequestAndGetContent([
            'url'     => '/payment/callback/bharatqr/hitachi',
            'method'  => 'post',
            'content' => $mockServer->getBharatQrCallback($qrCodeId),
        ]);

        $response = $this->fetchVirtualAccountPayments($virtualAccount['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        // This is to ensure that old qr payments should remain part of VA payments

        $this->fixtures->merchant->addFeatures(['qr_codes']);

        $response = $this->fetchVirtualAccountPayments($virtualAccount['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testVirtualAccountForCustomer()
    {
        $virtualAccount = $this->createVirtualAccount([
            'customer_id' => 'cust_100000customer',
        ]);

        $this->assertEquals('cust_100000customer', $virtualAccount['customer_id']);

        $this->payVirtualAccount($virtualAccount['id']);

        $payment = $this->getLastEntity('payment', true);

        $customer = $this->getEntityById('customer', 'cust_100000customer', true);

        $this->assertEquals($customer['id'], $payment['customer_id']);
        $this->assertEquals($customer['email'], $payment['email']);
        $this->assertStringEndsWith($customer['contact'], $payment['contact']);
    }

    public function testWebhookVirtualAccountCreated()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('virtual_account.created', $expectedEvent);

        $this->createVirtualAccount();
    }

    public function testWebhookVirtualAccountCredited()
    {
        $virtualAccount = $this->createVirtualAccount();

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $paymentArray = $event['payload']['payment']['entity'];
                $this->assertArrayNotHasKey('terminal_id', $paymentArray);
            }
        );

        $this->payVirtualAccount($virtualAccount['id']);
    }

    public function testWebhookVirtualAccountCreditedForBharatQr()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);

                // Virtual account credited webhook contains bank_tranfer
                // entity if applicable, but never bharat_qr entity.
                $this->assertArrayNotHasKey('bharat_qr', $event['payload']);
            }
        );

        $this->testFetchPaymentsForVirtualAccountForQrCode();
    }

    public function testWebhookVirtualAccountClosed()
    {
        $virtualAccount = $this->createVirtualAccount();

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('virtual_account.closed', $expectedEvent);

        $this->closeVirtualAccount($virtualAccount['id']);
    }

    public function testVirtualAccountMarkedClosed()
    {
        $order = $this->fixtures->create('order');

        $this->createVirtualAccountForOrder($order);

        $lastBankAccount = $this->getLastEntity('bank_account', true);

        $payment = $this->fixtures->create('payment:authorized', ['order_id' => $order->getId()]);

        $this->capturePayment('pay_'. $payment->getId(), $payment->getAmount());

        $virtualAccount = $this->getLastEntity('virtual_account', true);

        $updatedLastBankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals(Status::CLOSED, $virtualAccount['status']);

        // Because Bank Account is deleted too when VA is closed
        $this->assertNotEquals($lastBankAccount['id'], $updatedLastBankAccount['id']);
    }

    public function testPayVirutalAccountOnBankingBalance()
    {
        $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true);
        $this->fixtures->merchant->disableMethod('10000000000000', 'bank_transfer');

        // Doing this here because these are for banking product fund loads and we want to disable tpv flow for these
        // flows.
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_TPV_FLOW,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        // Does /ecollect/validate (i.e. payment) api call.
        $this->ba->proxyAuth();
        $this->startTest();

        // Various assertions follows on updated entities following a payment above.
        // Banking balance and only that should have been credited.
        $primaryBalance = $this->getDbEntityById('merchant', '10000000000000')->primaryBalance;
        $this->bankingBalance->reload();
        $this->assertEquals(2500, $this->bankingBalance->getBalance());
        $this->assertEquals($primaryBalance->getBalance(), $primaryBalance->reload()->getBalance());
        // A credit transaction with source of type bank_transfer and on banking balance, should have been created.
        $txns = $this->getDbEntities('transaction');
        $this->assertCount(1, $txns);
        $txn = $txns->first();
        $this->assertInstanceOf(BankTransfer\Entity::class, $txn->source);
        $this->assertEquals(2500, $txn->getAmount());
        $this->assertEquals(2500, $txn->getCredit());
        $this->assertEquals(0, $txn->getDebit());
        $this->assertEquals('yesbank', $txn->getChannel());
        $this->assertEquals($this->bankingBalance->getId(), $txn->getBalanceId());
        // No payment should have been created.
        $payments = $this->getDbEntities('payment');
        $this->assertCount(0, $payments);
        // Asserts balance association of newly created bank transfer.
        $bankTransfer = $this->getDbLastEntity('bank_transfer');
        $this->assertEquals($this->bankingBalance->getId(), $bankTransfer->getBalanceId());
    }

    /**
     * Note: This test is more like a unit test, being done here because unit test base does not have fixtures.
     */
    public function testCreateForBankingBalance()
    {
        // Sets up test merchant for business banking
        $this->fixtures->merchant->createBalanceOfBankingType();
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Case 1: Failure - Attempting to create virtual account when merchant is not setup for business banking.

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->expectException(\Rzp\Exception\BadRequestException::class);

        $this->expectExceptionMessage('Access to requested resource not available');

        $virtualAccount = (new Core)->createForBankingBalance($merchant, $merchant->sharedBankingBalance);

        // Case 2: Success

        $this->fixtures->edit('merchant', '10000000000000', ['activated' => true, 'business_banking' => true]);

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $virtualAccount = (new Core)->createForBankingBalance($merchant, $merchant->sharedBankingBalance);
        $this->assertEquals($merchant->sharedBankingBalance->getId(), $virtualAccount->getBalanceId());
        $this->assertNotEmpty($virtualAccount->bankAccount);
        $this->assertStringStartsWith('222444', $virtualAccount->bankAccount->getAccountNumber());
        // Assert that creation of first bank account updates balance's account number attribute.
        $this->assertEquals(
            $virtualAccount->bankAccount->getAccountNumber(),
            $merchant->sharedBankingBalance->getAccountNumber());
    }

    public function testFetchVirtualAccountBankingMultipleWithBalanceId()
    {
        $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true);

        $bankingBalance = $this->getDbLastEntity('balance');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/virtual_accounts/banking/account?balance_id=' . $bankingBalance['id'];

        $this->startTest();
    }

    public function testUpdateOnVirtualAccountOfBankingBalanceFails()
    {
        $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true);

        $this->expectException(\Rzp\Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Operation is not allowed for this specific virtual account');

        // This method calls the update route.
        $this->closeVirtualAccountViaEdit($this->virtualAccount->getPublicId());
    }
    public function testVirtualAccountUpdate_CollectxVA()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

        $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true);

        $request = [
            'method'  => 'PATCH',
            'url'     => '/virtual_accounts/'.$this->virtualAccount->getPublicId(),
            'content' => [
                'name' => 'Test new Name',
            ],
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        self::assertEquals('Test new Name', $response['name']);
    }

    public function testUpdateVirtualAccount_PrimaryBalanceTypeVA()
    {
        $virtualAccount = $this->createVirtualAccount();

        $request = [
            'method'  => 'PATCH',
            'url'     => '/virtual_accounts/'.$virtualAccount['id'],
            'content' => [
                'name' => 'Test new Name',
            ],
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        self::assertEquals('Test new Name', $response['name']);
    }

    public function testClosingOfVirtualAccountOfBankingBalanceFails()
    {
        $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true);

        $this->expectException(\Rzp\Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Operation is not allowed for this specific virtual account');

        $this->closeVirtualAccount($this->virtualAccount->getPublicId());
    }

    public function testFetchVirtualAccountsMustNotIncludeBankingVAs()
    {
        // Creates virtual account on primary balance.
        $this->createVirtualAccount();

        // Creates virtual account on banking balance.
        $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__], $this->fetchVirtualAccounts());
    }

    public function testCreateVirtualAccountWithoutCustomerIdWithCustomerDetails()
    {

        $response = $this->startTest();

        $this->assertNotNull($response['customer_id']);
    }

    public function testCreateVirtualAccountWithoutCustomerIdWithoutCustomerDetails()
    {
        $response = $this->startTest();

        $this->assertNull($response['customer_id']);
    }

    public function testCreateVirtualAccountWithCustomerIdWithCustomerDetails()
    {
        $customer = $this->fixtures->create(
            'customer',
            [
                'id'          => '100022customer',
                'contact'     => null,
                'email'       => null,
                'merchant_id' => '10000000000000',
            ]);
        $response = $this->startTest();

        $customerId = Entity::stripDefaultSign($response['customer_id']);

        $this->assertEquals($customer['id'], $customerId);
    }

    public function testCreateVirtualAccountInvalidCustomerEmail()
    {
        $response = $this->startTest();
    }

    protected function getTagMappedValues(string $qrString)
    {
        $tlvArray = [];

        $length = strlen($qrString);

        $index = 0;

        while ($index < $length)
        {
            $tlvTag = substr($qrString, $index, 2);

            $index += 2;

            $tlvLength = (int) substr($qrString, $index, 2);

            $index += 2;

            $tlvArray[$tlvTag] = substr($qrString, $index, $tlvLength);

            $index += $tlvLength;
        }

        return $tlvArray;
    }

    public function testAddVpaToExistingVirtualAccount()
    {
        $virtualAccount = $this->createVirtualAccount();

        $response = $this->addReceiverToVirtualAccount($virtualAccount['id'], 'vpa', ['descriptor' => 'virtualVpa']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testAddVpaToExistingVAWithVpa()
    {
        $expectedResponse = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($expectedResponse, function() {

            $virtualAccount = $this->createVirtualAccount([], false, null, null, true, 'virtualVpa');

            $this->addReceiverToVirtualAccount($virtualAccount['id'], 'vpa', ['descriptor' => 'virtualVpa']);
        });
    }

    public function testAddVpaToExistingVAWithOrder()
    {
        $order = $this->fixtures->create('order');

        $virtualAccount = $this->createVirtualAccountForOrder($order);

        $this->expectException(\Rzp\Exception\BadRequestException::class);

        $this->expectExceptionMessage('Can\'t add receiver to existing VA with order');

        $this->addReceiverToVirtualAccount($virtualAccount['id'], 'vpa');
    }

    public function testWebhookVirtualAccountCreatedForVpa()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('virtual_account.created', $expectedEvent);

        $this->createVirtualAccount([], false, null, null, true,'virtualVpa');
    }


    public function testOfflineQrCloseBy()
    {
        Carbon::setTestNow(Carbon::create(2019, 12, 30, 0, 0, 0, 'Asia/Kolkata'));

        $this->startTest();
    }

    public function testOfflineVACreation()
    {
        Carbon::setTestNow(Carbon::create(2019, 12, 30, 0, 0, 0, 'Asia/Kolkata'));

        $this->fixtures->merchant->addFeatures('offline_payments');

        $response = $this->startTest();

        $this->assertArrayHasKey('order_id', $response);
    }

    public function testCloseVirtualAccountWithVpa()
    {
        $virtualAccount = $this->createVirtualAccount([], false, null, null, true, 'virtualVpa');

        $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);

        $this->closeVirtualAccount($virtualAccount['id']);

        $virtualAccount = $this->getDbLastEntity('virtual_account');

        $this->assertEquals(Status::CLOSED, $virtualAccount->getStatus());
    }

    public function testFetchVirtualAccountsWithBankAccountId2()
    {
        $this->createVirtualAccount();

        $virtualAccount = $this->getDbLastEntity('virtual_account');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals($bankAccount['id'], $virtualAccount['bank_account_id']);

        $bankAccount2 = $this->createBankAccount(['account_number' => $bankAccount['account_number']]);

        $virtualAccount->bankAccount2()->associate($bankAccount2);

        $this->app['repo']->virtual_account->saveOrFail($virtualAccount);

        $virtualAccount->refresh();

        $this->assertArraySubset([
            [
                'entity'          => 'bank_account',
                'ifsc'            => 'RAZR0000001',
                'account_number'  => $bankAccount['account_number'],
            ],
            [
                'entity'          => 'bank_account',
                'ifsc'            => 'RAZOR000002',
                'account_number'  => $bankAccount['account_number'],
            ]
        ], $virtualAccount['receivers']);
    }

    public function testFetchVirtualAccountsWithDifferentBankAccountId2()
    {
        $this->createVirtualAccount();

        $virtualAccount = $this->getDbLastEntity('virtual_account');

        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals($bankAccount['id'], $virtualAccount['bank_account_id']);

        $bankAccount2 = $this->createBankAccount(['account_number' => random_integer(16)]);

        $virtualAccount->bankAccount2()->associate($bankAccount2);

        $this->app['repo']->virtual_account->saveOrFail($virtualAccount);

        $virtualAccount->refresh();

        $this->assertArraySubset([
                                     [
                                         'entity'         => 'bank_account',
                                         'ifsc'           => 'RAZR0000001',
                                         'account_number' => $bankAccount['account_number'],
                                     ],
                                     [
                                         'entity'         => 'bank_account',
                                         'ifsc'           => 'RAZOR000002',
                                         'account_number' => $bankAccount2['account_number'],
                                     ]
                                 ], $virtualAccount['receivers']);
    }

    public function testCreateVirtualAccountForBanking()
    {
        $this->setUpMerchantForBusinessBanking(true, 10000000);

        $this->fixtures->merchant->addFeatures(Feature\Constants::VIRTUAL_ACCOUNTS_BANKING);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $virtualAccount = $this->getDbEntityById('virtual_account', $response['id']);

        $this->assertEquals($merchant->sharedBankingBalance->getId(), $virtualAccount->getBalanceId());
        $this->assertNotEmpty($virtualAccount->bankAccount);
        $this->assertStringStartsWith('232323', $virtualAccount->bankAccount->getAccountNumber());
        $this->assertNotEquals(
            $virtualAccount->bankAccount->getAccountNumber(),
            $merchant->sharedBankingBalance->getAccountNumber());
    }

    public function testCreateVirtualAccountForBankingWithBody()
    {
        $this->setUpMerchantForBusinessBanking(true, 10000000);

        $this->fixtures->merchant->addFeatures(Feature\Constants::VIRTUAL_ACCOUNTS_BANKING);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $virtualAccount = $this->getDbEntityById('virtual_account', $response['id']);

        $this->assertEquals($merchant->sharedBankingBalance->getId(), $virtualAccount->getBalanceId());
        $this->assertNotEmpty($virtualAccount->bankAccount);
        $this->assertStringStartsWith('232323', $virtualAccount->bankAccount->getAccountNumber());
        $this->assertNotEquals(
            $virtualAccount->bankAccount->getAccountNumber(),
            $merchant->sharedBankingBalance->getAccountNumber());
    }

    public function testCreateVirtualAccountForBankingWithoutFeature()
    {
        $this->setUpMerchantForBusinessBanking(true, 10000000);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateVirtualAccountForBankingWithoutBusinessBankingFlagEnabled()
    {
        $this->fixtures->merchant->addFeatures(Feature\Constants::VIRTUAL_ACCOUNTS_BANKING);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateVirtualAccountInBulkForBanking()
    {
        $this->setUpMerchantForBusinessBanking(true, 10000000);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateVirtualAccountInBulk_Banking_RBL($skipSetup = false)
    {
        Queue::fake();

        $testData = $this->testData['testCreateVirtualAccountInBulkForBanking'];

        $this->app['config']->set('gateway.mock_bt_rbl', true);

        if ($skipSetup === false)
        {
            $terminalAttributes = [
                'gateway' => Gateway::RBL,
                'merchant_id' => '10000000000000',
                'gateway_merchant_id' => '123456',
                'type' => [
                    Type::NON_RECURRING     => '1',
                    Type::NUMERIC_ACCOUNT   => '1',
                    Type::BUSINESS_BANKING  => '1',
                ]
            ];

            $this->fixtures->on('live')->create('terminal:bank_account_terminal', $terminalAttributes);

            $this->setUpMerchantForBusinessBankingLive(true, 10000000);
        }

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    "10000000000000" => "123456"
                ]
            ]);

        $this->ba->adminAuth('live');

        $this->startTest($testData);

        $virtualAccount = $this->getDbLastEntity('virtual_account', 'live');
        $bankAccount = $this->getDbLastEntity('bank_account', 'live');

        $this->assertNotNull( $virtualAccount['bank_account_id']);
        $this->assertEquals($bankAccount['id'], $virtualAccount['bank_account_id']);
        $this->assertEquals($virtualAccount['status'], 'active');

        $this->assertEquals('RATN0VAAPIS', $bankAccount['ifsc_code']);
        $this->assertEquals(BankAccountConstants::BANK_ACCOUNT_NOT_SYNCED, $bankAccount['is_gateway_sync']);

        Queue::assertPushed(RblVirtualAccountForBanking::class, 1);

        return $virtualAccount;
    }

    public function testRblVirtualAccountForBanking_CreateAction_Handle()
    {
        $virtualAccount = $this->testCreateVirtualAccountInBulk_Banking_RBL();

        $bankAccount = $this->getDbLastEntity('bank_account', 'live');
        $this->assertEquals(BankAccountConstants::BANK_ACCOUNT_NOT_SYNCED, $bankAccount['is_gateway_sync']);

        (new RblVirtualAccountForBanking(Mode::LIVE,
            $virtualAccount['id'],
            Action::CREATE_VIRTUAL_ACCOUNT_FOR_BANKING))->handle();

        $bankAccount->reload();
        $this->assertEquals(BankAccountConstants::BANK_ACCOUNT_CREATE_SYNCED, $bankAccount['is_gateway_sync']);

        return $virtualAccount;
    }

    public function testRblVirtualAccountForBanking_CreateAction_Handle_ErrorFromBank()
    {
        $virtualAccount = $this->testCreateVirtualAccountInBulk_Banking_RBL();

        $bankAccount = $this->getDbLastEntity('bank_account', 'live');

        $this->assertEquals(BankAccountConstants::BANK_ACCOUNT_NOT_SYNCED, $bankAccount['is_gateway_sync']);

        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $this->app['config']->set('rbl_create_virtual_account.error_code', ErrorCode::ER002);

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Metric::RBL_VIRTUAL_ACCOUNT_BANKING_JOB_FAILURES_COUNT,
            $metricsMock,
            $boolMetricCaptured,
            [
                'action' => Action::CREATE_VIRTUAL_ACCOUNT_FOR_BANKING
            ]
        );

        (new RblVirtualAccountForBanking(Mode::LIVE,
            $virtualAccount['id'],
            Action::CREATE_VIRTUAL_ACCOUNT_FOR_BANKING))->handle();

        $virtualAccount->reload();

        $this->assertEquals($virtualAccount['status'], 'closed');

        $this->assertTrue($boolMetricCaptured);
    }

    public function testCloseVirtualAccountInBulk_Banking_RBL()
    {
        $virtualAccount1 = $this->testCreateVirtualAccountInBulk_Banking_RBL();

        $virtualAccount2 = $this->testCreateVirtualAccountInBulk_Banking_RBL(true);

        $this->app->singleton('worker.ctx', function($app) {
            return new Context($app);
        });

        Queue::fake();

        $this->testData[__FUNCTION__]['request']['content'] = [
            'virtual_account_ids' => [
                $virtualAccount1->getPublicId(),
                $virtualAccount2->getPublicId(),
            ]
        ];

        $this->ba->adminAuth('live');

        $this->startTest();

        Queue::assertPushed(RblVirtualAccountForBanking::class, 2);

        $virtualAccount1 = $this->getEntityById('virtual_account', $virtualAccount1['id'], true, 'live');
        $virtualAccount2 = $this->getEntityById('virtual_account', $virtualAccount2['id'], true, 'live');

        $this->assertEquals($virtualAccount1['status'], 'closed');
        $this->assertEquals($virtualAccount2['status'], 'closed');
    }

    public function testRblVirtualAccountForBanking_CloseAction_Handle()
    {
        $this->testCloseVirtualAccountInBulk_Banking_RBL();

        $virtualAccount = $this->getDbLastEntity('virtual_account', 'live');

        $this->app['config']->set('gateway.mock_bt_rbl', true);

        (new RblVirtualAccountForBanking(Mode::LIVE,
            $virtualAccount['id'],
            Action::CLOSE_VIRTUAL_ACCOUNT_FOR_BANKING))->handle();
    }

    public function testRblVirtualAccountForBanking_CloseAction_Handle_ErrorFromBank()
    {
        $this->testCloseVirtualAccountInBulk_Banking_RBL();

        $virtualAccount = $this->getDbLastEntity('virtual_account', 'live');

        $this->app['config']->set('rbl_close_virtual_account.error_code', ErrorCode::ER002);

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Metric::RBL_VIRTUAL_ACCOUNT_BANKING_JOB_FAILURES_COUNT,
            $metricsMock,
            $boolMetricCaptured,
            [
                'action' => Action::CLOSE_VIRTUAL_ACCOUNT_FOR_BANKING
            ]
        );

        (new RblVirtualAccountForBanking(Mode::LIVE,
            $virtualAccount['id'],
            Action::CLOSE_VIRTUAL_ACCOUNT_FOR_BANKING))->handle();

        $virtualAccount->reload();

        $this->assertEquals($virtualAccount['status'], 'closed');

        $this->assertTrue($boolMetricCaptured);
    }

    public function testCloseVirtualAccountInBulk()
    {
        $virtualAccount1 = $this->createVirtualAccount();
        $virtualAccount2 = $this->createVirtualAccount();
        $virtualAccount3 = $this->createVirtualAccount();

        $this->testData[__FUNCTION__]['request']['content'] = [
            'virtual_account_ids' => [
                substr($virtualAccount1['id'], 3, strlen($virtualAccount1['id'])),
                substr($virtualAccount2['id'], 3, strlen($virtualAccount2['id'])),
                substr($virtualAccount3['id'], 3, strlen($virtualAccount3['id'])),
            ]
        ];

        $this->testData[__FUNCTION__]['response']['content']= [
            'success' => [$virtualAccount1['id'], $virtualAccount2['id'], $virtualAccount3['id']]
        ];

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCloseVirtualAccountInBulkForBanking()
    {
        $this->setUpMerchantForBusinessBanking(true, 10000000);

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $virtualAccount1 = (new Core)->createForBankingBalance($merchant, $merchant->sharedBankingBalance);
        $virtualAccount2 = (new Core)->createForBankingBalance($merchant, $merchant->sharedBankingBalance);

        $this->testData[__FUNCTION__]['request']['content'] = [
            'virtual_account_ids' => [
                $virtualAccount1->getPublicId(),
                $virtualAccount2->getPublicId(),
                'va_10000000000000'
            ]
        ];

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function createBankAccount(array $overrideWith = [])
    {
        $bankAccount = $this->fixtures
            ->create(
                'bank_account',
                array_merge(
                    [
                        'beneficiary_name' => 'Yes bank deactivated',
                        'ifsc_code'        => 'RAZOR000002',
                        'account_type'     => 'savings',

                    ],
                    $overrideWith
                )
            );

        return $bankAccount;
    }

    public function testVirtualAccountPaymentForOrderWithNotes()
    {
        $order = $this->fixtures->create('order', ['notes' => ['key' => 'value']]);

        $virtualAccount = $this->createVirtualAccountForOrder($order);

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 10000]);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals('paid', $order['status']);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals($payment['notes'], ['key' => 'value']);
        $this->assertEquals('bank_transfer', $payment['method']);
    }

    public function testFetchVirtualAccountPayments()
    {
        $virtualAccount = $this->createVirtualAccount();

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $response = $this->fetchVirtualAccountPayments();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testPaymentsFetchMultiple()
    {
        $virtualAccount = $this->createVirtualAccount();

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50, 'transaction_id' => '09870000000']);

        $response = $this->fetchVirtualAccountPayments($virtualAccount['id'], '09870000000');

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testCreateVirtualAccountForVpaWithCustomPrefix()
    {
        $this->savePrefix('paytorazor');

        $response         = $this->createVirtualAccount([], false, null, null, true, 'virtualVpa');

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testCreateVirtualAccountWithVpaForIcici()
    {
        $response = $this->createVirtualAccount([], false, null, null, true, 'virtualVpa');

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testVaCoreGetVaName()
    {
        $input = [];

        // Case 1: name = "a", billing_label = "a"
        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'a', 'billing_label' => 'a']);

        $vaName = $this->invokeGetVaName($merchant, $input);

        $this->assertEquals('default', $vaName);

        // Case 2: name = "test", billing_label = "a"
        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'test', 'billing_label' => 'a']);

        $vaName = $this->invokeGetVaName($merchant, $input);

        $this->assertEquals('test', $vaName);

        // Case 3: name = "test1", billing_label = "test2"
        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'test1', 'billing_label' => 'test2']);

        $vaName = $this->invokeGetVaName($merchant, $input);

        $this->assertEquals('test2', $vaName);

        // Case 4: name = "a", billing_label = "test"
        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'a', 'billing_label' => 'test']);

        $vaName = $this->invokeGetVaName($merchant, $input);

        $this->assertEquals('test', $vaName);

        // Case 5: $input = ["name" => "testing"];
        $input = ["name" => "testing"];

        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'test', 'billing_label' => 'test']);

        $vaName = $this->invokeGetVaName($merchant, $input);

        $this->assertEquals('testing', $vaName);
    }

    public function invokeGetVaName (\RZP\Models\Merchant\Entity $merchant, array $input) :string
    {
        $vaCore = new \RZP\Models\VirtualAccount\Core();

        $vaCoreReflectionObj = new \ReflectionObject($vaCore);

        $getVaNameMethod = $vaCoreReflectionObj->getMethod('getVaName');

        $getVaNameMethod->setAccessible(true);

        $vaName = $getVaNameMethod->invokeArgs($vaCore, [$merchant, $input]);

        return $vaName;
    }

    public function testVaEntityModifyName()
    {
        $input = [];

        // Case 1: name = "a", billing_label = "a"
        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'a', 'billing_label' => 'a']);

        $vaName = $this->invokeModifyName($merchant, $input);

        $this->assertTrue(array_key_exists('name', $vaName) === false);

        // Case 2: name = "test", billing_label = "a"
        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'test', 'billing_label' => 'a']);

        $vaName = $this->invokeModifyName($merchant, $input);

        $this->assertEquals('test', $vaName['name']);

        // Case 3: name = "test1", billing_label = "test2"
        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'test1', 'billing_label' => 'test2']);

        $vaName = $this->invokeModifyName($merchant, $input);

        $this->assertEquals('test2', $vaName['name']);

        // Case 4: name = "a", billing_label = "test"
        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'a', 'billing_label' => 'test']);

        $vaName = $this->invokeModifyName($merchant, $input);

        $this->assertEquals('test', $vaName['name']);

        // Case 5: $input = ["name" => "testing"];
        $input = ["name" => "testing"];

        $merchant = $this->fixtures->edit('merchant', '10000000000000', ['name' => 'test', 'billing_label' => 'test']);

        $vaName = $this->invokeModifyName($merchant, $input);

        $this->assertEquals('testing', $vaName['name']);
    }

    public function invokeModifyName (\RZP\Models\Merchant\Entity $merchant, array $input) :array
    {
        $vaEntity = new \RZP\Models\VirtualAccount\Entity();

        $vaEntity->merchant()->associate($merchant);

        $vaEntityReflectionObj = new \ReflectionObject($vaEntity);

        $method = $vaEntityReflectionObj->getMethod('modifyName');

        $method->setAccessible(true);

        $method->invokeArgs($vaEntity, array(&$input));

        return $input;
    }

    public function testVirtualAccountVpaVerification()
    {
        $this->createVirtualAccount([], true, null, false, true);

        $vpa    = $this->getLastEntity('vpa', true);

        $address = explode('.', $vpa['username'])[1];

        $input = '<XML><Source>ICICI-EAZYPAY</Source><SubscriberId>' . $address . '</SubscriberId><TxnId>YBL457b50e1fa8b452ab996560a0c9bc8be</TxnId></XML>';
        $virtualUpiRoot = explode('.', $this->vpaTerminal['virtual_upi_root'])[0];

        $rawResponse = $this->ecollectValidateVirtualAccountVpa('upi_icici', $virtualUpiRoot, $input);
        $response = (array) simplexml_load_string($rawResponse->content());

        $this->assertEquals($response['ActCode'], '0');
        $this->assertEquals($response['Message'], 'VALID');
        $this->assertEquals($response['CustName'], 'Test Merchant');
        $this->assertEquals($response['TxnId'], 'YBL457b50e1fa8b452ab996560a0c9bc8be');
    }

    public function testInvalidVpaVerification()
    {
        $input = '<XML><Source>ICICI-EAZYPAY</Source><SubscriberId>upitestaccount123456</SubscriberId><TxnId>YBL457b50e1fa8b452ab996560a0c9bc8be</TxnId></XML>';
        $virtualUpiRoot = explode('.', $this->vpaTerminal['virtual_upi_root'])[0];

        $rawResponse = $this->ecollectValidateVirtualAccountVpa('upi_icici', $virtualUpiRoot, $input);
        $response = (array) simplexml_load_string($rawResponse->content());

        $this->assertEquals($response['ActCode'], '1');
        $this->assertEquals($response['Message'], 'INVALID');
    }

    public function testMerchantVAUpdateExpiry()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 1000;
        $virtualAccount = $this->createVirtualAccount();

        $this->fixtures->edit(
            "virtual_account",
            $virtualAccount['id'],
            ['close_by' => $closeTimeStamp]
        );

        $this->merchantId = '10000000000000';

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EDIT_SINGLE_VA_EXPIRY, '10000000000000');

        $testData = $this->testData['testMerchantVAUpdateExpiry'];
        $va_id = $virtualAccount['id'];

        $testData['request']['url'] = '/merchant/virtual_accounts/' . $va_id;

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testMerchantVAUpdateInvalidExpiry()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 1000;
        $virtualAccount = $this->createVirtualAccount();

        $this->fixtures->edit(
            "virtual_account",
            $virtualAccount['id'],
            ['close_by' => $closeTimeStamp]
        );

        $this->merchantId = '10000000000000';

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EDIT_SINGLE_VA_EXPIRY, '10000000000000');

        $testData = $this->testData['testMerchantVAUpdateInvalidExpiry'];
        $va_id = $virtualAccount['id'];

        $testData['request']['url'] = '/merchant/virtual_accounts/' . $va_id;

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testMerchantVAUpdateInvalidFormat()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 1000;
        $virtualAccount = $this->createVirtualAccount();

        $this->fixtures->edit(
            "virtual_account",
            $virtualAccount['id'],
            ['close_by' => $closeTimeStamp]
        );

        $this->merchantId = '10000000000000';

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EDIT_SINGLE_VA_EXPIRY, '10000000000000');

        $testData = $this->testData['testMerchantVAUpdateInvalidFormat'];
        $va_id = $virtualAccount['id'];

        $testData['request']['url'] = '/merchant/virtual_accounts/' . $va_id;

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testMerchantVAUpdateExpiryLessThanCurrent()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 1000;
        $virtualAccount = $this->createVirtualAccount();

        $this->fixtures->edit(
            "virtual_account",
            $virtualAccount['id'],
            ['close_by' => $closeTimeStamp]
        );

        $this->merchantId = '10000000000000';

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EDIT_SINGLE_VA_EXPIRY, '10000000000000');

        $testData = $this->testData['testMerchantVAUpdateExpiryLessThanCurrent'];
        $va_id = $virtualAccount['id'];

        $testData['request']['url'] = '/merchant/virtual_accounts/' . $va_id;

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testMerchantVAUpdateClosed()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 1000;
        $virtualAccount = $this->createVirtualAccount();

        $this->fixtures->edit(
            "virtual_account",
            $virtualAccount['id'],
            ['close_by' => $closeTimeStamp, 'status' => 'closed']
        );

        $this->merchantId = '10000000000000';

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EDIT_SINGLE_VA_EXPIRY, '10000000000000');

        $testData = $this->testData['testMerchantVAUpdateClosed'];
        $va_id = $virtualAccount['id'];

        $testData['request']['url'] = '/merchant/virtual_accounts/' . $va_id;

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testMerchantVAUpdateFeatureNotEnabled()
    {
        $closeTimeStamp = Carbon::now()->timestamp + 1000;
        $virtualAccount = $this->createVirtualAccount();

        $this->fixtures->edit(
            "virtual_account",
            $virtualAccount['id'],
            ['close_by' => $closeTimeStamp]
        );

        $this->merchantId = '10000000000000';

        $testData = $this->testData['testMerchantVAUpdateFeatureNotEnabled'];
        $va_id = $virtualAccount['id'];

        $testData['request']['url'] = '/merchant/virtual_accounts/' . $va_id;

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function generateOrderId($input)
    {
        $request  = [
        'convertContentToString' => false,
        'url'                    => '/orders',
        'method'                 => 'POST',
        'content'                => $input,
    ];

        if($this->fixtures->merchant->isFeatureEnabled([FeatureConstants::OFFLINE_PAYMENT_ON_CHECKOUT]) === false)
        {
            $this->fixtures->merchant->addFeatures(FeatureConstants::OFFLINE_PAYMENT_ON_CHECKOUT);
        }

        $this->ba->privateAuth();

        $response = $this->sendRequest($request);

        return $response['id'];

    }


    public function testValidateOfflineChallanForWrongMinLength()
    {
        try {
            $content = [
                'amount' => 1000,
                'currency' => 'INR',
                'receipt' => 'rec1',
                'customer_additional_info' => [
                    'property_id' => '12345',
                    'property_value' => 'abc'
                ],
                'notes' => [
                    'challan_number' => 'wnyx',
                ],
            ];

            $orderId = $this->generateOrderId($content);

        $terminalCreateData = [
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                   =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

            $this->fixtures->create(
                'terminal', $terminalCreateData);

            $this->fixtures->merchant->enableOffline();

            $this->fixtures->merchant->addFeatures(Feature\Constants::OTC_MERCHANT_CHALLAN);


            $input = [
                'customer_id' => $this->customer['id'],
                'receivers' => ['offline_challan'],
            ];

            $virtualAccount = $this->createVirtualAccountForOfflineOrder($orderId, $input);

            $requestContent = [
                'challan_no' => $virtualAccount['notes']['challan_number'],
                'client_code' => $terminalCreateData['gateway_merchant_id'],
                'identification_id' => '12345',

            ];

            $testData = $this->testData[__FUNCTION__];

            $testData['request']['content'] = $requestContent;

            $this->ba->hdfcOtcAuth();

            $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcertt'] = [self::CERT_HEADER];

             $this->startTest($testData);
        }catch (BadRequestValidationFailureException $e) {
            // Check if the exception message matches the expected message
            $this->assertEquals('The challan number must be between 5 and 40 characters.', $e->getMessage());
            return;
        }

        $this->fail('Expected exception BadRequestValidationFailureException was not thrown');

    }

    public function testValidateOfflineChallanForWrongMaxLength()
    {

     try {
         $content = [
             'amount' => 1000,
             'currency' => 'INR',
             'receipt' => 'rec1',
             'customer_additional_info' => [
                 'property_id' => '12345',
                 'property_value' => 'abc'
             ],
             'notes' => [
                 'challan_number' => 'wnyxuarmib26jd83wnyxuarmib26jd83wnyxuarmib26jd83',
             ],
         ];

         $orderId = $this->generateOrderId($content);

         $terminalCreateData = [
             'gateway' => 'offline_hdfc',
             'gateway_merchant_id' => '12345678',
             'gateway_secure_secret' => '12345',
             'offline' => 1,
             'merchant_id' => '10000000000000',
         ];

         $this->fixtures->create(
             'terminal', $terminalCreateData);

         $this->fixtures->merchant->enableOffline();

         $this->fixtures->merchant->addFeatures(Feature\Constants::OTC_MERCHANT_CHALLAN);


         $input = [
             'customer_id' => $this->customer['id'],
             'receivers' => ['offline_challan'],
         ];

         $virtualAccount = $this->createVirtualAccountForOfflineOrder($orderId, $input);

         $requestContent = [
             'challan_no' => $virtualAccount['notes']['challan_number'],
             'client_code' => $terminalCreateData['gateway_merchant_id'],
             'identification_id' => '12345',

         ];

         $testData = $this->testData[__FUNCTION__];

         $testData['request']['content'] = $requestContent;

         $this->ba->hdfcOtcAuth();

         $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

         $this->startTest($testData);
     }catch (BadRequestValidationFailureException $e) {
         // Check if the exception message matches the expected message
         $this->assertEquals('The challan number must be between 5 and 40 characters.', $e->getMessage());
         return;
     }

        $this->fail('Expected exception BadRequestValidationFailureException was not thrown');
    }

    public function testValidateOfflineChallan($functionalRequestContent = null,
                                               $functionalResponseContent = null, $sendChallanNumber = false)
    {
        $content = [
            'amount' => 1000,
            'currency' => 'INR',
            'receipt' => 'rec1',
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc'
            ],
        ];

        $orderId = $this->generateOrderId($content);

        $terminalCreteData = [
            'id'                       => 'Oc3KkqYe4LjpdA',
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                   =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreteData);

        $this->fixtures->merchant->enableOffline();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder($orderId, ['customer_id' => $this->customer['id'],'receivers' => ['offline_challan']]);

        $requestContent = [
            'challan_no' => $virtualAccount['receivers'][0]['challan_number'],
            'client_code' => $terminalCreteData['gateway_merchant_id'],
            'identification_id' => '12345',

        ];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $functionalRequestContent ?? $requestContent;

        $testData['request']['content']['challan_no'] = $virtualAccount['receivers'][0]['challan_number'];

        if($sendChallanNumber === true)
            $testData['request']['content']['challan_no'] = 'asdaf1q124314112';

        $responseContent = [
            'challan_no' => $virtualAccount['receivers'][0]['challan_number'],
            'expected_amount' => 1000,
            'currency' => 'INR',
            'partial_payment' => false,
            'status' => '0',
            'error' => null,
            'identification_id' => '12345'
        ];

        $testData['response']['content'] = $functionalResponseContent ?? $responseContent;

        if ($sendChallanNumber === false)
            $testData['response']['content']['challan_no'] = $virtualAccount['receivers'][0]['challan_number'];
        else
            $testData['response']['content']['challan_no'] = '';

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->startTest($testData);

    }

    public function testValidateOfflineChallanPresentInNotesWithExpirySetting()
    {
        $content = [
            'amount' => 1000,
            'currency' => 'INR',
            'receipt' => 'rec1',
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc'
            ],
            'notes' => [
                'challan_number' => 'aiynu34mmdkd9989rpwbhg61hg612q89rpw89rpw',
            ],
        ];

        $orderId = $this->generateOrderId($content);

        $terminalCreateData = [
            'id'                       => 'Oc3KkqYe4LjpdA',
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                   =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreateData);

        $this->fixtures->merchant->enableOffline();

        $this->fixtures->merchant->addFeatures(Feature\Constants::OTC_MERCHANT_CHALLAN);

        $input = [
            'customer_id' => $this->customer['id'],
            'receivers' => ['offline_challan'],
        ];

        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser['id']);

        $request = $this->testData['testVirtualAccountMerchantChallanExpirySetting'];

        $request['request']['content'][Constant::VA_EXPIRY_OFFSET_MERCHANT_CHALLAN] = 1550 ;

        $this->runRequestResponseFlow($request);

        $this->createVirtualAccountForOfflineOrder($orderId, $input);

        $this->ba->privateAuth();

        $response = $this->closeVirtualAccountsByCloseBy();

        $this->assertEquals($response['success'] , 0);

        $this->assertEquals($response['failure'] , 0);

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testValidateOfflineChallanPresentInNotesWithExpirySettingNegative()
    {
        $content = [
            'amount' => 1000,
            'currency' => 'INR',
            'receipt' => 'rec1',
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc'
            ],
            'notes' => [
                'challan_number' => 'aiynu34mmdkd9989rpwbhg61hg612q89rpw89rpw',
            ],
        ];
        $orderId = $this->generateOrderId($content);

        $terminalCreateData = [
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                   =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreateData);

        $this->fixtures->merchant->enableOffline();

        $this->fixtures->merchant->addFeatures(Feature\Constants::OTC_MERCHANT_CHALLAN);

        $input = [
            'customer_id' => $this->customer['id'],
            'receivers' => ['offline_challan'],
        ];

        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser['id']);

        $this->runRequestResponseFlow($this->testData['testVirtualAccountMerchantChallanExpirySetting']);

        $this->createVirtualAccountForOfflineOrder($orderId, $input);

        $nextFiveDays =  Carbon::today(Timezone::IST)->addDays(5)->timestamp;

        $fixedTime = (new Carbon())->timestamp($nextFiveDays);

        Carbon::setTestNow($fixedTime);

        $this->ba->privateAuth();

        $response = $this->closeVirtualAccountsByCloseBy();

        $this->assertEquals($response['success'] , 1);

        $this->assertEquals($response['failure'] , 0);

        $testData = $this->testData[__FUNCTION__];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->startTest($testData);

        Carbon::setTestNow();
    }

    public function testOfflinePaymentCreditWithMerchantChallan()
    {
        $this->testValidateOfflineChallanPresentInNotes();

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotes']['request']['content']['challan_no'];

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

        $paymentData = [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode' => 'cash',
            'status' => 'processed',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'description' => 'Received INR 1000 through Cheque',
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challanNumber,
                'status' => 0
            ],
        ];

        $this->startTest();

        $payment = $this->getDbLastEntity('payment');


        //Verifying the mock response of smart routing request by terminal id

        $this->assertEquals('Oc3KkqYe4LjpdA', $payment['terminal_id']);

    }

    public function testOfflinePaymentCreditWithMerchantChallanForemptyPaymentInstrumentDetailsAndPaymentDetailsForFileSource()
    {
        $this->testValidateOfflineChallanPresentInNotes();

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotes']['request']['content']['challan_no'];

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

        $paymentData = [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode' => 'cash',
            'status' => 'processed',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'description' => 'Received INR 1000 through Cheque',
            "payment_instrument_details" => "",
            "payer_details" => "",
            // "source" => "callback",
            "source" => "file",
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challanNumber,
                'status' => 0
            ],
        ];

        $this->startTest();

        $offlinePayment = $this->getDbLastEntityPublic('offline_payment');

        //Verifying the mock response of offline payment
        print_r($offlinePayment);
        $this->assertEquals('{}',  $offlinePayment['payment_instrument_details']);
        $this->assertEquals('{}',  $offlinePayment['payer_details']);

    }

    public function testOfflinePaymentCreditWithMerchantChallanForemptyPaymentInstrumentDetailsAndPaymentDetailsForSourceCallback()
    {
        $this->testValidateOfflineChallanPresentInNotes();

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotes']['request']['content']['challan_no'];

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

        $paymentData = [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode' => 'cash',
            'status' => 'processed',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'description' => 'Received INR 1000 through Cheque',
            "payment_instrument_details" => "",
            "payer_details" => "",
            "source" => "callback",
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challanNumber,
                'status' => 0
            ],
        ];

        $this->startTest();

        $offlinePayment = $this->getDbLastEntityPublic('offline_payment');

        //Verifying the mock response of offline payment

        $this->assertEquals('""',  $offlinePayment['payment_instrument_details']);
        $this->assertEquals('""',  $offlinePayment['payer_details']);

    }

    public function testValidateChallanWithDuplicateOfflinePaymentCredit()
    {
        $this->testOfflinePaymentCreditWithMerchantChallan();

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->startTest($this->testData);
    }

    public function testOfflinePaymentCreditWithoutMockResponseOfSmartRouting()
    {
        $this->app['config']->set('applications.smart_routing.mock', false);

        $this->testValidateOfflineChallanPresentInNotes();

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotes']['request']['content']['challan_no'];

        $paymentData = [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode' => 'cash',
            'status' => 'processed',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'description' => 'Received INR 1000 through Cash',
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challanNumber,
                'status' => 1,
                'error' => [
                    'code' => 'BAD_REQ_ER',
                    'description' => 'Terminal should not be null',
                    'field' => '',
                    'source' => 'business',
                    'step' => null,
                    'reason' => 'Terminal should not be null',
                    'metadata' => []
                ]
            ],
        ];

        $this->startTest();

    }

    public function testOfflinePaymentCreditWithOtherBankChequeByApi()
    {
        $this->testValidateOfflineChallan();

        $offlineChallans = $this->getDbLastEntityPublic('offline_challan');

        $challanNumber = $offlineChallans['challan_number'];

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

        $paymentData = [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode'   => 'other bank cheque',
            'status' => 'processed',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  => '12345678',
            'source'       =>  'callback',
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] = [
            'content' => [
                'challan_no' => $challanNumber,
                'status' => 1,
                'error' => [
                    'code' => 'BAD_REQ_ER',
                    'description' =>  'Invalid Mode: othcheque',
                    'field' => '',
                    'source' =>  'business',
                    'step' => null,
                    'reason' => 'Invalid Mode: othcheque',
                    'metadata' => [],
                ],
            ],
        ];

        $this->startTest();

    }

    public function testOfflinePaymentCreditWithOtherBankChequeByBatch()
    {
        $this->testValidateOfflineChallan();

        $offlineChallans = $this->getDbLastEntityPublic('offline_challan');

        $challanNumber = $offlineChallans['challan_number'];

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

        $paymentData =  [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode'   => 'other bank cheque',
            'status' => 'paid',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  => '12345678',
            'source'       =>  'file',
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challanNumber,
                'status' => 0
            ],
        ];

        $this->startTest();

        $offlinePayment = $this->getDbLastEntityPublic('offline_payment');

        $payment = $this->getDbLastEntity('payment');

        //Verifying the mock response of smart routing request by terminal id

        $this->assertEquals('Oc3KkqYe4LjpdA', $payment['terminal_id']);

        // Verifying new added  column SOURCE in offline_payments entity.

        $this->assertEquals('captured', $offlinePayment['status']);

        $this->assertEquals('file', $offlinePayment['source']);

    }

    public function testDuplicateOfflinePaymentCreditByBatch()
    {
        $this->testOfflinePaymentCreditWithMerchantChallan();

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotes']['request']['content']['challan_no'];

        $paymentData =  [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode'   => 'cash',
            'status' => 'paid',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  => '12345678',
            'source'       =>  'file'
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challanNumber,
                'status' => 1,
                'error' => [
                    'code' =>  "BAD_REQ_ER",
                    'description' => "OFFLINE_PAYMENT_DUPLICATE_REQUEST",
                    'reason' => "OFFLINE_PAYMENT_DUPLICATE_REQUEST",
                ]
            ],
        ];

        $this->startTest();

    }

    public function testFailedOfflinePaymentCreditWithOtherBankChequeByBatch()
    {
        $this->testValidateOfflineChallanPresentInNotes();

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotes']['request']['content']['challan_no'];

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

        $paymentData = [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode'   => 'other bank cheque',
            'status' => 'return',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  => '12345678',
            'source'       =>  'file',
        ];

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'challan_no' => $challanNumber,
                'status' => 1,
                'error' => [
                    'code' => 'BAD_REQ_ER',
                    'description' =>  'Challan marked as failed from the bank',
                    'field' => '',
                    'source' => 'business',
                    'step' => null,
                    'reason' =>  'Challan marked as failed from the bank',
                    'metadata' => [],
                ],
            ],
        ];

        $this->startTest();

        $offlinePayment = $this->getDbLastEntityPublic('offline_payment');

        $payment = $this->getDbLastEntity('payment');

        //Verifying the mock response of smart routing request by terminal id

        $this->assertEquals('Oc3KkqYe4LjpdA', $payment['terminal_id']);

        // Verifying newly added column SOURCE in offline_payments entity.

        $this->assertEquals('failed', $offlinePayment['status']);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('file', $offlinePayment['source']);

    }

    public function testOfflinePaymentCreditBeforeExpiryForMerchantChallanWithExpirySuccessByBatch()
    {

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotesWithExpirySetting']['request']['content']['challan_no'];

        $requestContent = [
            'challan_no' => $challanNumber,
            'amount' => 1000,
            'mode' => 'other bank dd',
            'status' => 'paid',
            'payment_date' => '01-apr-2025',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'source' => 'file'
        ];

        $responseContent = [
            'challan_no'    => $challanNumber,
            'status'        => 0,
            'error'         => null
        ];

        $this->testOfflinePaymentCreditBeforeExpiryForMerchantChallanWithExpirySuccess($requestContent, $responseContent);

    }

    public function testOfflinePaymentCreditBeforeExpiryForMerchantChallanWithPaidStatusByBatch()
    {

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotesWithExpirySetting']['request']['content']['challan_no'];

        $requestContent = [
            'challan_no' => $challanNumber,
            'amount' => 1000,
            'mode' => 'other bank dd',
            'status' => 'return',
            'payment_date' => '01-apr-2025',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'source' => 'file'
        ];

        $responseContent = [
            'challan_no' => $challanNumber,
            'status' => 1,
            'error' => [
                'code' => 'BAD_REQ_ER',
                'description' =>  'Challan marked as failed from the bank',
                'field' => '',
                'source' => 'business',
                'step' => null,
                'reason' =>  'Challan marked as failed from the bank',
                'metadata' => [],
            ],
        ];

        $this->testOfflinePaymentCreditBeforeExpiryForMerchantChallanWithExpirySuccess($requestContent, $responseContent);

        $payment = $this->getDbLastEntity('payment');

        $offlinePayment = $this->getDbLastEntity('offline_payment');

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('failed', $offlinePayment['status']);

    }

    public function testOfflinePaymentCreditBeforeExpiryForMerchantChallanWithExpirySuccess($functionalPaymentRequest = null, $functionalPaymentResponse = null)
    {

        $this->testValidateOfflineChallanPresentInNotesWithExpirySetting();

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotesWithExpirySetting']['request']['content']['challan_no'];

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

        $paymentData = [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode' => 'cash',
            'status' => 'processed',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'source'    => 'callback'
        ];

        $paymentData = $functionalPaymentRequest ?? $paymentData;

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response']['content']=   $functionalPaymentResponse ??
             [
                'challan_no' => $challanNumber,
                'status' => 0
             ];

        $this->startTest();

        $payment = $this->getDbLastEntity('payment');

        //Verifying the mock response of smart routing request by terminal id

        $this->assertEquals('Oc3KkqYe4LjpdA', $payment['terminal_id']);

    }

    public function testOfflinePaymentCreditAfterChallanExpiryWithFailureByBatch(){

        $requestContent = [
            'amount' => 1000,
            'mode' => 'other bank dd',
            'status' => 'return',
            'payment_date' => '01-apr-2025',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'source' => 'file'
        ];

        $this->testOfflinePaymentCreditAfterChallanExpiryWithFailure($requestContent);

    }
    public function testOfflinePaymentCreditAfterChallanExpiryWithFailure($functionalPaymentRequest = null,
                                                                                $functionalPaymentResponse = null, $challanNo = null)
    {

        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser['id']);

        $request = $this->testData['testVirtualAccountMerchantChallanExpirySetting'];

        $request['request']['content'][Constant::VA_EXPIRY_OFFSET_MERCHANT_CHALLAN] = 2089;

        $this->runRequestResponseFlow($request);

        $this->testValidateOfflineChallan();

        $offlineChallans = $this->getDbLastEntityPublic('offline_challan');

        $challanNumber = $offlineChallans['challan_number'];

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

        $paymentData = [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode' => 'cash',
            'status' => 'processed',
            'payment_date' => '01-sep-2024',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'source'       => 'callback'
        ];

        $paymentData = $functionalPaymentRequest ?? $paymentData;

        $paymentData['challan_no'] = $challanNo ?? $challanNumber;

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $nextTenDays =  Carbon::today(Timezone::IST)->addDays(10)->timestamp;

        $fixedTime = (new Carbon())->timestamp($nextTenDays);

        Carbon::setTestNow($fixedTime);

        $this->ba->privateAuth();

        $response = $this->closeVirtualAccountsByCloseBy();

        $this->assertEquals($response['success'] , 1);

        $this->assertEquals($response['failure'] , 0);

        $this->getDbEntityById('virtual_account', $offlineChallans['virtual_account_id']);

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->testData[__FUNCTION__]['response']['content'] = $functionalPaymentResponse ??  [
                'challan_no' => $challanNumber,
                'status' => 1,
                'error' => [
                    'code' => 'BAD_REQ_ER',
                    'description' =>  'Challan was expired on receipt of API push from the bank',
                    'field' => '',
                    'source' => 'business',
                    'step' => null,
                    'reason' =>  'Challan was expired on receipt of API push from the bank',
                    'metadata' => [],
                ],
        ];

        $this->startTest();

        //Verifying the mock response of smart routing request by terminal id

        $payment = $this->getDbLastEntity('payment');

        $offlinePayment = $this->getDbLastEntity('offline_payment');

        $this->assertEquals('Oc3KkqYe4LjpdA', $payment['terminal_id']);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('failed', $offlinePayment['status']);

        Carbon::setTestNow();
    }

    public function testOfflinePaymentCreditForMerchantChallanWithExpiryFailureByBatch()
    {
        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotesWithExpirySettingNegative']['request']['content']['challan_no'];

        $requestContent = [
            'challan_no' => $challanNumber,
            'amount' => 1000,
            'mode' => 'other bank dd',
            'status' => 'return',
            'payment_date' => '01-feb-2025',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'source' => 'file'
        ];

        $responseContent = [
            'challan_no' => $challanNumber,
            'status' => 1,
            'error' => [
                'code'        => 'BAD_REQ_ER',
                'description' => OfflinePayment\StatusCode::CHALLAN_NOT_VALIDATED,
                'source'      =>  'business',
                'reason'      =>  OfflinePayment\StatusCode::CHALLAN_NOT_VALIDATED
            ],
        ];

        $this->testOfflinePaymentCreditForMerchantChallanWithExpiryFailure($requestContent, $responseContent);

    }
    public function testOfflinePaymentCreditForMerchantChallanWithExpiryFailure($functionalPaymentRequest = null, $functionalPaymentResponse = null)
    {
        $this->testValidateOfflineChallanPresentInNotesWithExpirySettingNegative();

        $challanNumber = $this->testData['testValidateOfflineChallanPresentInNotesWithExpirySettingNegative']['request']['content']['challan_no'];

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

        $paymentData = [
            'challan_no' =>  $challanNumber,
            'amount' => 1000,
            'mode' => 'cash',
            'status' => 'processed',
            'payment_date' => '01-apr-2024',
            'payment_time' => '21:30:45',
            'client_code'  =>  '12345678',
            'source'    => 'callback'
        ];

        $paymentData = $functionalPaymentRequest ?? $paymentData;

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/credit/ecollect/offline',
            'method'  => 'post',
            'content' => $paymentData,
        ];

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $responseContent = [
            'challan_no' => $challanNumber,
            'status' => 1,
            'error' => [
                'code'        => 'BAD_REQ_ER',
                'description' => OfflinePayment\StatusCode::CHALLAN_NOT_VALIDATED,
                'source'      =>  'business',
                'reason'      =>  OfflinePayment\StatusCode::CHALLAN_NOT_VALIDATED
            ],
        ];

        $this->testData[__FUNCTION__]['response']['content'] = $functionalPaymentResponse ?? $responseContent;

        $this->startTest();
    }

    public function testValidateOfflineChallanPresentInNotes()
    {
        $content = [
            'amount' => 1000,
            'currency' => 'INR',
            'receipt' => 'rec1',
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc'
            ],
            'notes' => [
                'challan_number' => 'aiynuvrpwbhg6161uvrpwbrpwbhg612quvrpwbhg',
            ],
        ];
        $orderId = $this->generateOrderId($content);

        $terminalCreateData = [
            'id'                       => 'Oc3KkqYe4LjpdA',
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                   =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreateData);

        $this->fixtures->merchant->enableOffline();

        $this->fixtures->merchant->addFeatures(Feature\Constants::OTC_MERCHANT_CHALLAN);

        $input = [
            'customer_id' => $this->customer['id'],
            'receivers' => ['offline_challan'],
        ];

        $this->createVirtualAccountForOfflineOrder($orderId, $input);

        $this->ba->hdfcOtcAuth();

        $this->testData[__FUNCTION__]['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testValidateOfflineChallanNotPresentInNotes()
    {
        try
        {
            $content = [
                'amount' => 1000,
                'currency' => 'INR',
                'receipt' => 'rec1',
                'customer_additional_info' => [
                    'property_id' => '12345',
                    'property_value' => 'abc'
                ]
            ];

            $orderId = $this->generateOrderId($content);

            $terminalCreateData = [
                'gateway'                  => 'offline_hdfc',
                'gateway_merchant_id'      => '12345678',
                'gateway_secure_secret'    => '12345',
                'offline'                   =>  1,
                'merchant_id'              =>  '10000000000000',
            ];

            $this->fixtures->create(
                'terminal', $terminalCreateData);

            $this->fixtures->merchant->enableOffline();

            $this->fixtures->merchant->addFeatures(Feature\Constants::OTC_MERCHANT_CHALLAN);

            $input = [
                'customer_id' => $this->customer['id'],
                'receivers' => ['offline_challan'],
            ];

            $this->createVirtualAccountForOfflineOrder($orderId, $input);
        }
        catch(BadRequestException $e)
        {
            $this->assertEquals(PublicErrorDescription::BAD_REQUEST_CHALLAN_NOT_FOUND_IN_NOTES, $e->getMessage());
        }
    }

    public function testValidateOfflineChallanPresentInNotesWithDuplicateRequest(){

        $content = [
            'amount' => 1000,
            'currency' => 'INR',
            'receipt' => 'rec1',
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc'
            ],
            'notes' => [
                'challan_number' => 'bG7jLw8TzQ1xVrKd9P3eZmB2Ny6HfJ4uAoXpRsMh',
            ],
        ];

        $terminalCreateData = [
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                   =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $input = [
            'customer_id' => $this->customer['id'],
            'receivers' => ['offline_challan'],
        ];

        $this->fixtures->create(
            'terminal', $terminalCreateData);

        $this->fixtures->merchant->enableOffline();

        $this->fixtures->merchant->addFeatures(Feature\Constants::OTC_MERCHANT_CHALLAN);

        $orderId1 = $this->generateOrderId($content);

        $this->createVirtualAccountForOfflineOrder($orderId1, $input);

        $orderId2 = $this->generateOrderId($content);

        try
        {
            //Creating the OfflineChallan entity again on the same challan number.
            $this->createVirtualAccountForOfflineOrder($orderId2, $input);
        }
        catch(BadRequestException $e)
        {
            $this->assertEquals(PublicErrorDescription::BAD_REQUEST_OFFLINE_CHALLAN_DUPLICATE_REQUEST, $e->getMessage());
        }
    }

    public function testValidateOfflineChallanWithoutCert()
    {
        $content = [
            'amount' => 1000,
            'currency' => 'INR',
            'receipt' => 'rec1',
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc'
            ],
        ];

        $orderId = $this->generateOrderId($content);

        $terminalCreteData = [
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                   =>  1,
            'merchant_id'              =>  '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreteData);

        $this->fixtures->merchant->enableOffline();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder($orderId, ['customer_id' => $this->customer['id'],'receivers' => ['offline_challan']]);

        $requestContent = [
            'challan_no' => $virtualAccount['receivers'][0]['challan_number'],
            'client_code' => $terminalCreteData['gateway_merchant_id'],
            'identification_id' => '12345',

        ];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $functionalRequestContent ?? $requestContent;

        $testData['request']['content']['challan_no'] = $virtualAccount['receivers'][0]['challan_number'];

        $testData['request']['content']['challan_no'] = 'asdaf1q124314112';

        $this->ba->hdfcOtcAuth();

        $res = $this->startTest($testData);
    }

    public function testValidateOfflineChallanWithInvalidVirtualAccount()
    {
      try {

          $orderId = " ";

          $terminalCreateData = [
              'gateway' => 'offline_hdfc',
              'gateway_merchant_id' => '12345678',
              'gateway_secure_secret' => '12345',
              'offline' => 1,
              'merchant_id' => '10000000000000',
          ];

          $this->fixtures->create(
              'terminal', $terminalCreateData);

          $this->fixtures->merchant->enableOffline();

          //sending null order id
          $virtualAccount = $this->createVirtualAccountForOfflineOrder($orderId, ['customer_id' => $this->customer['id'], 'receivers' => ['offline_challan']]);

          $requestContent = [
              'challan_no' => $virtualAccount['receivers'][0]['challan_number'],
              'client_code' => $terminalCreateData['gateway_merchant_id'],
              'identification_id' => '12345',

          ];

          $testData = $this->testData[__FUNCTION__];

          $testData['request']['content'] = $functionalRequestContent ?? $requestContent;

          $testData['request']['content']['challan_no'] = $virtualAccount['receivers'][0]['challan_number'];

          $testData['request']['content']['challan_no'] = 'asdaf1q124314112';

          $this->ba->hdfcOtcAuth();

          $res = $this->startTest($testData);
      }catch(BadRequestException $exp){
          $this->assertEquals('The id provided does not exist', $exp->getMessage());
          return;
      }

        $this->fail('Expected exception BadRequestException was not thrown');
    }

    public function testValidateOfflineChallanClientCodeNotFound()
    {
        $requestContent = [
            'client_code' => '12345679',
            'identification_id' => '12345',
            'expected_amount' => 1000
        ];

        $responseContent = [
            'expected_amount' => 0,
            'currency' => 'INR',
            'partial_payment' => '',
            'status' => '1',
            'identification_id' => '',
            'error' => [
                'code' => 'BAD_REQ_ER',
                'field' => 'client_code',
                'source' => 'business',
                'step' => null,
                'reason' => 'CLIENT_CODE_NOT_FOUND',
                'metadata' => []
            ]
        ];

        $this->testValidateOfflineChallan($requestContent, $responseContent);
    }

    public function testValidateOfflineChallanAmountMismatch()
    {
        $requestContent = [
            'client_code' => '12345678',
            'identification_id' => '12345',
            'expected_amount' => 10000
        ];

        $responseContent = [
            'expected_amount' => 10000,
            'currency' => 'INR',
            'partial_payment' => false,
            'status' => '1',
            'identification_id' => '12345',
            'error' => [
                'code' => 'BAD_REQ_ER',
                'field' => 'amount',
                'source' => 'business',
                'step' => null,
                'reason' => 'AMOUNT_MISMATCH',
                'metadata' => []
            ]
        ];

        $this->testValidateOfflineChallan($requestContent, $responseContent, false);

    }

    public function testValidateOfflineChallanNotFound()
    {
        $requestContent = [
            'client_code' => '12345678',
            'identification_id' => '12345',
            'expected_amount' => 1000
        ];

        $responseContent = [
            'expected_amount' => 0,
            'currency' => 'INR',
            'partial_payment' => '',
            'status' => '1',
            'identification_id' => '',
            'error' => [
                'code' => 'BAD_REQ_ER',
                'field' => 'challan_no',
                'source' => 'business',
                'step' => null,
                'reason' => 'CHALLAN_NOT_FOUND',
                'metadata' => []
            ]
        ];

        $this->testValidateOfflineChallan($requestContent, $responseContent, true);

    }

    public function testValidateOfflineIdentificationIdNotFound()
    {
        $requestContent = [
            'client_code' => '12345678',
            'identification_id' => '12346',
            'expected_amount' => 1000
        ];

        $responseContent = [
            'expected_amount' => 0,
            'currency' => 'INR',
            'partial_payment' => '',
            'status' => '1',
            'identification_id' => '',
            'error' => [
                'code' => 'BAD_REQ_ER',
                'field' => 'identification_id',
                'source' => 'business',
                'step' => null,
                'reason' => 'IDENTIFICATION_ID_NOT_FOUND',
                'metadata' => []
            ]
        ];

        $this->testValidateOfflineChallan($requestContent, $responseContent, false);

    }

    public function testValidateOfflineChallanValidationFailure()
    {
        $requestContent = [
            //'client_code' => '12345678',
            'identification_id' => '12346',
            'expected_amount' => 1000
        ];

        $responseContent = [
            'expected_amount' => 0,
            'currency' => 'INR',
            'partial_payment' => '',
            'status' => '1',
            'identification_id' => '',
            'error' => [
                'code' => 'BAD_REQ_ER',
                'field' => '',
                'source' => 'business',
                'step' => null,
                'reason' => 'VALIDATION_FAILURE',
                'metadata' => []
            ]
        ];

        $this->testValidateOfflineChallan($requestContent, $responseContent, true);

    }

    public function testValidateOfflineChallanWithPartialPayment($functionalRequestContent = null,
                                               $functionalResponseContent = null, $sendChallanNumber = false)
    {
        $content = [
            'amount' => 1000,
            'currency' => 'INR',
            'receipt' => 'rec1',
            'partial_payment' => true,
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc'
            ],
        ];

        $orderId = $this->generateOrderId($content);

        $terminalCreteData = [
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                  =>  1,
            'merchant_id'              => '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreteData);

        $this->fixtures->merchant->enableOffline();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder($orderId, ['customer_id' => $this->customer['id'],'receivers' => ['offline_challan']]);

        $requestContent = [
            'challan_no' => $virtualAccount['receivers'][0]['challan_number'],
            'client_code' => $terminalCreteData['gateway_merchant_id'],
            'identification_id' => '12345',
            'expected_amount'   => 100
        ];

        $testData = $this->testData['testValidateOfflineChallan'];

        $testData['request']['content'] = $functionalRequestContent ?? $requestContent;

        $testData['request']['content']['challan_no'] = $virtualAccount['receivers'][0]['challan_number'];

        if($sendChallanNumber === true)
            $testData['request']['content']['challan_no'] = 'asdaf1q124314112';

        $responseContent = [
            'challan_no' => $virtualAccount['receivers'][0]['challan_number'],
            'expected_amount' => 100,
            'currency' => 'INR',
            'partial_payment' => true,
            'status' => '0',
            'error' => null,
            'identification_id' => '12345'
        ];

        $testData['response']['content'] = $functionalResponseContent ?? $responseContent;

        if ($sendChallanNumber === false)
            $testData['response']['content']['challan_no'] = $virtualAccount['receivers'][0]['challan_number'];
        else
            $testData['response']['content']['challan_no'] = '';

        $this->ba->hdfcOtcAuth();

        $testData['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->startTest($testData);

    }

    public function testValidateOfflineChallanWithPartialPaymentAmountMismatch($functionalRequestContent = null,
                                                                 $functionalResponseContent = null, $sendChallanNumber = false)
    {
        $content = [
            'amount' => 1000,
            'currency' => 'INR',
            'receipt' => 'rec1',
            'partial_payment' => true,
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc'
            ],
        ];

        $orderId = $this->generateOrderId($content);

        $terminalCreteData = [
            'gateway'                  => 'offline_hdfc',
            'gateway_merchant_id'      => '12345678',
            'gateway_secure_secret'    => '12345',
            'offline'                  =>  1,
            'merchant_id'              => '10000000000000',
        ];

        $this->fixtures->create(
            'terminal', $terminalCreteData);

        $this->fixtures->merchant->enableOffline();

        $virtualAccount = $this->createVirtualAccountForOfflineOrder($orderId, ['customer_id' => $this->customer['id'],'receivers' => ['offline_challan']]);

        $requestContent = [
            'challan_no' => $virtualAccount['receivers'][0]['challan_number'],
            'client_code' => $terminalCreteData['gateway_merchant_id'],
            'identification_id' => '12345',
            'expected_amount'   => 1100
        ];

        $testData = $this->testData['testValidateOfflineChallan'];

        $testData['request']['content'] = $functionalRequestContent ?? $requestContent;

        $testData['request']['content']['challan_no'] = $virtualAccount['receivers'][0]['challan_number'];

        if($sendChallanNumber === true)
            $testData['request']['content']['challan_no'] = 'asdaf1q124314112';

        $responseContent = [
            'challan_no' => $virtualAccount['receivers'][0]['challan_number'],
            'expected_amount' => 1100,
            'currency' => 'INR',
            'partial_payment' => true,
            'status' => '1',
            'identification_id' => '12345',
            'error' => [
                'code' => 'BAD_REQ_ER',
                'field' => 'amount',
                'source' => 'business',
                'step' => null,
                'reason' => 'AMOUNT_MISMATCH',
                'metadata' => []
            ]
        ];

        $testData['response']['content'] = $functionalResponseContent ?? $responseContent;

        if ($sendChallanNumber === false)
            $testData['response']['content']['challan_no'] = $virtualAccount['receivers'][0]['challan_number'];
        else
            $testData['response']['content']['challan_no'] = '';

        $this->ba->hdfcOtcAuth();

        $testData['request']['headers']['X-Amzn-Mtls-Clientcert'] = [self::CERT_HEADER];

        $this->startTest($testData);

    }

    public function testVpaValidationWithDuplicateVpaAddressAndDifferentEntityType()
    {
        $response = $this->createVirtualAccount([], false, null, null, true, null);

        $vpa = $this->getLastEntity('vpa', true);

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->create('vpa', [
            'username'    => $vpa['username'],
            'handle'      => $vpa['handle'],
            'entity_type' => 'contact',
            'entity_id'   => '1000000contact',
        ]);

        $vpa = $this->getLastEntity('vpa', true);

        $input          = '<XML><Source>ICICI-EAZYPAY</Source><SubscriberId>' . explode('.', $vpa['username'])[1] . '</SubscriberId><TxnId>YBL457b50e1fa8b452ab996560a0c9bc8be</TxnId></XML>';
        $virtualUpiRoot = explode('.', $this->vpaTerminal['virtual_upi_root'])[0];

        $rawResponse = $this->ecollectValidateVirtualAccountVpa('upi_icici', $virtualUpiRoot, $input);
        $response    = (array) simplexml_load_string($rawResponse->content());

        $this->assertEquals($response['ActCode'], '0');
        $this->assertEquals($response['Message'], 'VALID');
        $this->assertEquals($response['CustName'], 'Test Merchant');
        $this->assertEquals($response['TxnId'], 'YBL457b50e1fa8b452ab996560a0c9bc8be');
    }

    public function testCreateVirtualAccountWithTerminalCaching()
    {
        $this->enableRazorXTreatmentForCaching();

        $cacheKey = VirtualAccount\Constant::TERMINAL_CACHE_PREFIX . '_' . '10000000000000';

        $store = $this->app['cache'];

        $pickedFromTerminalCache = false;

        \Cache::shouldReceive('driver')
              ->andReturnUsing(function($driver = null) use ($store) {
                  return $store;
              });

        \Cache::shouldReceive('get')
              ->andReturnUsing(function($key, $default = null) use ($cacheKey, $store, &$pickedFromTerminalCache) {
                  if ($key === $cacheKey)
                  {
                      $pickedFromTerminalCache = true;

                      return [
                          [
                              'id'                     => 'SHRDBANKACC3DS',
                              Terminal\Entity::GATEWAY => 'bt_yesbank',
                              'merchant_id'            => '100000Razorpay',
                              'org_id'                 => '100000razorpay',
                              'procurer'               => 'razorpay',
                              'used_count'             => 0,
                              'used'                   => 0,
                              'gateway_merchant_id'    => '111222',
                              'gateway_merchant_id2'   => '00',
                              'gateway_terminal_id'    => 'quis'
                          ],
                          [
                              'id'                     => 'SHRDBANKACC3DS',
                              Terminal\Entity::GATEWAY => 'bt_dashboard',
                              'merchant_id'            => '100000Razorpay',
                              'org_id'                 => '100000razorpay',
                              'procurer'               => 'razorpay',
                              'used_count'             => 0,
                              'used'                   => 0,
                              'gateway_merchant_id'    => '111222',
                              'gateway_merchant_id2'   => '00',
                              'gateway_terminal_id'    => 'quis'
                          ]
                      ];
                  }

                  return $store->get($key, $default);
              })
              ->shouldReceive('store')
              ->withAnyArgs()
              ->andReturn($store)
              ->shouldReceive('put')
              ->withAnyArgs()
              ->andReturn($store);

        \Cache::shouldReceive('remember')
            ->withAnyArgs()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });


        $response = $this->createVirtualAccount();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->verifyEntityOrigin($response['id'], 'merchant', '10000000000000');

        $this->assertTrue($pickedFromTerminalCache);
    }

    protected function enableRazorXTreatmentForCaching()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
               ->andReturnUsing(function(string $id, string $featureFlag, string $mode) {
                   if ($featureFlag === (RazorxTreatment::SMART_COLLECT_TERMINAL_CACHING))
                   {
                       return 'on';
                   }

                   return 'control';
               });
    }

    public function testCreateVirtualAccountWithDefaultExpiry()
    {
        $this->fixtures->create('feature', [
            'name' => Feature\Constants::SET_VA_DEFAULT_EXPIRY,
            'entity_id' => '10000000000035',
            'entity_type' => 'merchant',
        ]);

        $key = $this->fixtures->create('key', ['merchant_id' => '10000000000035']);

        $order = $this->fixtures->create('order', ['merchant_id' => '10000000000035']);

        $this->ba->publicAuth($key->getPublicId());

        $response = $this->createVirtualAccountForOrder($order);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals($order->getAmountDue(), $virtualAccount['amount_expected']);
        $this->assertEquals(Status::ACTIVE, $virtualAccount['status']);
        $this->assertEquals($order->getId(), $virtualAccount['entity_id']);
        $this->assertEquals('order', $virtualAccount['entity_type']);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals($virtualAccount['id'], 'va_' . $bankAccount['entity_id']);

        $closeBy = Carbon::now(Timezone::IST)->addMinutes(Constant::HDFC_LIVE_VA_OFFSET_DEFAULT_CLOSE_BY_MINUTES)->toDateString();

        $vaCloseByDate = Carbon::createFromTimestamp($virtualAccount['close_by'], Timezone::IST)->toDateString();

        $this->assertEquals($closeBy, $vaCloseByDate);

        // Test create hdfc life VA with merchant level VA expiry setting
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000035');

        $this->ba->proxyAuth('rzp_test_10000000000035', $merchantUser['id']);

        //setting expiry to 24 hours
        $this->runRequestResponseFlow($this->testData['testUpdateVirtualAccountExpirySettingForHDFCLife']);

        $order = $this->fixtures->create('order', ['merchant_id' => '10000000000035']);

        $this->ba->publicAuth($key->getPublicId());

        $this->createVirtualAccountForOrder($order);

        $expiryOffset = $this->testData['testUpdateVirtualAccountExpirySettingForHDFCLife']['request']['content']['va_expiry_offset'];

        $virtualAccount = $this->getLastEntity('virtual_account', true);

        $closeBy = Carbon::now(Timezone::IST)->addMinutes($expiryOffset)->toDateString();

        $vaCloseByDate = Carbon::createFromTimestamp($virtualAccount['close_by'], Timezone::IST)->toDateString();

        $this->assertEquals($closeBy, $vaCloseByDate);
    }

    /*
     * Setting expiry to 10 hours
     */
    public function testUpdateVirtualAccountExpirySettingForHDFCLife()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000035');

        $this->ba->proxyAuth('rzp_test_10000000000035', $merchantUser['id']);

        $this->startTest();
    }

    public function testFetchWithQueryFieldIsSupported()
    {
        $this->startTest();
    }

    public function testFetchWithUnknownParamShouldFail()
    {
        $this->startTest();
    }

    public function testFetchWithQueryFieldMinCharacterValidation()
    {
        $this->startTest();
    }

    public function testFetchWithQueryFieldMaxCharacterValidation()
    {
        $this->startTest();
    }

    public function testVirtualAccountCreatedEventWithTransactionIsolation()
    {
        $this->createPartnerAndSubmerchantMapping();
        $this->mockSplitzTreatmentBulkRequest([['variant' => ['name' => 'enable']]]);

        $expectedEvent = $this->testData['testWebhookVirtualAccountCreatedForVpa']['event'];
        $this->expectWebhookEventWithContext(
            'virtual_account.created',
            ['entity_type' => 'virtual_account', 'event_type' => 'partnership'],
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $this->assertArrayNotHasKey('context', $event);
            });

        $this->createVirtualAccount([], false, null, null, true,'virtualVpa');
    }

    public function testVirtualAccountCreditedEventWithTransactionIsolation()
    {
        $this->createPartnerAndSubmerchantMapping();
        $this->mockSplitzTreatmentBulkRequest([['variant' => ['name' => 'enable']]]);
        $virtualAccount = $this->createVirtualAccount();

        $context = [
            'id' => explode("_", $virtualAccount['id'])[1],
            'entity_type' => 'virtual_account',
            'event_type' => 'partnership',
        ];

        $expectedEvent = $this->testData['testWebhookVirtualAccountCredited']['event'];
        $this->expectWebhookEventWithContext(
            'virtual_account.credited',
            $context,
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $this->assertArrayNotHasKey('context', $event);
            }
        );

        $this->payVirtualAccount($virtualAccount['id']);
    }

    public function testVirtualAccountClosedEventWithTransactionIsolation()
    {
        $this->createPartnerAndSubmerchantMapping();
        $this->mockSplitzTreatmentBulkRequest([['variant' => ['name' => 'enable']]]);
        $virtualAccount = $this->createVirtualAccount();

        $context = [
            'id' => explode("_", $virtualAccount['id'])[1],
            'entity_type' => 'virtual_account',
            'event_type' => 'partnership',
        ];

        $expectedEvent = $this->testData['testWebhookVirtualAccountClosed']['event'];
        $this->expectWebhookEventWithContext(
            'virtual_account.closed',
            $context,
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $this->assertArrayNotHasKey('context', $event);
            });

        $this->closeVirtualAccount($virtualAccount['id']);
    }

    public function testFetchMultipleVirtualAccountForCollectX()
    {
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::WHITE_LABELLED_INVOICES,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature\Constants::COLLECTX_ENABLED,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->createVirtualAccount(['name' => 'First VA']);
        $this->createVirtualAccount(['name' => 'Second VA']);

        $response = $this->fetchVirtualAccounts();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    protected function mockSplitzTreatmentBulkRequest($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('bulkCallsToSplitz')
            ->andReturn($output);
    }

    public function testVirtualAccountClose_CollectxVA()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

        $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true,
            ifscCode: 'YESB0CMSNOC');

        $vpaEntity = $this->fixtures->create('vpa', [
            'id' => 'J9embXZB7QAute',
            'username'    => 'amitm',
            'handle'      => 'upi',
            'entity_type' => 'virtual_account',
            'entity_id'   => substr($this->virtualAccount->getPublicId(), 3),
        ]);

        $this->virtualAccount->vpa()->associate($vpaEntity);
        $this->virtualAccount->save();

        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts/'.$this->virtualAccount->getPublicId().'/close'
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        self::assertEquals('closed', $response['status']);

        $va = $this->getDbLastEntity('virtual_account');

        $ba = $this->getTrashedDbEntityById('bank_account', $va->getBankAccountId());

        $vpa = $this->getTrashedDbEntityById('vpa', $va->getVpaId());

        self::assertEquals('closed', $response['status']);

        $this->assertNotNull($ba['deleted_at']);

        $this->assertNotNull($vpa['deleted_at']);
    }

    public function testVirtualAccountClose_PrimaryBalanceTypeVA()
    {
        $virtualAccount = $this->createVirtualAccount();

        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts/'.$virtualAccount['id'].'/close'
        ];

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

        $response = $this->makeRequestAndGetContent($request);

        $va = $this->getDbLastEntity('virtual_account');

        $ba = $this->getTrashedDbEntityById('bank_account', $va->getBankAccountId());

        self::assertEquals('closed', $response['status']);

        $this->assertNotNull($ba['deleted_at']);
    }

    public function testVirtualAccountClose_RBL_CollectxVA()
    {
        try
        {
            $input = [
                "id" => '10000000000000',
                'experiment_name' => RazorxTreatment::COLLECTX_RBL_MERCHANTS_VA_CLOSE_BLOCK,
                'request_data' => json_encode(['id'=>'10000000000000'])
            ];

            $output = [
                "response" => [
                    "variant" => [
                        "name" => 'enable',
                    ]
                ]
            ];

            $this->mockSplitzTreatment($input, $output);

            $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

            $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true, ifscCode: 'RATN0VAAPIS');

            $request = [
                'method'  => 'POST',
                'url'     => '/virtual_accounts/'.$this->virtualAccount->getPublicId().'/close'
            ];

            $this->ba->privateAuth();

            $response = $this->makeRequestAndGetContent($request);

        }
        catch (BadRequestException $exp)
        {
            $this->assertEquals(PublicErrorDescription::BAD_REQUEST_VA_CLOSE_BLOCKED_FOR_RBL_MERCHANTS, $exp->getMessage());
            return;
        }
    }

    public function testVirtualAccountClose_RBL_Sync_Mozart_CollectxVA()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

        $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true, channel: 'rbl', ifscCode: 'RATN0VAAPIS', setCorpId: true);

        $this->app['config']->set('gateway.mock_bt_rbl', true);

        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts/'.$this->virtualAccount->getPublicId().'/close'
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        $va = $this->getDbLastEntity('virtual_account');

        $ba = $this->getTrashedDbEntityById('bank_account', $va->getBankAccountId());

        self::assertEquals('closed', $response['status']);

        $this->assertNotNull($ba['deleted_at']);

        $this->assertEquals(2, $ba['is_gateway_sync']);

    }

    public function testVirtualAccountClose_RBL_Sync_Mozart_CollectxVA_Error_From_Bank()
    {
        try {
            $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

            $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true, channel: 'rbl', ifscCode: 'RATN0VAAPIS', setCorpId: true);

            $this->app['config']->set('gateway.mock_bt_rbl', true);

            $this->app['config']->set('rbl_close_virtual_account.error_code', ErrorCode::ER002);

            $request = [
                'method'  => 'POST',
                'url'     => '/virtual_accounts/'.$this->virtualAccount->getPublicId().'/close'
            ];

            $this->ba->privateAuth();

            $response = $this->makeRequestAndGetContent($request);
        }
        catch (BadRequestException $exp)
        {
            $va = $this->getDbLastEntity('virtual_account');

            self::assertEquals('active', $va->getStatus());

            $this->assertEquals('Gateway Failure in closing virtual account', $exp->getMessage());
            return;
        }

    }

    public function testVirtualAccountClose_RBL_Sync_Mozart_CollectxVA_Error_CorpID_NotFound()
    {
        try {
            $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

            $this->setUpMerchantForBusinessBanking($skipFeatureAddition = true, channel: 'rbl', ifscCode: 'RATN0VAAPIS');

            $this->app['config']->set('gateway.mock_bt_rbl', true);

            $this->app['config']->set('rbl_close_virtual_account.error_code', ErrorCode::ER002);

            $request = [
                'method'  => 'POST',
                'url'     => '/virtual_accounts/'.$this->virtualAccount->getPublicId().'/close'
            ];

            $this->ba->privateAuth();

            $response = $this->makeRequestAndGetContent($request);
        }
        catch (BadRequestException $exp)
        {
            $va = $this->getDbLastEntity('virtual_account');

            self::assertEquals('active', $va->getStatus());

            $this->assertEquals('BAD_REQUEST_CORP_CODE_NOT_FOUND', $exp->getCode());
            return;
        }

    }
}
