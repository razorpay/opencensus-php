<?php


namespace Functional\FreshdeskTicket;

use Mockery;
use RZP\Services\RazorXClient;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;


class SupportTicketDashboardTest extends TestCase
{
    use RequestResponseFlowTrait;

    const DAY = 24 * 60 * 60;


    protected $freshdeskClientMock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/SupportTicketDashboardTestData.php';

        parent::setUp();

        $this->flushCache();

        $this->setUpFreshdeskClientMock();

        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_freshdesk_tickets');

        $ticketDetails["fd_instance"] = "rzp";

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0012',
            'ticket_id'      => '12',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
            'created_at'     => '1600000000',
            'updated_at'     => '1600000000',
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'    => '10000000000000',
            'contact_mobile' => '9876543210',
        ]);
    }

    protected function mockRazorxTreatment(string $returnValue = 'On')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    protected function expectFreshdeskRequestAndRespondWith($expectedPath, $expectedMethod, $expectedContent, $respondWith = [], $times = 1)
    {
        $expectedUrl1 = $this->app['config']->get('applications.freshdesk.url') . '/' . $expectedPath;
        $expectedUrl2 = $this->app['config']->get('applications.freshdesk.url2') . '/' . $expectedPath;

        $expectedUrls = [$expectedUrl1, $expectedUrl2];


        $this->freshdeskClientMock
            ->shouldReceive('getResponse')
            ->times($times)
            ->with(Mockery::on(function ($request)  use ($expectedUrls, $expectedMethod, $expectedContent ) {
                if (in_array($request['url'], $expectedUrls) === false)
                {
                    return false;
                }

                return $this->validateMethodAndContent($request,$expectedMethod,$expectedContent);
            }))
            ->andReturnUsing(function () use ($respondWith) {
                $response = new \Requests_Response;

                $response->body = json_encode($respondWith);

                return $response;
            });

    }

    protected function shouldNotReceiveFresdeskRequest()
    {
        $this->freshdeskClientMock
            ->shouldReceive('getResponse')
            ->times(0);
    }

    protected function setUpFreshdeskClientMock(): void
    {
        $this->app['config']->set('applications.freshdesk.sandbox', false);

        $this->app['config']->set('applications.freshdesk.token', 'random token');

        $this->app['config']->set('applications.freshdesk.token2', 'random token 2');

        $this->freshdeskClientMock = Mockery::mock('RZP\Services\FreshdeskTicketClient', [$this->app])->makePartial();

        $this->freshdeskClientMock->shouldAllowMockingProtectedMethods();

        $this->app['freshdesk_client'] = $this->freshdeskClientMock;
    }

    protected function getTimeInFreshdeskFormat($time)
    {
        return strftime('%Y-%m-%dT%H:%I:%SZ', $time);
    }

    public function testGetById()
    {
        $this->expectFreshdeskRequestAndRespondWith('tickets/12?include=stats', 'get', [], [
            'id'        => '12',
            'body'      => 'some random body 12',
            'fr_due_by' => '2020-12-08T16:04:20Z',
        ]);

        $this->startTest();
    }

    public function testGetByIdProhibitedShouldFail()
    {
        $this->shouldNotReceiveFresdeskRequest();

        // other ticket type
        $this->fixtures->edit('merchant_freshdesk_tickets', 'razorpayid0012', [
            'type'           => 'reserve_balance_activate',
        ]);

        $this->startTest();

        // belongs to other merchant
        $this->fixtures->edit('merchant_freshdesk_tickets', 'razorpayid0012', [
            'merchant_id'           => '20000000000000',
        ]);

        $this->startTest();
    }

    public function testFetchTicketsForMerchant()
    {
        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000%22&page=1', 'get', [],
        [
            'results' => [
                [
                    'id'     => 12,
                    'body'   => 'some random body 12',
                    'fr_due_by' => '2020-12-08T16:04:20Z',

                ],
                [
                    'id'     => 34,
                    'body'   => 'some random body 34',
                    'fr_due_by' => '2020-12-08T16:04:20Z',

                ],
                [
                    // 56 is not mapped to this merchant in our db. so we don't show it in the response, even if Freshdesk somehow returned this in the response
                    'id'     => 56,
                    'body'   => 'some random body 56',
                    'fr_due_by' => '2020-12-08T16:04:20Z',
                ],
                [
                    // 78 is not mapped to 'support_dashboard' in our db. so we don't show it in the response
                    'id'     => 78,
                    'body'   => 'some random body 78',
                    'fr_due_by' => '2020-12-08T16:04:20Z',
                ],
            ],

        ], 2);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0034',
            'ticket_id'      => '34',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',

        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0056',
            'ticket_id'      => '56',
            'merchant_id'    => '20000000000000',
            'type'           => 'support_dashboard',

        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0078',
            'ticket_id'      => '78',
            'merchant_id'    => '10000000000000',
            'type'           => 'reserve_balance_activate',

        ]);

        $this->startTest();
    }

    public function testGetConversationsForTicket()
    {
        $this->expectFreshdeskRequestAndRespondWith('tickets/12/conversations?page=1&per_page=10', 'get', [], [
            [
                'id'       => 11119788088,
                'body'     => 'some random body1',
                'ticket_id'=> '12',
            ],
            [
                'id'       => 11119788089,
                'body'     => 'some random body2',
                'ticket_id'=> '12',
            ],
        ]);

        $this->startTest();
    }

    public function testGetConversationsProhibitedShouldFail()
    {
        $this->shouldNotReceiveFresdeskRequest();

        // other ticket type
        $this->fixtures->edit('merchant_freshdesk_tickets', 'razorpayid0012', [
            'type'           => 'reserve_balance_activate',
        ]);

        $this->startTest();

        // belongs to other merchant
        $this->fixtures->edit('merchant_freshdesk_tickets', 'razorpayid0012', [
            'merchant_id'           => '20000000000000',
        ]);

        $this->startTest();
    }

    public function testReplyToTicket()
    {
        $this->expectFreshdeskRequestAndRespondWith('tickets/12/reply', 'post',
            [
                'body' => 'random reply',
            ],
            [
                'id'        => 567,
                'user_id'   => 890,
                'body'      => 'random reply',
                'ticket_id' => '12',

        ]);

        $this->startTest();
    }

    public function testReplyToTicketProhibitedShouldFail()
    {
        $this->shouldNotReceiveFresdeskRequest();

        // other ticket type
        $this->fixtures->edit('merchant_freshdesk_tickets', 'razorpayid0012', [
            'type'           => 'reserve_balance_activate',
        ]);

        $this->startTest();

        // belongs to other merchant
        $this->fixtures->edit('merchant_freshdesk_tickets', 'razorpayid0012', [
            'merchant_id'           => '20000000000000',
        ]);

        $this->startTest();
    }

    public function testRaiseGrievanceOnTicket()
    {
        $this->expectFreshdeskRequestAndRespondWith('tickets/12', 'PUT',
        [
            'status'    => 2,
            'priority'  => 4,
        ],
        [
            'id'            => '12',
            'description'   => 'random grievance',
            'status'        => 2,
            'priority'      => 4,
            'fr_due_by'     => '2020-12-08T16:04:20Z',
        ]);

        $this->startTest();
    }

    public function testCreateTicketRzp()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);


        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzp',
            [
                'description' => 'ticket description',
                'subject' => 'ticket subject',
                'cc_emails' => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'email' =>  'test@razorpay.com',
                'phone' => '9876543210',
                'priority' =>  1,
            ],
            [
                'id'            => '99',
                'description'   => 'ticket description',
                'fr_due_by'     => $frDueByFreshdeskFormat,
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'priority' =>  1,
            ]);

        $response = $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertNotEquals('razorpayid0012', $ticket['id']);

        $this->assertNotEquals('99', $response['id']);

        $this->assertEquals($response['id'], $ticket['id']);

        // in this test case, we didnt have average FR response time for category+priority. so we proxied the FR time given by Freshdesk

        $this->assertEquals($frDueByFreshdeskFormat, $response['fr_due_by']);

        $this->assertEquals($frDueByFreshdeskFormat, $ticket['ticket_details']['fr_due_by']);

        $this->assertEquals('rzp', $fdInstance);
    }

    public function testCreateTicketRzpSol()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST','rzpsol',
            [
                'description' => 'ticket description',
                'subject' => 'ticket subject',
                'cc_emails' => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'email' =>  'test@razorpay.com',
                'phone' => '9876543210',
                'priority' =>  1,
                'group_id' => 42000097450,
            ],
            [
                'id'            => '99',
                'description'   => 'ticket description',
                'fr_due_by'     => $frDueByFreshdeskFormat,
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Technical support',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'priority' =>  1,
            ]);

        $response = $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertNotEquals('razorpayid0012', $ticket['id']);

        $this->assertNotEquals('99', $response['id']);

        $this->assertEquals($response['id'], $ticket['id']);

        $this->assertEquals('rzpsol', $fdInstance);

    }

    public function testCreateTicketFreshdeskError()
    {
        $this->expectFreshdeskRequestAndRespondWith('tickets', 'POST',
            [
                'description' => 'ticket description',
                'subject' => 'ticket subject',
                'cc_emails' => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'    => 'Invalid',
                    'cf_requestor_subcategory' => 'activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'email'     =>  'test@razorpay.com',
                'phone'     => '9876543210',
                'priority'  =>  1,
            ],            [
                'description'   => 'Validation failed',
                'errors'        => [
                    [
                        'field'     => 'custom_fields.cf_requester_category',
                        'values'    => 'Merchant,Customer,Service request,Prospect,Other,Partner',
                        'code'      => 'invalid value',
                    ]
                ]
            ]);

        $this->startTest();
    }

    public function testCreateTicketWithAttachment()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateTicketRzp'];

        $this->addAttachmentToRequest(__FUNCTION__, 'abc.jpg');

        $this->mockRazorxTreatment('on');

        $this->freshdeskClientMock
            ->shouldReceive('makeCurlRequest')
            ->times(1)
            ->andReturnUsing(function () {
                return json_encode([
                    'id'            => '99',
                    'description'   => 'ticket description',
                    'fr_due_by'     => '2020-11-30T16:52:00Z',
                    'custom_fields' => [
                        'cf_requester_category'    => 'Merchant',
                        'cf_requestor_subcategory' => 'Activation',
                        'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                    ],
                    'priority' =>  1,
                ]);
            });

        $this->startTest();
    }

    public function testCreateTicketInvalidAttachmentExtension()
    {

        $this->addAttachmentToRequest(__FUNCTION__, 'a.exe');

        $this->mockRazorxTreatment('on');

        $this->startTest();
    }

    public function testReceiveFreshdeskWebhookOnTicketReplyFirstResponseTimeDataDoesntExist()
    {
        $now = time();

        $this->ba->freshdeskWebhookAuth();

        $this->expectFreshdeskRequestAndRespondWith('tickets/12/conversations?page=1&per_page=10', 'get', [], [
            [
                'id'            => 11119788088,
                'body'          => 'some random body1',
                'ticket_id'     => '12',
                'from_email'    => 'foo@bar.com',
                'created_at'    => '2020-11-30T16:52:00Z',
            ],
            [
                'id'            => 11119788089,
                'body'          => 'some random body2',
                'ticket_id'     => '12',
                'from_email'    => 'Razorpaysandbox <support@razorpaysandbox.freshdesk.com>',
                'created_at'    => '2020-12-01T07:24:40Z',
            ],
        ]);

        $this->startTest();

        $firstResponseTimeData = $this->app['cache']->get('support_dashboard_fr_time_data_cache_key_Activation_Low');

        $this->assertEquals(1, count($firstResponseTimeData));

        $this->assertArraySelectiveEquals([
            'first_response_time'       => 6807480,
        ], $firstResponseTimeData[0]);

        $this->assertGreaterThanOrEqual($now, $firstResponseTimeData[0]['created_at']);
    }

    public function testReceiveFreshdeskWebhookOnTicketReplyFirstResponseTimeDataExist()
    {
        $ticketCreatedAt = time() - 2 * self::DAY;

        // our consideration window is 1 week(7 days)
        // create 3 existing response time data
        // the first 2 were created more than 7 days ago
        // at the end of the test we will assert that only 2 are left(1 new + 1 created within the last 1 week)
        $this->app['cache']->put('support_dashboard_fr_time_data_cache_key_Activation_Urgent', [
           [
               'first_response_time'  => 10000,
               'created_at'           => $ticketCreatedAt - 8 *  self::DAY,
           ],
           [
               'first_response_time' => 20000,
               'created_at'          => $ticketCreatedAt - 9 *  self::DAY,
           ],
           [
               'first_response_time' => 3 *  self::DAY,
               'created_at'          => $ticketCreatedAt - 3 *  self::DAY,
           ],
        ], 1 *  self::DAY);

        $this->ba->freshdeskWebhookAuth();

        $this->fixtures->edit('merchant_freshdesk_tickets', 'razorpayid0012', [
                'created_at'    => $ticketCreatedAt,
                'updated_at'    => $ticketCreatedAt,
            ]
        );

        $this->expectFreshdeskRequestAndRespondWith('tickets/12/conversations?page=1&per_page=10', 'get', [], [
            [
                'id'            => 11119788088,
                'body'          => 'some random body1',
                'ticket_id'     => '12',
                'from_email'    => 'foo@bar.com',
                'created_at'    => $this->getTimeInFreshdeskFormat($ticketCreatedAt + 1 * self::DAY),
            ],
            [
                'id'            => 11119788089,
                'body'          => 'some random body2',
                'ticket_id'     => '12',
                'from_email'    => 'Razorpaysandbox <support@razorpaysandbox.freshdesk.com>',
                'created_at'    => $this->getTimeInFreshdeskFormat($ticketCreatedAt + 2 * self::DAY),
            ],
        ]);

        $this->startTest();

        $firstResponseTimeData = $this->app['cache']->get('support_dashboard_fr_time_data_cache_key_Activation_Urgent');

        $firstResponseTimeAverage = $this->app['cache']->get('support_dashboard_fr_time_average_cache_key_Activation_Urgent');

        $this->assertEquals(2, count($firstResponseTimeData));

        // assert that new average for ticket of category+priority is 2.5 days with a delta of 5000 seconds
        // delta is needed to prevent test failures due to precision errors in tests
        $this->assertEquals(2.5 * self::DAY, $firstResponseTimeAverage, '', 5000);
    }

    public function testReceiveFreshdeskWebhookOnTicketReplyNoRazorpayResponseYet()
    {
        $ticketCreatedAt = time();

        $beforeCacheData = [
            [
                'first_response_time' => 1 * self::DAY,
                'created_at'          => $ticketCreatedAt - 4 * self::DAY,
            ],
            [
                'first_response_time' => 3 * self::DAY,
                'created_at'          => $ticketCreatedAt - 3 * self::DAY,
            ],
        ];

        $this->app['cache']->put('support_dashboard_fr_time_data_cache_key_Activation_Urgent', $beforeCacheData, self::DAY);

        $this->expectFreshdeskRequestAndRespondWith('tickets/12/conversations?page=1&per_page=10', 'get', [], [
            [
                'id'            => 11119788088,
                'body'          => 'some random body1',
                'ticket_id'     => '12',
                'from_email'    => 'foo@bar.com',
                'created_at'    => $this->getTimeInFreshdeskFormat($ticketCreatedAt + 1 * self::DAY),
            ]
        ]);

        $this->startTest();

        $afterCacheData = $this->app['cache']->get('support_dashboard_fr_time_data_cache_key_Activation_Urgent');

        $this->assertEquals($beforeCacheData, $afterCacheData);
    }

    public function testCreateTicketWithRewrittenFrDueBy()
    {
        //  we are testing the following scenario
        // in freshdesk, we have set an SLA of FR_DUE_BY as 2 days
        // but the last week average is 4 days
        // we assert that such a ticket response contains fr_due_by = 4days instead of 2 days
        $now = time();

        $frDueBy = $now + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->app['cache']->put('support_dashboard_fr_time_average_cache_key_Activation_Low', self::DAY * 4, self::DAY);


        $this->expectFreshdeskRequestAndRespondWith('tickets', 'POST',
            [
                'description' => 'ticket description',
                'custom_fields' => [
                    'cf_requester_category' => 'Merchant',
                    'cf_requestor_subcategory' => 'Activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'priority' => 1,
            ],
            [
                'id' => '99',
                'description' => 'ticket description',
                'fr_due_by' => $frDueByFreshdeskFormat,
                'custom_fields' => [
                    'cf_requester_category' => 'Merchant',
                    'cf_requestor_subcategory' => 'Activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'priority' => 1,
            ]);

        $response = $this->startTest();

        $frDueByBasedOnWeekAverage = $now + self::DAY * 4;

        $frDueByBasedOnWeekAverageFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueByBasedOnWeekAverage);

        $this->assertEquals(strtotime($frDueByBasedOnWeekAverageFreshdeskFormat), strtotime($response['fr_due_by']), '', 100);
    }

    public function testReceiveFreshdeskWebhookOnTicketCreated()
    {
        $this->ba->freshdeskWebhookAuth();

        $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $this->assertArraySelectiveEquals([
            'merchant_id'       => '10000000000000',
            'ticket_id'         => '1234',
            'type'              => 'support_dashboard',
            'ticket_details'    => [
                'fd_instance'       => 'rzp',
                'fr_due_by'         => '2020-12-08T16:04:20Z',
            ],
        ], $ticket);
    }

    protected function checkFreshdeskCorrectInstanceCallAndRespondWith($expectedPath,
                                                                       $expectedMethod,
                                                                       $expectedFdInstance,
                                                                       $expectedContent,
                                                                       $respondWith = [],
                                                                       $times = 1)
    {
        $expectedUrl1 = $this->app['config']->get('applications.freshdesk.url') . '/' . $expectedPath;

        $expectedUrl2 = $this->app['config']->get('applications.freshdesk.url2') . '/' . $expectedPath;

        $expectedUrls = [
            'rzp'       => $expectedUrl1,
            'rzpsol'    =>$expectedUrl2
        ];

        $this->freshdeskClientMock
            ->shouldReceive('getResponse')
            ->times($times)
            ->with(Mockery::on(function ($request)  use ($expectedFdInstance, $expectedUrls, $expectedMethod, $expectedContent )
            {
                if ($request['url'] !== $expectedUrls[$expectedFdInstance])
                {
                    return false;
                }

                return $this->validateMethodAndContent($request,$expectedMethod,$expectedContent);
            }))
            ->andReturnUsing(function () use ($respondWith) {
                $response = new \Requests_Response;

                $response->body = json_encode($respondWith);

                return $response;
            });

    }

    protected function validateMethodAndContent($request, $expectedMethod, $expectedContent) : bool
    {
        if (strtolower($request['method']) !== strtolower($expectedMethod))
        {
            return false;
        }

        if (is_string($request['content']) === true)
        {
            $actualContent = json_decode($request['content'], true);
        }

        foreach ($expectedContent as $key => $value)
        {
            if (isset($actualContent[$key]) === false)
            {
                return false;
            }

            if ($expectedContent[$key] !== $actualContent[$key])
            {
                return false;
            }
        }

        return true;
    }

    protected function addAttachmentToRequest(string $caller, string $filename, int $size = 1)
    {
        $uploadedFile = UploadedFile::fake()->create($filename, $size);

        $this->testData[$caller]['request']['files']['attachments'] = $this->testData[$caller]['request']['files']['attachments'] ?? [];

        array_push($this->testData[$caller]['request']['files']['attachments'], $uploadedFile);
    }
}
