<?php

namespace RZP\Tests\Functional\Card;

use Event;
use Mockery;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;

use RZP\Models\Card\IIN;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class IinTest extends TestCase
{
    use RequestResponseFlowTrait;
    use IinTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/IinTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testAddIin()
    {
        $this->startTest();
    }

    public function testAddIinWithSubType()
    {
        $this->startTest();
    }

    public function testAddIinWithInValidSubType()
    {
        $this->startTest();
    }

    public function testAddIinWithCategory()
    {
        $this->startTest();
    }

    public function testAddIinWithInvalidCategory()
    {
        $this->startTest();
    }

    public function testEditIinWithCategoryWithoutNetwork()
    {
        $this->testAddIin();

        $this->startTest();
    }

    public function testEditIinWithoutCategory()
    {
        $this->testAddIin();

        $this->startTest();
    }

    public function testAddIinWithCategoryAndRuPay()
    {
        $this->startTest();
    }

    public function testAddIinWithRecurring()
    {
        $this->startTest();
    }

    public function testAddIinWithCountry()
    {
        $this->startTest();
    }

    public function testAddIinWithMandateHub()
    {
        $this->startTest();
    }

    public function testAddIinWithInvalidMandateHub()
    {
        $this->startTest();
    }

    public function testEditIinWithMandateHub()
    {
        $this->testAddIinWithMandateHub();

        $this->startTest();
    }

    public function testEditIinFailedInvalidMessageType()
    {
        $this->testAddIin();

        $this->startTest();
    }

    public function testEditIin()
    {
        $this->testAddIin();

        $this->startTest();
    }

    public function testLockedIin()
    {
        $this->testAddIin();

        $this->startTest();

        $iin = $this->getLastEntity('iin', true);

        $this->assertEquals($iin[IIN\Entity::LOCKED], true);
    }

    public function testGetIin()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testPrivateGetIin()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['bin_api']);

        $this->startTest();
    }

    public function testPrivateGetIinWithoutFeatureFlag()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testPrivateGetIinInternational()
    {
        $this->testAddIinWithCountry();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['bin_api']);

        $this->startTest();
    }

    public function testPrivateGetIinNotPresent()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['bin_api']);

        $data = $this->testData[__FUNCTION__];

        $request = array(
            'url'     => '/iins/607530',
            'method'  => 'get',
        );

        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testPrivateGetInvalidIin()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['bin_api']);

        $this->startTest();
    }

    public function testPrivateGetIinAllFlowsSupported()
    {
        $this->testAddIin();

        $flows = [
            'headless_otp' => '1',
            'iframe'       => '1',
        ];

        $this->fixtures->edit('iin', 112333, ['flows' => $flows, 'country' => 'US', 'recurring' => true, 'type' => 'credit', 'emi' => true]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['bin_api']);

        $this->startTest();
    }

    public function testPrivateGetIinIvrSupported()
    {
        $this->testAddIin();

        $flows = [
            'ivr'          => '1',
            'iframe'       => '1',
        ];

        $this->fixtures->edit('iin', 112333, ['flows' => $flows, 'country' => 'US', 'recurring' => true, 'type' => 'credit', 'emi' => true]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['bin_api']);

        $this->startTest();
    }

    public function testGetIinDCCBlacklistedUpdate()
    {
        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testPrivateGetIinEmptyNetwork()
    {
        $this->testAddIin();

        $this->fixtures->edit('iin', 112333, ['network' => '', 'country' => 'US', 'recurring' => true, 'type' => 'credit', 'emi' => true]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['bin_api']);

        $this->startTest();
    }

    public function testBatchServiceIinUpdate()
    {
        $this->ba->batchAppAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testGetPaymentFlows()
    {
        $this->testAddIin();

        $this->ba->publicAuth();

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
            'iframe'       => '1',
            ];

        $this->fixtures->edit('iin', 112333, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['atm_pin_auth', 'charge_at_will']);

        $this->startTest();
    }

    public function testGetPaymentFlowsEmptyResponse()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetPaymentOtpFlow()
    {
        $this->testAddIin();

        $this->ba->publicAuth();

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
        ];

        $this->fixtures->edit('iin', 112333, ['flows' => $flows]);

        $response = $this->startTest();

        $this->assertArrayNotHasKey('atm_pin_auth', $response);
    }

    public function testGetPaymentFlowsFromIinDetailsEndpoint()
    {
        $this->testAddIin();

        $this->ba->publicAuth();

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
            'iframe'       => '1',
        ];

        $this->fixtures->edit('iin', 112333, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['atm_pin_auth', 'charge_at_will']);

        $this->startTest();
    }

    public function testCobrandingPartnerFromIinDetailsEndpoint()
    {
        $this->testAddIin();

        $this->ba->publicAuth();

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
            'iframe'       => '1',
        ];

        $this->fixtures->edit('iin', 112333, ['cobranding_partner' => 'onecard']);

        $this->startTest();
    }

    public function testGetPaymentFlowsEmptyResponseFromIinDetailsEndpoint()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetPaymentOtpFlowFromIinDetailsEndpoint()
    {
        $this->testAddIin();

        $this->ba->publicAuth();

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
        ];

        $this->fixtures->edit('iin', 112333, ['flows' => $flows]);

        $response = $this->startTest();

        $this->assertArrayHasKey('country', $response);

        $this->assertArrayNotHasKey('atm_pin_auth', $response);
    }

    public function testGetIins()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testImportIin()
    {
        $this->ba->adminAuth();

        $file = $this->getUploadedIinFile();

        $testData = &$this->testData['testImportIin'];

        $testData['request']['files']['file'] = $file;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testImportIinWithMessageType()
    {
        $this->ba->adminAuth();

        $file = $this->getUploadedIinFile(false, true);

        $testData = &$this->testData['testImportIinWithMessageType'];

        $testData['request']['files']['file'] = $file;

        $this->ba->adminAuth();

        $this->startTest();

        $iin = $this->getDbEntityById('iin', '559300')->toArray();
        $this->assertEquals('DMS', $iin['message_type']);
    }

    public function testImportIinWithIssuer()
    {
        $this->ba->adminAuth();

        $file = $this->getUploadedIinFile(true);

        $testData = &$this->testData['testImportIinWithIssuer'];

        $testData['request']['files']['file'] = $file;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testIinRangeUploadWithType()
    {
        $this->ba->adminAuth();

        $this->startTest();

        $iin = $this->getEntityById('iin', 652851, true);

        $this->assertEquals('credit', $iin['type']);
        $this->assertEquals('RuPay', $iin['network']);
        $this->assertEquals(null, $iin['issuer']);
    }

    public function testGetCardPaymentFlowsFailure()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCardPaymentFlowsEmpty()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCardPaymentFlows()
    {
        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
            'iframe'       => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['atm_pin_auth', 'axis_express_pay']);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('iframe', $response);
    }

    public function testGetCardPaymentFlowsFromIin()
    {
        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['atm_pin_auth', 'axis_express_pay']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCardPaymentFlowAndIinDetails()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetIssuerDetails()
    {
        $this->ba->mandateHQAuth();

        $this->startTest();
    }

    public function testGetCardPaymentFlowAndIinDetailsWithEmiNotEnabled()
    {
        $this->ba->publicAuth();

        $this->fixtures->edit('iin', 401200, ['emi' => false]);

        $this->startTest();
    }

    public function testGetCardPaymentDomesticIinTestMode()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCardPaymentDomesticIinLiveMode()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->addFeatures(['recurring_card_mandate']);

        $mandateHQ = Mockery::mock('RZP\Services\MandateHQ', [$this->app]);

        $this->app->instance('mandateHQ', $mandateHQ);

        $mandateHQ->shouldReceive('isBinSupported')
            ->andReturnUsing(function ()
            {
                return true;
            });

        $this->startTest();
    }

    public function testGetCardPaymentFlowAndIinDetailsWithHdfcDebitIin()
    {
        $this->ba->publicAuth();

        $this->fixtures->edit('iin', 401200, [
            'emi'     => false,
            'issuer'  => 'HDFC',
            'type'    => 'debit',
        ]);

        $this->startTest();
    }

    public function testGetInnsListWithFeatures()
    {
        $flows = [
            'otp' => '1',
        ];
        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $flows = [
            'headless_otp' => '1',
            'ivr' => '1',
        ];
        $this->fixtures->edit('iin', 401201, ['flows' => $flows]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['iin_listing']);

        $this->fixtures->merchant->addFeatures(['axis_express_pay']);

        $response = $this->startTest();

        $this->assertEquals(2, $response['count']);

        $this->assertEquals([401200,401201], $response['iins']);

        $this->fixtures->merchant->addFeatures(['headless_disable']);

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $this->assertEquals([401200], $response['iins']);
    }

    public function testGetInnsListBySubtype()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['iin_listing']);

        $this->fixtures->edit('iin', 401200, ['sub_type' => 'business']);

        $this->fixtures->edit('iin', 401201, ['sub_type' => 'business']);

        $response = $this->startTest();

        $this->assertEquals(2, $response['count']);

        $this->assertEquals([401200,401201], $response['iins']);
    }

    public function testGetInnsListBySubtypeNoneExisting()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['iin_listing']);

        $response = $this->startTest();

        $this->assertEquals(0, $response['count']);

        $this->assertEquals([], $response['iins']);
    }

    public function testGetInnsListInvalidSubtype()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['iin_listing']);

        $this->startTest();
    }

    public function testGetBulkFlows()
    {
        $flows = [
            'pin' => '1',
            'otp' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['iin_listing']);

        $this->fixtures->merchant->addFeatures(['axis_express_pay']);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $this->assertEquals([401200], $response['iins']);

        $flows = [
            'pin' => '1',
            'otp' => '1',
            'headless_otp' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $this->assertEquals([401200], $response['iins']);

        $flows = [
            'pin' => '1',
            'headless_otp' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $this->assertEquals([401200], $response['iins']);
    }

    public function testBulkFlowsUpdateEnable()
    {
        $flows = [
            'pin' => '1',
            'otp' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $flows = [
            'pin' => '1',
            'otp' => '0',
        ];

        $this->fixtures->edit('iin', 401201, ['flows' => $flows]);

        $this->ba->adminAuth();

        // Enable otp flow for both and assert that it appears in response
        $this->startTest();
    }

    public function testBulkFlowsUpdateDisable()
    {
        $flows = [
            'pin' => '1',
            'otp' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $flows = [
            'pin' => '1',
            'otp' => '0',
        ];

        $this->fixtures->edit('iin', 401201, ['flows' => $flows]);

        $this->ba->adminAuth();

        // Disable pin flow for both
        $response = $this->startTest();

        // Neither IIN now supports pin flow
        $this->assertNotContains('pin', $response['401200']['flows']);
        $this->assertNotContains('pin', $response['401201']['flows']);
    }

    public function testBulkFlowsInvalidInput()
    {
        $flows = [
            'pin' => '1',
            'otp' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $flows = [
            'pin' => '1',
        ];

        $this->fixtures->edit('iin', 401201, ['flows' => $flows]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkMandateHubUpdateEnable()
    {
        $mandateHubs = [
            'mandate_hq'     => '0',
            'billdesk_sihub' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['mandate_hubs' => $mandateHubs]);

        $mandateHubs = [
            'mandate_hq'     => '1',
            'billdesk_sihub' => '1',
        ];

        $this->fixtures->edit('iin', 401201, ['mandate_hubs' => $mandateHubs]);

        $this->ba->adminAuth();

        // Enable otp flow for both and assert that it appears in response
        $this->startTest();
    }

    public function testBulkMandateHubUpdateDisable()
    {
        $mandateHubs = [
            'mandate_hq'     => '0',
            'billdesk_sihub' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['mandate_hubs' => $mandateHubs]);

        $mandateHubs = [
            'mandate_hq'     => '1',
            'billdesk_sihub' => '1',
        ];

        $this->fixtures->edit('iin', 401201, ['mandate_hubs' => $mandateHubs]);

        $this->ba->adminAuth();

        // Enable otp flow for both and assert that it appears in response
        $this->startTest();
    }

    public function testIinsBulkUpdate()
    {
        $this->startTest();
    }

    public function testQueryCacheforIin()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake(false);

        $this->testEditIin();

        Event::assertDispatched(CacheMissed::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                $this->assertEquals('iin_112333', $tag);
            }

            return true;
        });

        Event::assertDispatched(KeyWritten::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                $this->assertEquals('iin_112333', $tag);
            }

            return true;
        });

        Event::assertNotDispatched(CacheHit::class);

        $request = [
            'request' => [
                'url' => '/iins/112333',
                'method' => 'put',
                'content' => [
                    'country'        => 'IN',
                    'issuer'         => 'HDFC',
                    'issuer_name'    => 'HDFC',
                    'emi'            => 1,
                    'network'        => 'RuPay',
                    'type'           => 'credit',
                    'message_type'   => 'SMS',
                ],
            ],
            'response' => [
                'content' => [
                    'iin'            => '112333',
                    'network'        => 'RuPay',
                    'type'           => 'credit',
                    'country'        => 'IN',
                    'issuer'         => 'HDFC',
                    'issuer_name'    => 'HDFC Bank',
                    'emi'            => true,
                    'message_type'   => 'SMS',
                    'recurring'      => false,
                ],
            ],
        ];

        $this->runRequestResponseFlow($request);

        //Since after update cache is flushed, for next request cacheMiss happens .

        Event::assertDispatched(CacheMissed::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                $this->assertEquals('iin_112333', $tag);
            }

            return true;
        });
    }

    public function testDisableMultipleIinFlows()
    {
        $this->testAddIin();

        $flows = [
            'headless_otp' => '1',
            'ivr'          => '1',
        ];

        $this->fixtures->edit('iin', 112333, ['flows' => $flows, 'country' => 'US', 'recurring' => true, 'type' => 'credit', 'emi' => true]);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        return $this->runRequestResponseFlow($testData);
    }
}
