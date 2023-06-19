<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantAutomatedAPMOnboardingTest extends OAuthTestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $connector = $this->mockSqlConnectorWithReplicaLag(0);
        $this->app->instance('db.connector.mysql', $connector);
        $this->app['config']->set('applications.reminders.mock', true);
        $this->app['config']->set('applications.ufh.mock', true);
        $this->app['config']->set('applications.beam.mock', true);
        $this->app['config']->set('applications.terminals_service.mock', true);

        $this->fixtures->merchant->enableInternational();

        $this->fixtures->edit('merchant', '10000000000000', [
            'name' => 'Sample Org Pvt Ltd',
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'XYZ',
            'business_type' => 2,
            'business_website' => 'www.sample.org',
            'company_cin' => 'L21091KA2019OPC141331',
            'gstin'     => '29GGGGG1314R9Z6',
        ]);

        $this->mockSplitzTreatment();

        $this->postEmerchantpayRequestData();
    }

    /**
     * Comprehensive Test Case for testing Create and Update of EmerchantPay APM Onboarding
     * Request Creation API. Tests in 3 stages :
     * 1. Creates Request with 2 payment instruments
     * 2. Adds another payment instrument, adds merchant_info and creates an owner
     * 3. Updates merchant_info and owner_detail, adds another owner
     */
    protected function postEmerchantpayRequestData()
    {
        $request = [
            'url' => '/merchant/international/apm_request',
            'method' => 'POST',
            'convertContentToString' => false,
            'content' => [
                'instruments' => ['trustly', 'poli', 'sofort'],
                'submitted'   => false,
            ],
        ];

        $this->ba->proxyAuth();
        $this->makeRequestAndGetContent($request);

        $request['content']['instruments'] = ['trustly', 'poli', 'giropay'];
        $request['content']['merchant_info'] =
            [
                'service_offered' => 'service',
                'average_delivery_in_days' => 5,
                'physical_delivery' => 'yes',
                'registration_number' => 'L21091KA2019OPC141331',
                'gst_number' => '29GGGGG1314R9Z6',
                'date_of_incorporation' => '2012-04-23',
                'address_line1' => 'building 1',
                'address_line2' => 'church street',
                'city' => 'Bangalore',
                'zipcode' => '560095',
                'state' => 'Karnataka',
                'country' => 'India',
                'purpose_code' => 'P103',
                'iec_code' => '120',
                'documents' => [[
                    'id' => 'doc_JVt3gMwxRshtWa',
                    'key' => 'gst_certificate'
                ]]
            ];

        $request['content']['owner_details'] =
            [[
                'first_name' => 'Suresh',
                'last_name' => 'Varma',
                'position' => 'CEO',
                'ownership_percentage' => 10.17,
                'date_of_birth' => '1980-04-23',
                'passport_number' => 'J1234567',
                'aadhaar_number' => '223456789123',
                'pan_number' => 'ABCDE1234F',
                'address_line1' => 'building 1',
                'address_line2' => 'church street',
                'city' => 'Bangalore',
                'zipcode' => '560095',
                'state' => 'Karnataka',
                'country' => 'India',
                'documents' =>
                    [[
                        'id' => 'doc_JVt3gMwxRshtWa',
                        'key' => 'aadhaar'
                    ],
                    [
                        'id' => 'doc_JlM2YPgK7XC1F1',
                        'key' => 'passport'
                    ]]
            ]];

        $this->ba->proxyAuth();
        $this->makeRequestAndGetContent($request);

        $ow = $this->getLastEntity('merchant_owner_details', true);

        $request['content']['merchant_info']['registration_number'] = 'L21091KA2019OPC141335';
        $request['content']['owner_details'][0]['id'] = $ow['id'];
        array_push($request['content']['owner_details'][0]['documents'],
            ['id' => 'doc_JlM2YPgK7XC1F1', 'key' => 'pancard']);
        array_push($request['content']['owner_details'],
            [
                'first_name' => 'Ramesh',
                'last_name' => 'Varma',
                'position' => 'CTO',
                'ownership_percentage' => 10.15,
                'date_of_birth' => '1980-04-24',
                'passport_number' => 'J1234567',
                'aadhaar_number' => '223456789123',
                'pan_number' => 'ABCDE1234F',
                'address_line1' => 'building 1',
                'address_line2' => 'church street',
                'city' => 'Bangalore',
                'zipcode' => '560095',
                'state' => 'Karnataka',
                'country' => 'India',
                'documents' =>
                    [[
                        'id' => 'doc_JVt3gMwxRshtWa',
                        'key' => 'aadhaar'
                    ],
                    [
                        'id' => 'doc_JlM2YPgK7XC1F1',
                        'key' => 'passport'
                    ]]
            ]);
        $request['content']['submitted'] = true;

        $this->ba->proxyAuth();
        $response = $this->makeRequestAndGetContent($request);

        $mii = $this->getLastEntity('merchant_international_integrations', true);
        $ows = $this->getEntities('merchant_owner_details', ['count' => 2], true);

        $pm = ['trustly', 'poli', 'giropay'];

        self::assertCount(3, $mii['payment_methods']);
        foreach ($mii['payment_methods'] as $payment_method)
        {
            self::assertTrue($payment_method['terminal_request_sent']);
            self::assertTrue(in_array($payment_method['instrument'], $pm));
        }
        self::assertEquals(2, $ows['count']);
    }

    /**
     *
     */
    public function testGetEmerchantpayRequestData()
    {
        $request = [
            'url' => '/merchant/international/apm_request',
            'method' => 'GET',
        ];

        $this->ba->proxyAuth();
        $response = $this->makeRequestAndGetContent($request);

        self::assertCount(3, $response['instruments']);
        self::assertCount(4, $response['non_editable']);
        self::assertCount(15, $response['merchant_info']);
        self::assertCount(2, $response['owner_details']);
        self::assertCount(16, $response['owner_details'][0]);
        self::assertCount(16, $response['owner_details'][1]);
        self::assertTrue($response['submitted']);
    }

    public function testdeleteEmerchantpayRequestOwner()
    {
        $ow = $this->getLastEntity('merchant_owner_details', true);
        $request = [
            'url' => '/merchant/international/apm_request/owner',
            'method' => 'DELETE',
            'content' => ['owner_id' => $ow['id']]
        ];

        $this->ba->proxyAuth();
        $response = $this->makeRequestAndGetContent($request);

        self::assertEquals($response['owner_id'], $ow['id']);

        $request = [
            'url' => '/merchant/international/apm_request',
            'method' => 'GET',
        ];

        $this->ba->proxyAuth();
        $response = $this->makeRequestAndGetContent($request);

        self::assertCount(1, $response['owner_details']);

    }

    public function testGenerateEmerchantpayMAFRequest()
    {
        $request = [
            'url' => '/merchant/international/apm_request/reminder/test/' . '10000000000000',
            'method' => 'POST',
            'convertContentToString' => false,
            'content' => [
                'submitted_at' => Carbon::now(Timezone::IST)->getTimestamp(),
                'payment_methods' => ['trustly', 'poli', 'giropay'],
                'send_file' => false,
            ],
        ];

        $this->ba->reminderAppAuth();
        $this->makeRequestAndGetContent($request);

        $mii = $this->getLastEntity('merchant_international_integrations', true);
        foreach ($mii['payment_methods'] as $pm)
        {
            self::assertTrue($pm['file_request_sent']);
        }

        $document = $this->getLastEntity('merchant_document', true);
        self::assertEquals('owner', $document['entity_type']);
    }

    protected function mockSplitzTreatment()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->splitzMock = \Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }
}
