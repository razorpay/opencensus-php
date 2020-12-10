<?php


namespace Functional\FreshdeskTicket;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;


class SupportTicketDashboardTest extends TestCase
{
    use RequestResponseFlowTrait;


    protected $freshdeskClientMock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/SupportTicketDashboardTestData.php';

        parent::setUp();

        $this->setUpFreshdeskClientMock();

        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_freshdesk_tickets');

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0012',
            'ticket_id'      => '12',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'created_at'     => '1600000000',
            'updated_at'     => '1600000000',
        ]);
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

    public function testGetById()
    {
        $this->expectFreshdeskRequestAndRespondWith('tickets/12?include=stats', 'get', [], [
            'id'     => '12',
            'body'   => 'some random body 12',
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
                ],
                [
                    'id'     => 34,
                    'body'   => 'some random body 34',
                ],
                [
                    // 56 is not mapped to this merchant in our db. so we don't show it in the response, even if Freshdesk somehow returned this in the response
                    'id'     => 56,
                    'body'   => 'some random body 56',
                ],
                [
                    // 78 is not mapped to 'support_dashboard' in our db. so we don't show it in the response
                    'id'     => 78,
                    'body'   => 'some random body 78',
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
        ]);

        $this->startTest();
    }

    public function testCreateTicket()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'    => '10000000000000',
            'contact_mobile' => '9876543210',
        ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets', 'POST',
            [
                'description' => 'ticket description',
                'subject' => 'ticket subject',
                'cc_emails' => ['a@b.com'],
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'email' =>  'test@razorpay.com',
                'phone' => '9876543210',
                'priority' =>  1,
            ],
            [
                'id'            => '99',
                'description'   => 'ticket description',
            ]);

        $response = $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $this->assertNotEquals('razorpayid0012', $ticket['id']);

        $this->assertNotEquals('99', $response['id']);

        $this->assertEquals($response['id'], $ticket['id']);
    }

    public function testCreateTicketFreshdeskError()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'    => '10000000000000',
            'contact_mobile' => '9876543210',
        ]);

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
}
