<?php

namespace Functional\FreshdeskTicket;

use Mockery;
use RZP\Services\RazorXClient;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\User\Entity as UserEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
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

    const RZP_CREATE_TICKET = 'rzp_create_ticket';
    
    const RZP_CREATE_TICKET_CHECKING_CC_EMAILS = 'rzp_create_ticket_checking_cc_emails';
    const RZP_CREATE_TICKET_SALESFORCE         = 'rzp_create_ticket_salesforce';
    const RZP_CREATE_TICKET_INTERNAL_AUTH      = 'rzp_create_ticket_internal_auth';
    const RZP_FETCH_TICKET_FILTER              = 'rzp_fetch_ticket_filter';
    const RZP_FETCH_TICKET                     = 'rzp_fetch_ticket';
    const RZP_CREATE_TICKET_HTML_TAGS          = 'rzp_create_ticket_html_tags';

    const RZP_GET_TICKET_BY_ID                 = 'rzp_get_ticket_by_id';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FreshdeskTicketV2TestData.php';

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

    protected function mockStork()
    {
        $this->storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $this->storkMock);
    }

    protected function expectStorkWhatsappRequest($template, $text, $destination = '9876543210', $ownerId = '10000000000000'): void
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

                    $actualText = $whatsappChannel['text'];

                    $actualDestination = $whatsappChannel['destination'];

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
                $response = new \Requests_Response;

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

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+custom_string%3A%27Merchant%27+AND+custom_string%3A%27Activation%27%22&page=1', 'get',
            $expectedRequestResponse['request'], $expectedRequestResponse['response'], 4);

        $this->createTicketsToFetch();

        $this->startTest();
    }

    public function testFetchTicketsForMerchantWithFilter()
    {
        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET_FILTER);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+custom_string%3A%27Merchant%27+AND+custom_string%3A%27Activation%27+AND+custom_string%3A%27merchant%27%22&page=1', 'get',
                                                    $expectedRequestResponse['request'], $expectedRequestResponse['response'], 4);

        $this->createTicketsToFetch();

        $this->startTest();
    }

    public function testFetchTicketsForMerchantFailedForSomeInstance()
    {
        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+custom_string%3A%27Merchant%27+AND+custom_string%3A%27Activation%27%22&page=1', 'get',
                                                    $expectedRequestResponse['request'], $expectedRequestResponse['response'], 2);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+custom_string%3A%27Merchant%27+AND+custom_string%3A%27Activation%27%22&page=1', 'get',
                                                    $expectedRequestResponse['request'], null, 2);

        $this->createTicketsToFetch();

        $this->startTest();
    }

    public function testFetchTicketsForMerchantWithStatusOnly()
    {
        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_FETCH_TICKET);

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3Amerchant_dashboard_10000000000000+AND+%28status%3A2%29%22&page=1', 'get',
            $expectedRequestResponse['request'], $expectedRequestResponse['response'], 4);

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
                $expectedRequestResponse['request'], $expectedRequestResponse['response'], 4);

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

    public function testCreateTicketRzp()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzp',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

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

    public function testCreateTicketRzpWithHtmlTagsAndNoMerchantName()
    {
        $expectedRequestResponse    =   $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET_HTML_TAGS);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzp',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->fixtures->merchant->edit('10000000000000', ['name' => null]);

        $this->startTest();
    }

    public function testCreateTicketRzpWithDCMigrationExperimentOn()
    {
        $this->mockRazorxTreatment('on');

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
            
            $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzp',
                $expectedRequestResponse['request'], $expectedRequestResponse['response']);

            $this->testData[__FUNCTION__]['response']['cc_emails'] = $testcase['cc_emails'];

            $this->startTest();
        }
    }

    public function testCreateTicketRzpSalesForce()
    {
        $this->ba->salesForceAuth();

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET_SALESFORCE);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzp',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $this->assertNotEquals('razorpayid0012', $ticket['id']);
    }

    public function testCreateTicketForInternalAuth()
    {
        $beforeCount = $this->getDbEntities('merchant_freshdesk_tickets');

        $this->ba->careAppAuth();

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET_INTERNAL_AUTH);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzp',
                                                               $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();

        $ticket = $this->getLastEntity('merchant_freshdesk_tickets', true);

        $afterCount = $this->getDbEntities('merchant_freshdesk_tickets');
        // makes sure no entry is created in db
        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testCreateTicketRzpSol()
    {
        $testCases = [
            [
                'fd_instance' => 'rzpsol',
                'razorx'      => 'control',
                'group_id'      => 14000000007644,
            ],
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
                                                                       'cc_emails'     => ['a@b.com', 'merchantuser01@razorpay.com'],
                                                                       'custom_fields' => [
                                                                           'cf_requester_category'    => 'Merchant',
                                                                           'cf_requestor_subcategory' => 'Technical support',
                                                                           'cf_requester_item'        => 'Success rate',
                                                                           'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                                                                           'cf_merchant_id'           => '10000000000000',
                                                                       ],
                                                                       'email'         => 'test@razorpay.com',
                                                                       'phone'         => '9876543210',
                                                                       'priority'      => 1,
                                                                       'group_id'      => $testCase['group_id'],
                                                                   ],
                                                                   [
                                                                       'id'            => '99',
                                                                       'description'   => 'ticket description',
                                                                       'fr_due_by'     => $frDueByFreshdeskFormat,
                                                                       'custom_fields' => [
                                                                           'cf_requester_category'    => 'Merchant',
                                                                           'cf_requestor_subcategory' => 'Technical support',
                                                                           'cf_requester_item'        => 'Success rate',
                                                                           'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                                                                       ],
                                                                       'priority'      => 1,
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

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST','rzpcap',
            [
                'description' => 'ticket description',
                'subject' => 'ticket subject',
                'cc_emails' => ['a@b.com', 'merchantuser01@razorpay.com'],
                'custom_fields' => [
                    'cf_requester_category'       =>  'Merchant',
                    'cf_requestor_subcategory'    =>  'Cash Advance',
                    'cf_merchant_id_dashboard'    =>  'merchant_dashboard_10000000000000',
                    'cf_merchant_id'              => '10000000000000',
                ],
                'email' =>  'test@razorpay.com',
                'phone' => '9876543210',
                'priority' =>  1,
                'group_id' => 14000000007642,
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

    public function testCreateTicketRzpCapViaX()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST','rzpcap',
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
                'phone'=>'9876543210',
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
                'phone'=>'9876543210',
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

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST','rzpx',
            [
                'description' => 'ticket description',
                'subject' => 'ticket subject',
                'cc_emails' => ['a@b.com','merchantuser01@razorpay.com'],
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

        $this->assertEquals('rzpx', $fdInstance);

    }

    public function testCreateTicketForAUserWithoutNameRzpX()
    {
        $frDueBy = time() + self::DAY * 2;

        $frDueByFreshdeskFormat = $this->getTimeInFreshdeskFormat($frDueBy);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST','rzpx',
            [
                'description' => 'ticket description',
                'subject' => 'ticket subject',
                'cc_emails' => ['a@b.com','merchantuser01@razorpay.com'],
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'email' =>  'user@razorpay.com',
                'priority' =>  1,
                'name' => '',
                'phone' => '1234567890',
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
                'description' => 'ticket description',
                'subject' => 'ticket subject',
                'cc_emails' => ['a@b.com', 'merchantuser01@razorpay.com'],
                'custom_fields' => [
                    'cf_requester_category'    => 'Merchant',
                    'cf_requestor_subcategory' => 'Activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                ],
                'email' =>  'user@razorpay.com',
                'phone' => '1234567890',
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

        $this->assertEquals('rzpx', $fdInstance);

    }

    public function testCreateTicketFreshdeskError()
    {
        $this->expectFreshdeskRequestAndRespondWith('tickets', 'POST',
            [
                'description' => 'ticket description',
                'subject' => 'ticket subject',
                'cc_emails' => ['a@b.com', 'merchantuser01@razorpay.com'],
                'custom_fields' => [
                    'cf_requester_category'    => 'Invalid',
                    'cf_requestor_subcategory' => 'activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                    'cf_merchant_id'           => '10000000000000',
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

        $this->expectStorkWhatsappRequest('support.ticket_created',
        'Hi, 
Thank you for reaching out. This is to inform you that your ticket number 99 has been registered. Our team is working on your request and will get back to you within 3 working days. 
Team Razorpay');

        $this->testData[__FUNCTION__] = $this->testData['testCreateTicketRzp'];

        $expectedRequestResponse = $this->getExpectedRequestResponse(self::RZP_CREATE_TICKET);

        $this->checkFreshdeskCorrectInstanceCallAndRespondWith('tickets', 'POST', 'rzpind',
            $expectedRequestResponse['request'], $expectedRequestResponse['response']);

        $this->startTest();
    }

    public function testCreateTicketInvalidAttachmentExtension()
    {

        $this->addAttachmentToRequest(__FUNCTION__, 'a.exe');

        $this->mockRazorxTreatment('on');

        $this->startTest();
    }

    public function testReceiveFreshdeskWebhookToNotifyMerchant()
    {
        $this->ba->freshdeskWebhookAuth();

        $this->mockRazorxTreatment('on');

        $testcases = [
            [
                'event'         => 'TICKET_DELAY_UPDATE_72HRS',
                'expected_text' => 'Hi, 
We are sorry about the delay regarding your ticket 12. We will revert back to you with a resolution for the same in the next 72 hrs. Please bear with us.
Team Razorpay',
            ],
            [
                'event'         => 'TICKET_DELAY_UPDATE_24HRS',
                'expected_text' => 'Hi, 
We are sorry about the delay regarding your ticket 12. We will revert back to you with a resolution for the same in the next 24 hrs. Please bear with us. 
Team Razorpay',
            ],
            [
                'event'         => 'TICKET_DETAILS_PENDING',
                'expected_text' => 'Hi, 
We require a few details from your end on the ticket 12. Request you to check your email and respond with the details for us to check and resolve the concern raised.
Team Razorpay',
            ],
            [
                'event'         => 'TICKET_RESOLVED',
                'expected_text' => 'Hi, 
Your issue regarding the ticket 12 has been resolved and a response has been sent over to your email. If you are not satisfied with the resolution provided feel free to reopen the ticket by replying to the same email. 
Team Razorpay',
            ],
            [
                'event'         => 'TICKET_REOPENED',
                'expected_text' => 'Hi, 
We believe that your issue regarding the ticket 12 is still not resolved. Your ticket has been reopened and our team will take it up on priority and get back to you within 24 hrs.
Team Razorpay',
            ],
        ];

        foreach ($testcases as $testcase)
        {
            $expectedTemplate = 'support.'. strtolower($testcase['event']);

            $this->mockStork();

            $this->expectStorkWhatsappRequest($expectedTemplate, $testcase['expected_text']);

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
                'description' => 'ticket description',
                'custom_fields' => [
                    'cf_requester_category' => 'Merchant',
                    'cf_requestor_subcategory' => 'Activation',
                    'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                    'cf_merchant_id'           => '10000000000000',
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
        $testCases = [
            [
               'ticket_id'     => '1234',
               'fd_instance'   => 'rzp',
            ],
            [
                'ticket_id'     => '12345',
                'fd_instance'   => 'rzpind',
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->ba->freshdeskWebhookAuth();

            $this->testData[__FUNCTION__]['request']['content']['ticket_id'] = $testCase['ticket_id'];

            $this->testData[__FUNCTION__]['request']['content']['ticket_details']['fd_instance'] = $testCase['fd_instance'];

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
            'rzp'       => $expectedUrl1,
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
                $response = new \Requests_Response;

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
                        'cf_requester_category'    => 'Merchant',
                        'cf_requestor_subcategory' => 'Activation',
                        'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'           => '10000000000000',
                    ],
                    'email' =>  'test@razorpay.com',
                    'phone' => '9876543210',
                    'priority' =>  1,
                ],
                'response'  =>
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
                    'priority' =>  1,
                ]
            ];
        }

        if ($key === self::RZP_CREATE_TICKET_HTML_TAGS)
        {
            return [
                'request'   =>  [
                    'description' => '<br>Ticket<b>Description</b><br>HTML',
                    'subject' => 'ticket subject',
                    'cc_emails' => ['a@b.com','merchantuser01@razorpay.com'],
                    'custom_fields' => [
                        'cf_requester_category'    => 'Merchant',
                        'cf_requestor_subcategory' => 'Activation',
                        'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'           => '10000000000000',
                    ],
                    'email' =>  'test@razorpay.com',
                    'phone' => '9876543210',
                    'priority' =>  1,
                ],
                'response'  =>
                    [
                        'id'            => '99',
                        'description'   => '<br>Ticket<b>Description</b><br>HTML',
                        'fr_due_by'     => $frDueByFreshdeskFormat,
                        'custom_fields' => [
                            'cf_requester_category'    => 'Merchant',
                            'cf_requestor_subcategory' => 'Activation',
                            'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                            'cf_merchant_id'           => '10000000000000',
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
                        'custom_fields' => [
                            'cf_requester_category'    => 'Merchant',
                            'cf_requestor_subcategory' => 'Activation',
                            'cf_merchant_id'           => '10000000000000',
                            'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                        ],
                        'priority' =>  1,
                    ]
            ];
        }

        if ($key === self::RZP_CREATE_TICKET_SALESFORCE)
        {
            return [
                'request'   =>  [
                    'description' => 'ticket description',
                    'subject' => 'ticket subject',
                    'cc_emails' => ['a@b.com'],
                    'custom_fields' => [
                        'cf_requester_category'    => 'Merchant',
                        'cf_requestor_subcategory' => 'Activation',
                        'cf_merchant_id_dashboard' => 'merchant_dashboard_10000000000000',
                        'cf_merchant_id'           => '10000000000000',
                    ],
                    'email' =>  'test@razorpay.com',
                    'phone' => '9876543210',
                    'priority' =>  1,
                ],
                'response'  =>
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
                        'cf_requester_category'    => 'Merchant',
                        'cf_requestor_subcategory' => 'Call Requested',
                        'cf_merchant_id'           => '10000000000000',
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
                ],

            ];
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
                            ],
                            'fr_due_by' => '2020-12-08T16:04:20Z',

                        ],
                        [
                            'id'        => 34,
                            'body'      => 'some random body 34',
                            'custom_fields' =>  [
                                "cf_requestor_subcategory"  => "Activation",
                                "cf_requester_category"     => "Merchant",
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
    }
}
