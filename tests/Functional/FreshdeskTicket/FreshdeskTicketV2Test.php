<?php

namespace Functional\FreshdeskTicket;

use Mockery;
use Carbon\Carbon;
use RZP\Services\RazorXClient;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Services\Mock\HarvesterClient;
use RZP\Models\User\Entity as UserEntity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Services\Mock\DruidService as MockDruidService;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Tests\Functional\Fixtures\Entity\User as UserFixture;


class FreshdeskTicketV2Test extends TestCase
{
    use FreshdeskTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    const DAY = 24 * 60 * 60;

    protected $freshdeskClientMock;

    protected $storkMock;

    protected $ravenMock;

    const RZP_CREATE_TICKET                         = 'rzp_create_ticket';
    const RZP_CREATE_TICKET_WITH_ACTIVATION_STATUS  = 'rzp_create_ticket_with_activation_status';
    const RZP_CREATE_TICKET_WITH_CREATION_SOURCE    = 'rzp_create_ticket_with_creation_source';
    const RZP_CREATE_TICKET_MOBILE_SIGNUP           = 'rzp_create_ticket_mobile_signup';

    const RZP_FETCH_OPEN_TICKETS                        = 'rzp_fetch_open_tickets';
    const RZP_CREATE_TICKET_CHECKING_CC_EMAILS          = 'rzp_create_ticket_checking_cc_emails';
    const RZP_CREATE_TICKET_SALESFORCE                  = 'rzp_create_ticket_salesforce';
    const RZP_CREATE_TICKET_INTERNAL_AUTH               = 'rzp_create_ticket_internal_auth';
    const RZP_FETCH_TICKET_FILTER                       = 'rzp_fetch_ticket_filter';
    const RZP_FETCH_TICKET_FILTER_WITH_TAGS             = 'rzp_fetch_ticket_filter_with_tags';
    const RZP_FETCH_TICKET_FILTER_AGENT                 = 'rzp_fetch_ticket_filter_agent';
    const RZP_FETCH_TICKET_FILTER_AGENT_REMOVE_INTERNAL = 'rzp_fetch_ticket_filter_agent_remove_internal';
    const RZP_FETCH_TICKET                              = 'rzp_fetch_ticket';
    const RZP_CREATE_TICKET_HTML_TAGS                   = 'rzp_create_ticket_html_tags';

    const RZP_GET_TICKET_BY_ID                 = 'rzp_get_ticket_by_id';

    const RZP_GET_AGENTS_FILTER = 'RZP_GET_AGENTS_FILTER';

    const RZP_FETCH_TICKET_FILTER_AGENT_CREATED_TICKET                = 'rzp_fetch_ticket_filter_agent_created_ticket';
    const RZP_FETCH_TICKET_FILTER_AGENT_CREATED_TICKET_WRONG_MERCHANT = 'rzp_fetch_ticket_filter_agent_created_ticket_wrong_merchant';
    const RZP_FETCH_TICKET_FILTER_PAGINATED_AGENT_CREATED_TICKET      = 'rzp_fetch_ticket_filter_paginated_agent_created_ticket';
    const RZP_FETCH_TICKET_FILTER_AGENT_CREATED_TICKET_MAPPED         = 'rzp_fetch_ticket_filter_agent_created_ticket_mapped';

    const RZP_FETCH_UNASSIGNED_TICKET_BY_ID                                     = 'rzp_fetch_unassigned_ticket_by_id';
    const RZP_FETCH_AGENT_BY_FRESHDESK_AGENT_ID                                 = 'rzp_fetch_agent_by_freshdesk_agent_id';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FreshdeskTicketV2TestData.php';

        parent::setUp();

        $this->flushCache();

        $this->setUpFreshdeskClientMock();

        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_freshdesk_tickets');

        $ticketDetails["fd_instance"] = "rzpind";

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
            'contact_mobile' => '+919876543210',
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

    protected function mockRaven()
    {
        $this->ravenMock = Mockery::mock('RZP\Services\Raven', [$this->app])->makePartial();

        $this->app->instance('raven', $this->ravenMock);
    }

    protected function expectRavenSendSmsRequest($ravenMock, $templateName, $receiver = '1234567890')
    {
        $ravenMock->shouldReceive('sendSms')
            ->times(1)
            ->with(
                Mockery::on(function ($actualPayload) use ($templateName, $receiver)
                {
                    if (($templateName !== $actualPayload['template']) or
                        ($receiver !== $actualPayload['receiver']))
                    {
                        return false;
                    }

                    return true;
                }),  true)
            ->andReturnUsing(function ()
            {
                return ['success' => true];
            });
    }

    protected function mockStork()
    {
        $this->storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $this->storkMock);
    }

    protected function expectStorkWhatsappRequest($template, $text, $destination = '+919876543210', $ownerId = '10000000000000'): void
    {
        $this->storkMock
            ->shouldReceive('request')
            ->times(1)
            ->with(
                Mockery::on(function ($actualPath)
                {
                    return true;
                }),
                Mockery::on(function ($actualContent) use ($template, $text, $destination, $ownerId)
                {
                    $message = $actualContent['message'];

                    $whatsappChannel = $message['whatsapp_channels'][0];

                    $actualOwnerId = $message['owner_id'];

                    $actualTemplate = $message['context']->template;

                    $actualText = $whatsappChannel->text;

                    $actualDestination = $whatsappChannel->destination;

                    if (($template !== $actualTemplate) or
                        ($text !== $actualText) or
                        ($destination !== $actualDestination) or
                        ($ownerId !== $actualOwnerId))
                    {
                        return false;
                    }

                    return true;
                }))
            ->andReturnUsing(function ()
            {
                $response = new \WpOrg\Requests\Response;

                $response->body = json_encode(['key' => 'value']);

                return $response;
            });
    }

    protected function shouldNotReceiveFresdeskRequest()
    {
        $this->freshdeskClientMock
            ->shouldReceive('getResponse')
            ->times(0);
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

    public function testInsertIntoDB()
    {
        $this->ba->careAppAuth();

        $this->startTest();

        $ticket = $this->getEntityById('merchant_freshdesk_tickets', 'KyvhWca25YAvF8', true);

        $this->assertEquals('349', $ticket['ticket_id']);
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
        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+custom_string%3A%27Merchant%27+AND+custom_string%3A%27Activation%27+AND+custom_string%3A%27w_action_1234%27%22&page=1', 'get',
            $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);

        $this->createTicketsToFetch();

        $this->startTest();
    }

    public function testFetchTicketsForAgentWithFilterNoCall()
    {
        $this->startTest();
    }

    public function testFetchTicketsForMerchantWithFilter()
    {
        $this->createTicketsToFetch();

        $testCases = [
            [
                'name'              => 'fetche_merchant_tickets',
                'cf_created_by'     => 'merchant',
                'fetch_response'    => self::RZP_FETCH_TICKET_FILTER,
            ],
            [
                'name'              => 'fetch_agent_tickets',
                'cf_created_by'     => 'agent',
                'fetch_response'    => self::RZP_FETCH_TICKET_FILTER_AGENT,
                'content'   =>
                    [
                    'total'   => 2,
                    'results' => [
                        [
                            'id' => 'razorpayid0013',
                        ],
                        [
                            'id' => 'razorpayid0013',
                        ],
                    ],
                    ]
            ],
            [
                'name'           => 'fetch_agent_tickets',
                'cf_created_by'  => 'agent',
                'fetch_response' => self::RZP_FETCH_TICKET_FILTER_AGENT_REMOVE_INTERNAL,
                'content'        =>
                    [
                        'total'   => 2,
                        'results' => [
                            [
                                'id' => 'razorpayid0013',
                            ],
                            [
                                'id' => 'razorpayid0013',
                            ],
                        ],
                    ]
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['request']['content']['cf_created_by'] = $testCase['cf_created_by'];

            if (empty($testCase['fetch_response']) === false)
            {
                $expectedRequestResponse    =   $this->getExpectedRequestResponse($testCase['fetch_response']);

                $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+custom_string%3A%27Merchant%27+AND+custom_string%3A%27Activation%27+AND+custom_string%3A%27' . $testCase['cf_created_by'] . '%27%22&page=1', 'get',
                                                            $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);
            }

            if (empty($testCase['content']) === false)
            {
                $this->testData[__FUNCTION__]['response']['content'] = $testCase['content'];
            }

            $this->startTest();
        }

    }

    public function testInternalFetchMerchantFreshdeskTicketsValidationError()
    {
        $this->ba->careAppAuth();

        $this->startTest();
    }

    public function testInternalFetchMerchantFreshdeskTickets()
    {
        $this->ba->careAppAuth();

        $ticketDetails["fd_instance"] = "rzpind";

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0034',
            'ticket_id'      =>  ['34'],
            'merchant_id'    => '10000000000001',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $this->startTest();
    }

    public function testFetchTicketsForMerchantWithTagFilter()
    {
        $this->createTicketsToFetch();

        $testCases = [
            [
                'name'              => 'fetch_merchant_tickets_with_tags',
                'cf_created_by'     => 'merchant',
                'fetch_response'    => self::RZP_FETCH_TICKET_FILTER_WITH_TAGS,
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['request']['content']['cf_created_by'] = $testCase['cf_created_by'];

            if (empty($testCase['fetch_response']) === false)
            {
                $expectedRequestResponse    =   $this->getExpectedRequestResponse($testCase['fetch_response']);

                $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+custom_string%3A%27Merchant%27+AND+custom_string%3A%27Activation%27+AND+custom_string%3A%27merchant%27+AND+tag%3A%27testing%27%22&page=1', 'get',
                                                            $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);
            }

            $this->startTest();
        }
    }

    public function testFetchTicketsForMerchantFailedForSomeInstance()
    {
        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+custom_string%3A%27Merchant%27+AND+custom_string%3A%27Activation%27%22&page=1', 'get',
                                                    $expectedRequestResponse['request'], $expectedRequestResponse['response'], 1);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+custom_string%3A%27Merchant%27+AND+custom_string%3A%27Activation%27%22&page=1', 'get',
                                                    $expectedRequestResponse['request'], null, 1);

        $this->createTicketsToFetch();

        $this->startTest();
    }

    public function testFetchTicketsForMerchantWithStatusOnly()
    {
        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+%28status%3A2%29%22&page=1', 'get',
            $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);

        $this->createTicketsToFetch();

        $this->startTest();
    }

    public function testFetchTicketsForMerchantInternalAuth()
    {
        $this->createTicketsToFetch();

        $auths = [
            'salesforceAuth',
            'careAuth',
        ];

        foreach ($auths as $auth)
        {
            $this->ba->$auth();

            $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET);

            $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000%22&page=1', 'get',
                $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);

            $this->startTest();
        }
    }

    public function testFetchTicketsForMerchantSalesforceWrongAuth()
    {
        // wrong the auth so that the call fails
        $this->ba->paymentLinksAuth();

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

    public function testGetConversationsForRazorpayxTicket()
    {
        $ticketDetails["fd_instance"] = "rzpx";

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0013',
            'ticket_id'      => '13',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard_x',
            'ticket_details' => $ticketDetails,
            'created_at'     => '1600000000',
            'updated_at'     => '1600000000',
        ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/13/conversations?', 'get', [], [
            [
                'id'       => 11119788088,
                'body'     => 'some random body1',
                'ticket_id'=> '13',
            ],
            [
                'id'       => 11119788089,
                'body'     => 'some random body2',
                'ticket_id'=> '13',
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

        $this->app['cache']->put('freshdesk_ticket_razorpayid0012_requester_id', 890);

        $this->startTest();
    }

    public function testReplyToTicketWithOutDataInRedis()
    {
        $this->expectFreshdeskRequestAndRespondWith('tickets/12?include=requester', 'get',
            [], [
            'requester_id' => 890,
        ]);

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

    public function testInternalReplyToTicket()
    {
        $this->ba->careAppAuth();

        $this->expectFreshdeskRequestAndRespondWith('tickets/12/reply', 'post',
            [
                'body' => 'random reply',
            ],
            [
                'id'        => 567,
                'user_id'   => 890,
                'account_id' => '10000000000000',
                'body'      => 'random reply',
                'ticket_id' => '12',

            ]);
        $this->app['cache']->put('freshdesk_ticket_razorpayid0012_requester_id', 890);

        $this->startTest();
    }

    public function testInternalReplyToTicketProhibitedShouldFail()
    {
        $this->ba->careAppAuth();

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
        $this->expectFreshdeskRequestAndRespondWith('tickets/12?include=requester', 'get',
            [
            ],
            [
                'tags'  => ['value2'],
                'id'    => '12',
            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/12', 'PUT',
        [
            'status'    => 2,
            'priority'  => 4,
            'tags'      => ['value2','new_grievance_raised'],
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

    public function testCreateTicketOpenTicketLimitExceeded()
    {
        $this->createFiveTicketsToFetch();

        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_OPEN_TICKETS);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+%28status%3A2%29%22&page=1', 'get',
                                                    $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);

        $this->mockRazorxTreatment('on');

        $this->mockPinot([
                             'plugin_transactions' => 1,
                             'total_transactions'  => 25
                         ]);

        $response = $this->startTest();
    }

    public function testCreateTicketRzpMobileSignup()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET_MOBILE_SIGNUP);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $response = $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertNotEquals('razorpayid0012', $ticket['id']);

        $this->assertNotEquals('99', $response['id']);

        $this->assertEquals($response['id'], $ticket['id']);

        $this->assertEquals('w_action_1234', $response['custom_fields']['cf_workflow_id']);

        // in this test case, we didnt have average FR response time for category+priority. so we proxied the FR time given by Freshdesk

        $this->assertEquals($frDueByFreshdeskFormat, $response['fr_due_by']);

        $this->assertEquals($frDueByFreshdeskFormat, $ticket['ticket_details']['fr_due_by']);

        $this->assertEquals('rzpind', $fdInstance);
    }

    public function testCreateTicketRzpWithHtmlTagsAndNoMerchantName()
    {
        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET_HTML_TAGS);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->fixtures->merchant->edit('10000000000000', ['name' => null]);

        $this->startTest();
    }

    public function testCreateTicketRzpWithInvalidCreationSource()
    {
        $this->startTest();
    }

    public function testCreateTicketRzpCreationSource()
    {
        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET_WITH_CREATION_SOURCE);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpind',
                                                               $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertEquals('rzpind', $fdInstance);
    }

    public function testCreateTicketRzpWithDCMigrationExperimentOn()
    {
        $this->mockRazorxTreatment('on');

        $this->mockPinot([
                             'plugin_transactions' => 1,
                             'total_transactions'  => 25
                         ]);

        $this->fixtures->edit('merchant_detail', '10000000000000', ['activation_status' => 'activated']);

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertEquals('rzpind', $fdInstance);
    }

    public function testGetTicketRzpInd()
    {
        $this->testCreateTicketRzpWithDCMigrationExperimentOn();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $this->testData[__FUNCTION__]['request']['url'] .= $ticket['id'];

        $this->testData[__FUNCTION__]['response']['content']['id'] = $ticket['id'];

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_GET_TICKET_BY_ID);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets/99?include=stats', 'GET', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateTicketRzpCheckingCCEmails()
    {
        /*Appending user emails to cc_emails only if merchant and user emails are different*/

        $frDueBy = time() + self::DAY * 2;

        $testcases = [
            [
                'cc_emails'             => ['test@razorpay.com'],
                'userAndMerchantEqual'  => false,
            ],
            [
                'cc_emails'             => [],
                'userAndMerchantEqual'  => true,
            ],
        ];

        foreach ($testcases as $testcase)
        {
            $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET_CHECKING_CC_EMAILS);

            if($testcase['userAndMerchantEqual'] === true)
            {
                $user = $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
                    [UserEntity::EMAIL => 'test@razorpay.com']);

                $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);
            }

            $expectedRequestResponse['response']['cc_emails'] = $testcase['cc_emails'];

            $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpind',
                $expectedRequestResponse['request'], $expectedRequestResponse['response']);

            $this->testData[__FUNCTION__]['response']['cc_emails'] = $testcase['cc_emails'];

            $this->startTest();
        }
    }

    public function testCreateTicketRzpSalesForce()
    {
        $this->ba->salesForceAuth();

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET_SALESFORCE);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $this->assertNotEquals('razorpayid0012', $ticket['id']);
    }

    public function testCreateTicketNotPluginMerchant()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateTicketRzp'];

        $testCases = [
            [
                'razorx'        => 'off',
            ],
            [
                'razorx'             => 'on',
                'plugin_transaction' => false,
                'druid_data'         => [
                    'plugin_transactions' => 1,
                    'total_transactions'  => 25
                ],
            ],
            [
                'razorx'             => 'on',
                'plugin_transaction' => true,
                'subject'            => 'ticket subject2',
                'druid_data'         => [
                    'plugin_transactions' => 5,
                    'total_transactions'  => 25
                ],
            ]
        ];

        foreach ($testCases as $testCase)
        {
            $this->mockRazorxTreatment($testCase['razorx']);

            $this->ba->proxyAuth();

            $this->fixtures->edit('merchant_detail', '10000000000000', ['activation_status' => 'activated']);

            $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET);

            if ($testCase['razorx'] === 'on')
            {
                $this->mockPinot($testCase['druid_data']);

                if ($testCase['plugin_transaction'] === true)
                {
                    $expectedRequestResponse['request']['tags']     = ['plugin_merchant'];
                    $expectedRequestResponse['request']['group_id'] = 1082000586150;
                }
            }

            $this->expectFreshdeskRequestAndRespondWith('tickets', 'POST',
                                                        $expectedRequestResponse['request'], $expectedRequestResponse['response'], 1);

            $response = $this->startTest();

            $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

            $this->assertNotEquals('razorpayid0012', $ticket['id']);

            $this->assertEquals('99', $response['ticket_id']);

            $this->assertEquals($response['id'], $ticket['id']);
        }
    }

    public function testCreateTicketForInternalAuth()
    {
        $beforeCount = $this->getDbEntities('merchant_freshdesk_tickets');

        $this->ba->careAppAuth();

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET_INTERNAL_AUTH);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpind',
                                                               $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $afterCount = $this->getDbEntities('merchant_freshdesk_tickets');
        // makes sure no entry is created in db
        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testGetAgentDetailForTicketInternalAuth()
    {
        $this->ba->cmmaAppAuth();

        $this->fixtures->create('admin', [
            'id'    => '6dLbNSpv5Ybbbd',
            'email' => 'agentemail@razorpay.com',
            'name'  => 'test_agent',
            'org_id'             => Org::RZP_ORG,
        ]);

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_GET_TICKET_BY_ID);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets/1234?include=requester', 'GET', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_FETCH_AGENT_BY_FRESHDESK_AGENT_ID);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('agents/123456789', 'GET', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();
    }

    public function testGetAgentsFilterInternalAuth()
    {
        $this->ba->cmmaAppAuth();

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_GET_AGENTS_FILTER);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('agents?email=vinita.nirmal%40razorpay.com', 'GET', 'rzpind',
                                                               $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();
    }

    public function testGetAgentDetailForUnassignedTicketInternalAuthFail()
    {
        $this->ba->cmmaAppAuth();

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_FETCH_UNASSIGNED_TICKET_BY_ID);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets/1234?include=requester', 'GET', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();
    }

    public function testCreateTicketRzpSol()
    {
        $testCases = [
            [
                'fd_instance' => 'rzpind',
                'razorx'      => 'on',
                'group_id'      => 14000000007644,
            ]];

        foreach ($testCases as $testCase)
        {
            $this->mockRazorxTreatment($testCase['razorx']);

            $this->ba->proxyAuth();

            $frDueBy = time() + self::DAY * 2;

            $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

            $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', $testCase['fd_instance'],
                                                                   [
                                                                       'description'   => 'ticket description',
                                                                       'subject'       => 'ticket subject',
                                                                       'status'        => 2,
                                                                       'cc_emails'     => ['a@b.com', 'merchantuser01@razorpay.com'],
                                                                       'custom_fields' => [
                                                                           'cf_requester_category'          => 'Merchant',
                                                                           'cf_requestor_subcategory'       => 'Technical support',
                                                                           'cf_requester_item'              => 'Success rate',
                                                                           'cf_merchant_id_dashboard'       => 'merchant_dashboard_10000000000000',
                                                                           'cf_merchant_id'                 => '10000000000000',
                                                                           'cf_merchant_activation_status'  => 'undefined',
                                                                           'cf_category'                    => 'New Ticket',
                                                                       ],
                                                                       'email'         => 'test@razorpay.com',
                                                                       'phone'         => '+919876543210',
                                                                       'priority'      => 1,
                                                                       'group_id'      => $testCase['group_id'],
                                                                   ],
                                                                   [
                                                                       'id'            => '99',
                                                                       'description'   => 'ticket description',
                                                                       'fr_due_by'     => $frDueByFreshdeskFormat,
                                                                       'custom_fields' => [
                                                                           'cf_requester_category'          => 'Merchant',
                                                                           'cf_requestor_subcategory'       => 'Technical support',
                                                                           'cf_requester_item'              => 'Success rate',
                                                                           'cf_merchant_id_dashboard'       => 'merchant_dashboard_10000000000000',
                                                                           'cf_merchant_activation_status'  => 'undefined',
                                                                       ],
                                                                       'priority'      => 1,
                                                                   ]);

            $this->mockPinot([
                                 'plugin_transactions' => 1,
                                 'total_transactions'  => 25
                             ]);

            $response = $this->startTest();

            $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

            $fdInstance = $ticket['ticket_details']['fd_instance'];

            $this->assertNotEquals('razorpayid0012', $ticket['id']);

            $this->assertNotEquals('99', $response['id']);

            $this->assertEquals($response['id'], $ticket['id']);

            $this->assertEquals($testCase['fd_instance'], $fdInstance);
        }
    }

    public function testCreateTicketRzpCap()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpcap',
                                                               [
                                                                   'description'   => 'ticket description',
                                                                   'subject'       => 'ticket subject',
                                                                   'cc_emails'     => ['a@b.com', 'merchantuser01@razorpay.com'],
                                                                   'status'        => 2,
                                                                   'custom_fields' => [
                                                                       'cf_requester_category'    => 'Merchant',
                                                                       'cf_requestor_subcategory' => 'Cash Advance',
                                                                       'cf_creation_source'       => 'Dashboard X',
                                                                       'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                                                                       'cf_merchant_id'           => '10000000000000',
                                                                   ],
                                                                   'email'         => 'test@razorpay.com',
                                                                   'phone'         => '+919876543210',
                                                                   'priority'      => 1,
                                                                   'group_id'      => 14000000007642,
                                                               ],
                                                               [
                                                                   'id'            => '99',
                                                                   'description'   => 'ticket description',
                                                                   'fr_due_by'     => $frDueByFreshdeskFormat,
                                                                   'custom_fields' => [
                                                                       'cf_requester_category'    => 'Merchant',
                                                                       'cf_requestor_subcategory' => 'Cash Advance',
                                                                       'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                                                                   ],
                'priority' =>  1,
            ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $response = $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertNotEquals('razorpayid0012', $ticket['id']);

        $this->assertNotEquals('99', $response['id']);

        $this->assertEquals($ticket['id'], $response['id']);

        $this->assertEquals('rzpcap', $fdInstance);

        $this->assertEquals('Cash Advance', $response['custom_fields']['cf_requester_item']);

        $this->assertEquals('Capital', $response['custom_fields']['cf_requestor_subcategory']);
    }

    public function testCreateTicketRzpCapBehindExp()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpcap',
            [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com', 'merchantuser01@razorpay.com'],
                'status'        => 2,
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Cash Advance',
                    'cf_creation_source'       => 'Dashboard X',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                    'cf_merchant_id'           => '10000000000000',
                ],
                'email'         => 'test@razorpay.com',
                'phone'         => '+919876543210',
                'priority'      => 1,
                'group_id'      => 14000000007642,
            ],
            [
                'id'            => '99',
                'description'   => 'ticket description',
                'fr_due_by'     => $frDueByFreshdeskFormat,
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Cash Advance',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'priority' =>  1,
            ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $response = $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertNotEquals('razorpayid0012', $ticket['id']);

        $this->assertNotEquals('99', $response['id']);

        $this->assertEquals($ticket['id'], $response['id']);

        $this->assertEquals('rzpcap', $fdInstance);

        $this->assertEquals('Capital', $response['custom_fields']['cf_requester_item']);

        $this->assertEquals('Cash Advance', $response['custom_fields']['cf_requestor_subcategory']);
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    public function testCreateTicketRzpCapViaX()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpcap',
                                                               [
                'description'=>'ticket description',
                'subject'=>'[Merchant] Corporate Credit Cards',
                'custom_fields'=>[
                    'cf_merchant_id'=>'10000000000000',
                    'cf_category'=>'RazorpayX',
                    'cf_requestor_category'=>'Merchant',
                    'cf_query'=>'Corporate Credit Cards',
                    'cf_ticket_queue'=>'RazorpayX',
                    'cf_merchant_id_dashboard'=>'merchant_dashboard_10000000000000'
                ],
                'cc_emails'=>[
                    'a@b.com',
                    'merchantuser01@razorpay.com',
                    'merchantuser01@razorpay.com'
                ],
                'email'=>'test@razorpay.com',
                'phone'=>'+919876543210',
                'priority'=>1,
                'status'=>2
            ],
                                                               [
                'id'            => '99',
                'description'   => 'ticket description',
                'fr_due_by'     => $frDueByFreshdeskFormat,
                'custom_fields' => [
                    'cf_query'                    =>  'Corporate Credit Cards',
                ],
                'priority' =>  1,
            ]);

        $response = $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertNotEquals('razorpayid0012', $ticket['id']);

        $this->assertNotEquals('99', $response['id']);

        $this->assertEquals($ticket['id'], $response['id']);

        $this->assertEquals('rzpcap', $fdInstance);
    }

    public function testCreateTicketRzpCapViaXForLimit()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST','rzpcap',
            [
                'subject'=>'[Merchant] Higher Corporate Card Spend Limit',
                'description'=>'Requested Limit: 50005\\nReason: test for payload',
                'custom_fields'=>[
                    'cf_product'=>'Corporate Credit Cards',
                    'cf_merchant_id_dashboard'=>'merchant_dashboard_10000000000000'
                ],
                'cc_emails'=>[
                    'a@b.com',
                    'merchantuser01@razorpay.com',
                    'merchantuser01@razorpay.com'
                ],
                'email'=>'test@razorpay.com',
                'phone'=>'+919876543210',
                'priority'=>1,
                'status'=>2
            ],
            [
                'id'            => '99',
                'description'   => 'Requested Limit: ₹50005\nReason: test for payload',
                'fr_due_by'     => $frDueByFreshdeskFormat,
                'custom_fields' => [
                    'cf_product'                    =>  'Corporate Credit Cards',
                ],
                'priority' =>  1,
            ]);

        $response = $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertNotEquals('razorpayid0012', $ticket['id']);

        $this->assertNotEquals('99', $response['id']);

        $this->assertEquals($ticket['id'], $response['id']);

        $this->assertEquals('rzpcap', $fdInstance);
    }

    public function testCreateTicketRzpX()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpx',
                                                               [
                                                                   'description'   => 'ticket description',
                                                                   'subject'       => 'ticket subject',
                                                                   'cc_emails'     => ['a@b.com', 'merchantuser01@razorpay.com'],
                                                                   'custom_fields' => [
                                                                       'cf_requester_category'    => 'Merchant',
                                                                       'cf_requestor_subcategory' => 'Activation',
                                                                       'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                                                                   ],
                                                                   'email'         => 'test@razorpay.com',
                                                                   'phone'         => '+919876543210',
                                                                   'priority'      => 1,
                                                                   'status'        => 2,
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

        $this->assertEquals('rzpx', $fdInstance);

    }

    public function testCreateTicketRzpXMobileSignUp()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpx',
                                                               [
                                                                   'description'   => 'ticket description',
                                                                   'subject'       => 'ticket subject',
                                                                   'cc_emails'     => ['a@b.com'],
                                                                   'custom_fields' => [
                                                                       'cf_requester_category'    => 'Merchant',
                                                                       'cf_requestor_subcategory' => 'Activation',
                                                                       'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                                                                   ],
                                                                   'name'          => 'test_name',
                                                                   'phone'         => '+919876543210',
                                                                   'priority'      => 1,
                                                                   'status'        => 2,
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

        $this->fixtures->merchant->edit('10000000000000', ['signup_via_email' => 0]);

        $this->fixtures->user->edit('MerchantUser01', ['signup_via_email' => 0]);

        $this->fixtures->merchant->edit('10000000000000', ['name' => 'test_name']);

        $response = $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $fdInstance = $ticket['ticket_details']['fd_instance'];

        $this->assertNotEquals('razorpayid0012', $ticket['id']);

        $this->assertNotEquals('99', $response['id']);

        $this->assertEquals($response['id'], $ticket['id']);

        $this->assertEquals('rzpx', $fdInstance);

    }

    public function testCreateTicketForAUserWithoutNameRzpX()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST','rzpx',
            [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com', 'merchantuser01@razorpay.com'],
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'email'         => 'user@razorpay.com',
                'priority'      => 1,
                'name'          => '',
                'phone'         => '1234567890',
                'status'        => 2,
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

        $this->assertEquals('rzpx', $fdInstance);

    }

    public function testCreateTicketForUserRzpX()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST','rzpx',
            [
                'description'   => 'ticket description',
                'subject'       => 'ticket subject',
                'cc_emails'     => ['a@b.com', 'merchantuser01@razorpay.com'],
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'email'         => 'user@razorpay.com',
                'phone'         => '1234567890',
                'priority'      => 1,
                'status'        => 2,
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

        $this->assertEquals('rzpx', $fdInstance);

    }

    public function testCreateTicketFreshdeskError()
    {
        $this->expectFreshdeskRequestAndRespondWith('tickets', 'POST',
                                                    [
                                                        'description'   => 'ticket description',
                                                        'subject'       => 'ticket subject',
                                                        'cc_emails'     => ['a@b.com', 'merchantuser01@razorpay.com'],
                                                        'status'        => 2,
                                                        'custom_fields' => [
                                                            'cf_requester_category'         => 'Invalid',
                                                            'cf_requestor_subcategory'      => 'activation',
                                                            'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                                                            'cf_merchant_id'                => '10000000000000',
                                                            'cf_merchant_activation_status' => 'undefined',
                                                            'cf_category'                   => 'New Ticket',
                                                        ],
                                                        'email'         => 'test@razorpay.com',
                                                        'phone'         => '+919876543210',
                                                        'priority'      => 1,
                                                    ], [
                                                        'description' => 'Validation failed',
                                                        'errors'      => [
                                                            [
                                                                'field'  => 'custom_fields.cf_requester_category',
                                                                'values' => 'Merchant,Customer,Service request,Prospect,Other,Partner',
                                                                'code'   => 'invalid value',
                                                            ]
                                                        ]
                                                    ]);

        $this->startTest();
    }

    public function testCreateTicketWithAttachment()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateTicketRzp'];

        $this->addAttachmentToRequest(__FUNCTION__, 'abc.jpg');

        $this->freshdeskClientMock
            ->shouldReceive('makeCurlRequest')
            ->times(1)
            ->andReturnUsing(function () {
                return [json_encode([
                    'id'            => '99',
                    'description'   => 'ticket description',
                    'fr_due_by'     => '2020-11-30T16:52:00Z',
                    'custom_fields' => [
                        'cf_requester_category'    => 'Merchant',
                        'cf_requestor_subcategory' => 'Activation',
                        'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                    ],
                    'priority' =>  1,
                ]),200];
            });

        $this->startTest();
    }

    public function testCreateTicketWithWhatsappNotification()
    {
        $this->mockRazorxTreatment('on');

        $this->mockStork();

        $this->mockRaven();

        $this->mockPinot([
                             'plugin_transactions' => 1,
                             'total_transactions'  => 25
                         ]);

        $this->expectRavenSendSmsRequest($this->ravenMock, 'sms.support.ticket_created', '+919876543210');

        $url = $this->app['config']->get('applications.dashboard.url');

        $this->expectStorkWhatsappRequest('support.ticket_created',
        'Hi, '.PHP_EOL.
        'Thank you for reaching out. This is to inform you that your ticket number 99 has been registered. Our team is working on your request and will get back to you within 3 working days. You can track your ticket updates by logging into the dashboard : '. $url .' '.PHP_EOL.
        'Team Razorpay');

        $this->testData[__FUNCTION__] = $this->testData['testCreateTicketRzp'];

        $this->fixtures->edit('merchant_detail', '10000000000000', ['activation_status' => 'activated']);

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();
    }

    public function testCreateTicketInvalidAttachmentExtension()
    {
        $this->addAttachmentToRequest(__FUNCTION__, 'a.exe');

        $this->startTest();
    }

    public function testReceiveFreshdeskWebhookToNotifyMerchant()
    {
        $this->ba->freshdeskWebhookAuth();

        $this->mockRazorxTreatment('on');

        $url = $this->app['config']->get('applications.dashboard.url');

        $testcases = [
            [
                'event'         => 'TICKET_DELAY_UPDATE_72HRS',
                'expected_text' => 'Hi, '.PHP_EOL.
                    'We are sorry about the delay regarding your ticket 12. We will revert back to you with a resolution for the same in the next 72 hrs. Please bear with us. You can track your ticket updates by logging into the dashboard : '. $url .' '.PHP_EOL.
                    'Team Razorpay',
            ],
            [
                'event'         => 'TICKET_DELAY_UPDATE_24HRS',
                'expected_text' => 'Hi, '.PHP_EOL.
                    'We are sorry about the delay regarding your ticket 12. We will revert back to you with a resolution for the same in the next 24 hrs. Please bear with us. You can track your ticket updates by logging into the dashboard : '. $url .' '.PHP_EOL.
                    'Team Razorpay',
            ],
            [
                'event'         => 'TICKET_DETAILS_PENDING',
                'expected_text' => 'Hi, '.PHP_EOL.
                    'We require a few details from you on the ticket 12. Request you to check and respond with the details for us to resolve the concern raised. You can track your ticket updates by logging into the dashboard : '. $url .' '.PHP_EOL.
                    'Team Razorpay',
            ],
            [
                'event'         => 'TICKET_RESOLVED',
                'expected_text' => 'Hi, '.PHP_EOL.
                    'Your issue regarding the ticket 12 has been resolved and a response has been sent over to you. If you are not satisfied with the resolution provided, feel free to reopen the ticket by replying to the same ticket. You can track your ticket updates by logging into the dashboard : '. $url .' '.PHP_EOL.
                    'Team Razorpay',
            ],
            [
                'event'         => 'TICKET_REOPENED',
                'expected_text' => 'Hi, '.PHP_EOL.
                    'We believe that your issue regarding the ticket 12 is still not resolved. Your ticket has been reopened and our team will take it up on priority and get back to you within 24 hrs. You can track your ticket updates by logging into the dashboard : '. $url .' '.PHP_EOL.
                    'Team Razorpay',
            ],
        ];

        foreach ($testcases as $testcase)
        {
            $expectedTemplate = 'support.'. strtolower($testcase['event']);

            $smsTemplate = 'sms.'.$expectedTemplate;

            $this->mockStork();

            $this->mockRaven();

            $this->expectStorkWhatsappRequest($expectedTemplate, $testcase['expected_text']);

            $this->expectRavenSendSmsRequest($this->ravenMock, $smsTemplate, '+919876543210');

            $this->testData[__FUNCTION__]['request']['content']['event'] = $testcase['event'];

            $this->startTest();
        }
    }

    public function testReceiveFreshdeskWebhookToNotifyMerchantInvalidEvent()
    {
        $this->ba->freshdeskWebhookAuth();

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

    public function testFreshdeskWebhookGetAgentCreatedTicket()
    {
        $testcases = [
            [
                'name'                          => 'pagination',
            ],
            [
                'name'                          => 'general',
            ],
        ];

        $fixedTime = (new Carbon())->timestamp(1583548200);

        Carbon::setTestNow($fixedTime);

        foreach ($testcases as $testcase)
        {
            $this->setupTestDataGetAgentCreatedTickets($testcase['name']);

            $ticketBeforeTest = $this->getLastEntity('merchant_freshdesk_tickets', true, 'live');

            $this->ba->cronAuth('live');

            $this->startTest();

            $ticketAfterTest = $this->getLastEntity('merchant_freshdesk_tickets', true, 'live');

            $this->assertNotEquals($ticketBeforeTest['id'], $ticketAfterTest['id']);

            $this->assertEquals('agent', $ticketAfterTest['created_by']);
        }
    }

    public function testFreshdeskWebhookGetAgentCreatedTicketFailed()
    {
        $this->testData[__FUNCTION__] = $this->testData['testFreshdeskWebhookGetAgentCreatedTicket'];

        $fixedTime = (new Carbon())->timestamp(1583548200);

        Carbon::setTestNow($fixedTime);

        $this->setupTestDataGetAgentCreatedTickets('merchant_doesnt_exist');

        $ticketBeforeTest = $this->getLastEntity('merchant_freshdesk_tickets', true, 'live');

        $this->ba->cronAuth('live');

        $this->startTest();

        $ticketAfterTest = $this->getLastEntity('merchant_freshdesk_tickets', true, 'live');

        $this->assertEquals($ticketBeforeTest['id'], $ticketAfterTest['id']);
    }

    public function testFreshdeskWebhookGetAgentCreatedTicketMappedAlready()
    {
        $this->testData[__FUNCTION__] = $this->testData['testFreshdeskWebhookGetAgentCreatedTicket'];

        $fixedTime = (new Carbon())->timestamp(1583548200);

        Carbon::setTestNow($fixedTime);

        $ticketDetails["fd_instance"] = "rzpind";

        $this->fixtures->on('live')->create('merchant_freshdesk_tickets', [
            'id'             => 'razoridind0017',
            'ticket_id'      => '17',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $ticketDetails["fd_instance"] = "rzpcap";

        $this->fixtures->on('live')->create('merchant_freshdesk_tickets', [
            'id'             => 'razoridcap0017',
            'ticket_id'      => '17',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $ticketBeforeTest = $this->getLastEntity('merchant_freshdesk_tickets', true, 'live');

        $this->ba->cronAuth('live');

        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET_FILTER_AGENT_CREATED_TICKET_MAPPED);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22created_at%3A%272020-03-07%27+AND+custom_string%3A%27agent%27%22&page=1', 'get',
                                                    $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);

        $this->startTest();

        $ticketAfterTest = $this->getLastEntity('merchant_freshdesk_tickets', true, 'live');

        $this->assertEquals($ticketBeforeTest['id'], $ticketAfterTest['id']);
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
        $this->assertEqualsWithDelta(2.5 * self::DAY, $firstResponseTimeAverage, 5000, '');
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

        $this->ba->freshdeskWebhookAuth();

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
                                                        'description'   => 'ticket description',
                                                        'status'        => 2,
                                                        'custom_fields' => [
                                                            'cf_requester_category'         => 'Merchant',
                                                            'cf_requestor_subcategory'      => 'Activation',
                                                            'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                                                            'cf_merchant_id'                => '10000000000000',
                                                            'cf_merchant_activation_status' => 'undefined',
                                                            'cf_category'                   => 'New Ticket',
                                                        ],
                                                        'priority'      => 1,
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
                                                        'priority'      => 1,
                                                    ]);

        $response = $this->startTest();

        $frDueByBasedOnWeekAverage = $now + self::DAY * 4;

        $frDueByBasedOnWeekAverageFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueByBasedOnWeekAverage);

        $this->assertEquals(strtotime($frDueByBasedOnWeekAverageFreshdeskFormat), strtotime($response['fr_due_by']), '', 100);
    }

    public function testReceiveFreshdeskWebhookOnTicketStatusUpdate()
    {
        $testCases = [
            [
                'ticket_id'   => '12',
                'fd_instance' => 'rzpind',
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['request']['content']['ticket_id'] = $testCase['ticket_id'];

            $this->testData[__FUNCTION__]['request']['content']['ticket_details']['fd_instance'] = $testCase['fd_instance'];

            $this->ba->freshdeskWebhookAuth();

            $this->startTest();

            $ticket = $this->getDbEntityById('merchant_freshdesk_tickets', 'razorpayid0012');

            $this->assertEquals(5 , $ticket['status']);

        }
    }

    public function testReceiveFreshdeskWebhookOnTicketCreated()
    {
        $testCases = [
            [
               'ticket_id'     => '1234',
               'fd_instance'   => 'rzpind',
            ],
            [
                'ticket_id'     => '12345',
                'fd_instance'   => 'rzpind',
            ],
            [
                'ticket_id'     => '12345',
                'fd_instance'   => 'rzpind',
                'created_by'    => 'agent',
                'status'        =>  'Open',
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->ba->freshdeskWebhookAuth();

            $this->testData[__FUNCTION__]['request']['content']['ticket_id'] = $testCase['ticket_id'];

            $this->testData[__FUNCTION__]['request']['content']['ticket_details']['fd_instance'] = $testCase['fd_instance'];

            if (empty($testCase['created_by']) === false)
            {
                $this->testData[__FUNCTION__]['request']['content']['created_by'] = $testCase['created_by'];

                $this->mockRazorxTreatment('on');

                $this->mockStork();

                $this->mockRaven();

                $this->expectRavenSendSmsRequest($this->ravenMock, 'sms.support.agent_ticket_created', '+919876543210');

                $url = $this->app['config']->get('applications.dashboard.url');

                $this->expectStorkWhatsappRequest('support.agent_ticket_created',
                                                  'Hi, '.PHP_EOL.
                                                  'Our team has raised a new service request that requires your action. Please respond sooner for a faster resolution. You can track and reply to the service request by logging into the dashboard : '. $url .' '.PHP_EOL.
                                                  'Team Razorpay');
            }
            if (empty($testCase['status']) === false)
            {
                $this->testData[__FUNCTION__]['request']['content']['status'] = $testCase['status'];
            }

            $this->startTest();

            $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

            $this->assertArraySelectiveEquals([
                                                  'merchant_id'       => '10000000000000',
                                                  'ticket_id'         => $testCase['ticket_id'],
                                                  'type'              => 'support_dashboard',
                                                  'ticket_details'    => [
                                                      'fd_instance'       => $testCase['fd_instance'],
                                                      'fr_due_by'         => '2020-12-08T16:04:20Z',
                                                  ],
                                              ], $ticket);

            if (empty($testCase['status']) === false)
            {
                $this->assertEquals(2, $ticket['status']);
            }
            if (empty($testCase['created_by']) === false)
            {
                $this->assertEquals($testCase['created_by'], $ticket['created_by']);
            }
        }
    }

    public function testGetFreshdeskTicketCareApp()
    {
        $this->ba->careAuth();

        $this->expectFreshdeskRequestAndRespondWith('tickets/12?include=stats', 'get',
            [
            ],
            [
                'key2' => 'value2',
            ]);

        $this->startTest();
    }

    public function testUpdateFreshdeskTicketInternalSuccess()
    {
        $this->ba->careAppAuth();

        $this->expectFreshdeskRequestAndRespondWith('tickets/12', 'put',
        [
            'key1' => 'value1',
        ],
        [
            'id' => 'value2',
        ]);

        $this->startTest();
    }

    public function testUpdateFreshdeskTicketInternalFailed()
    {
        $this->ba->careAppAuth();

        $this->expectFreshdeskRequestAndRespondWith('tickets/12', 'put',
                                                    [
                                                        'key1' => 'value1',
                                                    ],
                                                    [
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

    protected function checkFreshdeskCorrectInstanceCallAndRespondWith($expectedPath,
                                                                       $expectedMethod,
                                                                       $expectedFdInstance,
                                                                       $expectedContent,
                                                                       $respondWith = [],
                                                                       $times = 1)
    {
        $expectedUrl1 = $this->app['config']->get('applications.freshdesk.url') . '/' . $expectedPath;

        $expectedUrlInd = $this->app['config']->get('applications.freshdesk.urlind') . '/' . $expectedPath;

        $expectedUrl2 = $this->app['config']->get('applications.freshdesk.url2') . '/' . $expectedPath;

        $expectedUrlx = $this->app['config']->get('applications.freshdesk.urlx') . '/' . $expectedPath;

        $expectedUrlCap = $this->app['config']->get('applications.freshdesk.urlcap') . '/' . $expectedPath;

        $expectedUrls = [
            'rzpind'    => $expectedUrlInd,
            'rzpsol'    => $expectedUrl2,
            'rzpx'      => $expectedUrlx,
            'rzpcap'    => $expectedUrlCap
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
                $response = new \WpOrg\Requests\Response;

                $response->body = json_encode($respondWith);

                return $response;
            });

    }

    protected function addAttachmentToRequest(string $caller, string $filename, int $size = 1)
    {
        $uploadedFile = UploadedFile::fake()->create($filename, $size);

        $this->testData[$caller]['request']['files']['attachments'] = $this->testData[$caller]['request']['files']['attachments'] ?? [];

        array_push($this->testData[$caller]['request']['files']['attachments'], $uploadedFile);
    }

    protected function getExpectedRequestResponse(string $key)
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        if ($key === self::RZP_CREATE_TICKET)
        {
            return [
                'request'   =>  [
                    'description' => 'ticket description',
                    'subject' => 'ticket subject',
                    'cc_emails' => ['a@b.com','merchantuser01@razorpay.com'],
                    'custom_fields' => [
                        'cf_requester_category'         => 'Merchant',
                        'cf_requestor_subcategory'      => 'Activation',
                        'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'                => '10000000000000',
                        'cf_merchant_activation_status' => 'activated',
                        'cf_category'                   => 'New Ticket',
                    ],
                    'email' =>  'test@razorpay.com',
                    'phone' => '+919876543210',
                    'priority' =>  1,
                ],
                'response'  =>
                [
                    'id'            => '99',
                    'description'   => 'ticket description',
                    'fr_due_by'     => $frDueByFreshdeskFormat,
                    'custom_fields' => [
                        'cf_requester_category'         => 'Merchant',
                        'cf_requestor_subcategory'      => 'Activation',
                        'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'                => '10000000000000',
                        'cf_merchant_activation_status' => 'activated',
                    ],
                    'priority' =>  1,
                ]
            ];
        }

        if ($key === self::RZP_CREATE_TICKET_WITH_CREATION_SOURCE)
        {
            return [
                'request'   =>  [
                    'description' => 'ticket description',
                    'subject' => 'ticket subject',
                    'cc_emails' => ['a@b.com','merchantuser01@razorpay.com'],
                    'custom_fields' => [
                        'cf_requester_category'         => 'Merchant',
                        'cf_requestor_subcategory'      => 'Activation',
                        'cf_creation_source'            => 'Dashboard',
                        'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'                => '10000000000000',
                        'cf_merchant_activation_status' => 'undefined',
                        'cf_category'                   => 'New Ticket',
                    ],
                    'email' =>  'test@razorpay.com',
                    'phone' => '+919876543210',
                    'priority' =>  1,
                ],
                'response'  =>
                    [
                        'id'            => '99',
                        'description'   => 'ticket description',
                        'fr_due_by'     => $frDueByFreshdeskFormat,
                        'custom_fields' => [
                            'cf_requester_category'         => 'Merchant',
                            'cf_requestor_subcategory'      => 'Activation',
                            'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                            'cf_merchant_id'                => '10000000000000',
                            'cf_creation_source'            => 'Dashboard',
                            'cf_merchant_activation_status' => 'undefined',
                        ],
                        'priority' =>  1,
                    ]
            ];
        }

        if ($key === self::RZP_CREATE_TICKET_MOBILE_SIGNUP)
        {
            return [
                'request'  => [
                    'description'   => 'ticket description',
                    'subject'       => 'ticket subject',
                    'status'        => 2,
                    'custom_fields' => [
                        'cf_requester_category'         => 'Merchant',
                        'cf_requestor_subcategory'      => 'Activation',
                        'cf_workflow_id'                => 'w_action_1234',
                        'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'                => '10000000000000',
                        'cf_merchant_activation_status' => 'undefined',
                        'cf_category'                   => 'New Ticket',
                    ],
                    'tags'          => ['workflow_ticket'],
                    'phone'         => '+919876543210',
                    'priority'      => 1,
                ],
                'response' =>
                    [
                        'id'            => '99',
                        'description'   => 'ticket description',
                        'fr_due_by'     => $frDueByFreshdeskFormat,
                        'custom_fields' => [
                            'cf_requester_category'         => 'Merchant',
                            'cf_requestor_subcategory'      => 'Activation',
                            'cf_workflow_id'                => 'w_action_1234',
                            'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                            'cf_merchant_id'                => '10000000000000',
                            'cf_merchant_activation_status' => 'undefined',
                        ],
                        'tags'          => ['workflow_ticket'],
                        'priority' =>  1,
                    ]
            ];
        }

        if ($key === self::RZP_CREATE_TICKET_HTML_TAGS)
        {
            return [
                'request'   => [
                    'description'   => '<br>Ticket<b>Description</b><br>HTML',
                    'subject'       => 'ticket subject',
                    'cc_emails'     => ['a@b.com', 'merchantuser01@razorpay.com'],
                    'status'        => 2,
                    'custom_fields' => [
                        'cf_requester_category'         => 'Merchant',
                        'cf_requestor_subcategory'      => 'Activation',
                        'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'                => '10000000000000',
                        'cf_merchant_activation_status' => 'undefined',
                        'cf_category'                   => 'New Ticket',
                    ],
                    'email'         => 'test@razorpay.com',
                    'phone'         => '+919876543210',
                    'priority'      => 1,
                ],
                'response'  =>
                    [
                        'id'            => '99',
                        'description'   => '<br>Ticket<b>Description</b><br>HTML',
                        'fr_due_by'     => $frDueByFreshdeskFormat,
                        'custom_fields' => [
                            'cf_requester_category'         => 'Merchant',
                            'cf_requestor_subcategory'      => 'Activation',
                            'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                            'cf_merchant_id'                => '10000000000000',
                            'cf_merchant_activation_status' => 'undefined',
                            'cf_category'                   => 'New Ticket',
                        ],
                        'priority' =>  1,
                    ]
            ];
        }

        if ($key === self::RZP_CREATE_TICKET_CHECKING_CC_EMAILS)
        {
            return [
                'request'   =>  [
                    'description' => 'ticket description',
                    'subject' => 'ticket subject',
                ],
                'response'  =>
                    [
                        'id'            => '99',
                        'description'   => 'ticket description',
                        'fr_due_by'     => $frDueByFreshdeskFormat,
                        'status'        => 2,
                        'custom_fields' => [
                            'cf_requester_category'         => 'Merchant',
                            'cf_requestor_subcategory'      => 'Activation',
                            'cf_merchant_id'                => '10000000000000',
                            'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                            'cf_merchant_activation_status' => 'undefined',
                            'cf_category'                   => 'New Ticket',
                        ],
                        'priority'      => 1,
                    ]
            ];
        }

        if ($key === self::RZP_CREATE_TICKET_SALESFORCE)
        {
            return [
                'request'   => [
                    'description'   => 'ticket description',
                    'subject'       => 'ticket subject',
                    'status'        => 2,
                    'cc_emails'     => ['a@b.com'],
                    'custom_fields' => [
                        'cf_requester_category'         => 'Merchant',
                        'cf_requestor_subcategory'      => 'Activation',
                        'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'                => '10000000000000',
                        'cf_merchant_activation_status' => 'undefined',
                        'cf_category'                   => 'New Ticket',
                    ],
                    'email'         => 'test@razorpay.com',
                    'phone'         => '+919876543210',
                    'priority'      => 1,
                ],
                'response'  =>
                    [
                        'id'            => '99',
                        'description'   => 'ticket description',
                        'fr_due_by'     => $frDueByFreshdeskFormat,
                        'custom_fields' => [
                            'cf_requester_category'         => 'Merchant',
                            'cf_requestor_subcategory'      => 'Activation',
                            'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                            'cf_merchant_id'                => '10000000000000',
                            'cf_merchant_activation_status' => 'undefined',
                        ],
                        'priority' =>  1,
                    ]
            ];
        }
        if ($key === (self::RZP_CREATE_TICKET_INTERNAL_AUTH))
        {
            return [
                'request'  => [
                    'description'   => 'ticket description',
                    'subject'       => 'ticket subject',
                    'custom_fields' => [
                        'cf_requester_category'         => 'Merchant',
                        'cf_requestor_subcategory'      => 'Call Requested',
                        'cf_merchant_id'                => '10000000000000',
                        'cf_merchant_activation_status' => 'undefined',
                        'cf_category'                   => 'New Ticket',
                    ],
                    'email'         => 'test@razorpay.com',
                    'priority'      => '4',
                ],
                'response' =>
                    [
                        'id'            => '99',
                        'description'   => 'ticket description',
                        'fr_due_by'     => $frDueByFreshdeskFormat,
                        'custom_fields' => [
                            'cf_requester_category'    => 'Merchant',
                            'cf_requestor_subcategory' => 'Activation',
                            'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                            'cf_merchant_id'           => '10000000000000',
                        ],
                        'priority'      => 4,
                    ]
            ];
        }
        else if ($key === self::RZP_GET_TICKET_BY_ID)
        {
            return [
                'request'  => [],
                'response' => [
                    'id'            => '99',
                    'description'   => 'ticket description',
                    'fr_due_by'     => $frDueByFreshdeskFormat,
                    'custom_fields' => [
                        'cf_requester_category'    => 'Merchant',
                        'cf_requestor_subcategory' => 'Activation',
                        'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'           => '10000000000000',
                    ],
                    'priority'      => 1,
                    'responder_id'  => 123456789,
                ],

            ];
        }
        else if ($key === self::RZP_GET_AGENTS_FILTER)
        {
            return [
                'request'  => [],
                'response' => [[
                                   "available"       => false,
                                   "occasional"      => false,
                                   "id"              => 14000004891643,
                                   "ticket_scope"    => 1,
                                   "created_at"      => "2021-09-20T08:42:13Z",
                                   "updated_at"      => "2022-08-26T09:56:37Z",
                                   "last_active_at"  => "2022-08-26T09:56:37Z",
                                   "available_since" => "2022-04-25T08:32:16Z",
                                   "type"            => "support_agent",
                                   "contact"         => [
                                       "active"        => true,
                                       "email"         => "vinita.nirmal@razorpay.com",
                                       "job_title"     => null,
                                       "language"      => "en",
                                       "last_login_at" => "2022-08-25T07:41:22Z",
                                       "mobile"        => null,
                                       "name"          => "Vinita.nirmal",
                                       "phone"         => null,
                                       "time_zone"     => "New Delhi",
                                       "created_at"    => "2021-09-20T08:42:12Z",
                                       "updated_at"    => "2021-09-20T08:49:09Z"
                                   ],
                                   "signature"       => "<div dir=\"ltr\"><p><br><\/p>\n<\/div>"

                               ]],

            ];
        }
        else if ($key === self::RZP_FETCH_OPEN_TICKETS)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => 35,
                            'body'      => 'some random body 12',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => 36,
                            'body'      => 'some random body 34',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Merchant Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => 37,
                            'body'      => 'some random body 37',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                        [
                            'id'        => 38,
                            'body'      => 'some random body 38',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                        [
                            'id'        => 39,
                            'body'      => 'some random body 39',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_TICKET)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => 12,
                            'body'      => 'some random body 12',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_workflow_id"            => "w_action_1234",
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => 34,
                            'body'      => 'some random body 34',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_workflow_id"            => "w_action_1234",
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            // 56 is not mapped to this merchant in our db. so we don't show it in the response, even if Freshdesk somehow returned this in the response
                            'id'        => 56,
                            'body'      => 'some random body 56',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_workflow_id"            => "w_action_1234",
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                        [
                            // 78 is not mapped to 'support_dashboard' in our db. so we don't show it in the response
                            'id'        => 78,
                            'body'      => 'some random body 78',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_workflow_id"            => "w_action_1234",
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_TICKET_FILTER_WITH_TAGS)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => 12,
                            'body'      => 'some random body 12',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'tags' => [
                                'testing',
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => 34,
                            'body'      => 'some random body 34',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Merchant Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'tags' => [
                                'testing',
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            // 56 is not mapped to this merchant in our db. so we don't show it in the response, even if Freshdesk somehow returned this in the response
                            'id'        => 56,
                            'body'      => 'some random body 56',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'tags' => [
                                'testing',
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                        [
                            // 78 is not mapped to 'support_dashboard' in our db. so we don't show it in the response
                            'id'        => 78,
                            'body'      => 'some random body 78',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'tags' => [
                                'testing',
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_TICKET_FILTER)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => 12,
                            'body'      => 'some random body 12',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => 34,
                            'body'      => 'some random body 34',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Merchant Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            // 56 is not mapped to this merchant in our db. so we don't show it in the response, even if Freshdesk somehow returned this in the response
                            'id'        => 56,
                            'body'      => 'some random body 56',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                        [
                            // 78 is not mapped to 'support_dashboard' in our db. so we don't show it in the response
                            'id'        => 78,
                            'body'      => 'some random body 78',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "merchant"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_TICKET_FILTER_AGENT)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => 13,
                            'body'      => 'some random body 13',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => 34,
                            'body'      => 'some random body 34',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Merchant Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            // 56 is not mapped to this merchant in our db. so we don't show it in the response, even if Freshdesk somehow returned this in the response
                            'id'        => 56,
                            'body'      => 'some random body 56',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                        [
                            // 78 is not mapped to 'support_dashboard' in our db. so we don't show it in the response
                            'id'        => 78,
                            'body'      => 'some random body 78',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_TICKET_FILTER_AGENT_REMOVE_INTERNAL)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => 13,
                            'body'      => 'some random body 13',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => 34,
                            'body'      => 'some random body 34',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Merchant Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            // 56 is not mapped to this merchant in our db. so we don't show it in the response, even if Freshdesk somehow returned this in the response
                            'id'        => 56,
                            'body'      => 'some random body 56',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                        [
                            // 78 is not mapped to 'support_dashboard' in our db. so we don't show it in the response
                            'id'        => 78,
                            'body'      => 'some random body 78',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                        [
                            // 79 is an internal Ticket so it should not come in the result
                            'id'        => 79,
                            'body'      => 'some random body 78',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "Internal"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',
                        ],
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_TICKET_FILTER_AGENT_CREATED_TICKET)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => "13",
                            'body'      => 'some random body 13',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_ticket_queue"           => "xyz",
                                "cf_merchant_id"            => "10000000000000"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => "34",
                            'body'      => 'some random body 34',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Merchant Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_merchant_id"            => "10000000000000",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => "34",
                            'body'      => 'some random body 35',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Merchant Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_merchant_id"            => "10000000000000",
                                "cf_ticket_queue"           => "Internal"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_TICKET_FILTER_AGENT_CREATED_TICKET_WRONG_MERCHANT)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => "13",
                            'body'      => 'some random body 13',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_merchant_id"            => "middoesntexist",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => "34",
                            'body'      => 'some random body 34',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Merchant Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_merchant_id"            => "middoesntexist",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_TICKET_FILTER_PAGINATED_AGENT_CREATED_TICKET)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => "13",
                            'body'      => 'some random body',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_merchant_id"            => "10000000000000",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_TICKET_FILTER_AGENT_CREATED_TICKET_MAPPED)
        {
            return [
                'request'  => [],
                'response' => [
                    'results' => [
                        [
                            'id'        => "17",
                            'body'      => 'some random body 17',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
                                "cf_created_by"             => "agent",
                                "cf_merchant_id"            => "10000000000000",
                                "cf_ticket_queue"           => "xyz"
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ]
                    ],

                ]];
        }
        else if ($key === self::RZP_FETCH_UNASSIGNED_TICKET_BY_ID)
        {
            return [
                'request'  => [],
                'response' => [
                    'id'            => '99',
                    'description'   => 'ticket description',
                    'fr_due_by'     => $frDueByFreshdeskFormat,
                    'custom_fields' => [
                        'cf_requester_category'    => 'Merchant',
                        'cf_requestor_subcategory' => 'Activation',
                        'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'           => '10000000000000',
                    ],
                    'priority'      => 1,
                    'responder_id'  => null,
                ],
            ];
        }
        else if ($key === self::RZP_FETCH_AGENT_BY_FRESHDESK_AGENT_ID)
        {
            return [
                'request'  => [],
                'response' => [
                    'id'            => '123456789',
                    'contact' => [
                        'email'         => 'agentemail@razorpay.com',
                    ],
                ],
            ];
        }
    }

    protected function createFiveTicketsToFetch()
    {
        $ticketDetails["fd_instance"] = "rzpind";

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0035',
            'ticket_id'      => '35',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0036',
            'ticket_id'      => '36',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0037',
            'ticket_id'      => '37',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0038',
            'ticket_id'      => '38',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0039',
            'ticket_id'      => '39',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);
    }

    protected function createTicketsToFetch()
    {
        $ticketDetails["fd_instance"] = "rzpind";

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0034',
            'ticket_id'      => '34',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0056',
            'ticket_id'      => '56',
            'merchant_id'    => '20000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0078',
            'ticket_id'      => '78',
            'merchant_id'    => '10000000000000',
            'type'           => 'reserve_balance_activate',
            'ticket_details' => $ticketDetails,
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0013',
            'ticket_id'      => '13',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
            'created_by'     => 'agent',
            'status'         => 2,
            'created_at'     => '1700000000',
            'updated_at'     => '1700000000',
        ]);
    }

    private function setupTestDataGetAgentCreatedTickets($testcaseName)
    {
        if ($testcaseName === 'pagination')
        {
            $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET_FILTER_PAGINATED_AGENT_CREATED_TICKET);

            $pageOneTickets = [];

            $singleTicket = $expectedRequestResponse['response']['results'][0];

            for ($i=0;$i<30;$i++)
            {
                $singleTicket['id'] = stringify($i+1000);

                $this->expectFreshdeskRequestAndRespondWith('tickets/'.$singleTicket['id'], 'PUT',
                                                            [
                                                                'custom_fields'=> [
                                                                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000'
                                                                ]
                                                            ],
                                                            [
                                                                'id'            => $singleTicket['id'],
                                                            ],2);

                array_push($pageOneTickets, $singleTicket);
            }

            $expectedRequestResponse['response']['results'] = $pageOneTickets;

            $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22created_at%3A%272020-03-07%27+AND+custom_string%3A%27agent%27%22&page=1', 'get',
                                                        $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);

            $pageTwoTickets = [];

            for ($i=0;$i<2;$i++)
            {
                $singleTicket['id'] = stringify($i+2000);

                $this->expectFreshdeskRequestAndRespondWith('tickets/'.$singleTicket['id'], 'PUT',
                                                            [
                                                                'custom_fields'=> [
                                                                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000'
                                                                ]
                                                            ],
                                                            [
                                                                'id'            => $singleTicket['id'],
                                                            ],2);

                array_push($pageTwoTickets, $singleTicket);
            }

            $expectedRequestResponse['response']['results'] = $pageTwoTickets;

            $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22created_at%3A%272020-03-07%27+AND+custom_string%3A%27agent%27%22&page=2', 'get',
                                                        $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);

        }
        else if ($testcaseName === 'general')
        {
            $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET_FILTER_AGENT_CREATED_TICKET);

            $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22created_at%3A%272020-03-07%27+AND+custom_string%3A%27agent%27%22&page=1', 'get',
                                                        $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);

            $this->expectFreshdeskRequestAndRespondWith('tickets/13', 'PUT',
                                                        [
                                                            'custom_fields'=> [
                                                                'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000'
                                                            ]
                                                        ],
                                                        [
                                                            'id'            => '12',
                                                        ],2);

            $this->expectFreshdeskRequestAndRespondWith('tickets/34', 'PUT',
                                                        [
                                                            'custom_fields'=> [
                                                                'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000'
                                                            ]
                                                        ],
                                                        [
                                                            'id'            => '12',
                                                        ],2 );
        }
        else if ($testcaseName = 'merchant_doesnt_exist')
        {
            $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET_FILTER_AGENT_CREATED_TICKET_WRONG_MERCHANT);

            $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22created_at%3A%272020-03-07%27+AND+custom_string%3A%27agent%27%22&page=1', 'get',
                                                        $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);
        }

    }

    protected function mockDruid(array $array)
    {
        $druidService = $this->getMockBuilder(MockDruidService::class)
                             ->setConstructorArgs([$this->app])
                             ->setMethods([ 'getDataFromDruid'])
                             ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = $array;

        $druidService->method( 'getDataFromDruid')
                     ->willReturn([null, [$dataFromDruid]]);
    }

    protected function mockPinot(array $array)
    {
        $harvesterService = $this->getMockBuilder(HarvesterClient::class)
                                 ->setConstructorArgs([$this->app])
                                 ->setMethods([ 'getDataFromPinot'])
                                 ->getMock();

        $this->app->instance('eventManager', $harvesterService);

        $dataFromHarvester = $array;

        $harvesterService->method( 'getDataFromPinot')
                         ->willReturn([$dataFromHarvester]);
    }
}
