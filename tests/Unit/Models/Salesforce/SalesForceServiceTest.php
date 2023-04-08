<?php

namespace RZP\Tests\Unit\Models\SalesForceServiceTest;

use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\XChannelDefinition;
use RZP\Services\SalesForceClient;
use RZP\Models\SalesForce\SalesForceService;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Helpers\MocksDiagTrait;
use RZP\Models\SalesForce\SalesForceEventRequestDTO;
use RZP\Models\SalesForce\SalesForceEventRequestType;

class SalesForceServiceTest extends OAuthTestCase {

    use MocksDiagTrait;

    const RESPONSE_ACCESS_TOKEN_SUCCESS = 'RESPONSE_ACCESS_TOKEN_SUCCESS';
    const RESPONSE_OPPORTUNITY_UPSERT_INVALID_AUTH_FAILURE = 'RESPONSE_OPPORTUNITY_UPSERT_INVALID_AUTH_FAILURE';
    const RESPONSE_OPPORTUNITY_UPSERT_SUCCESS = 'RESPONSE_OPPORTUNITY_UPSERT_SUCCESS';

    private $salesForceService;
    private $salesForceClient;
    private $xChannelDefinitionServiceMock;

    protected function setUp(): void {
        parent::setUp();
        $this->salesForceClient = $this->mockSalesForceServiceClient();
        $this->xChannelDefinitionServiceMock = $this->createMock(XChannelDefinition\Service::class);
        $this->salesForceService = new SalesForceService($this->salesForceClient, $this->xChannelDefinitionServiceMock);
    }

    public function testEventPayloadIsGeneratedForAMerchantInterestedInCA() {
        //Given
        $merchantData = $this->getMerchantData();
        $merchant = $this->createConfiguredMock(Entity::class, $merchantData);

        $salesForceRequestDTO = new SalesForceEventRequestDTO();
        $salesForceRequestDTO->setEventType(new SalesForceEventRequestType('CURRENT_ACCOUNT_INTEREST'));
        $salesForceRequestDTO->setEventProperties($this->getEventProperties());

        $actualData = null;
        $this->salesForceClient->expects($this->any())
                               ->method('sendEventToSalesForce')
                               ->will($this->returnCallback(function (array $payload) use (&$actualData) {
                                   $actualData = $payload;
                                   return;
                               }));

        $this->xChannelDefinitionServiceMock
            ->expects($this->any())
            ->method('getCurrentChannelDetails')
            ->will(
                $this->returnValue(
                    [
                        XChannelDefinition\Constants::CHANNEL    => XChannelDefinition\Channels::PG,
                        XChannelDefinition\Constants::SUBCHANNEL => XChannelDefinition\Channels::PG_NITRO,
                    ]
                )
            );

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id'   => $merchant->getId(),
                'product'       => 'banking',
                'group'         => 'x_merchant_preferences',
                'type'          => 'x_signup_platform',
                'value'         => 'x_mobile',
                'updated_at'    => time(),
                'created_at'    => time()
            ]);

        $expectedPayload = [
            'merchant_id'                   => '1DefeDEQE',
            'name'                          => 'Aditya',
            'email'                         => 'aditya@example.com',
            'activated'                     => 1,
            'signup_date'                   => '2020-08-19',
            'interested_in_current_account' => 1,
            'pin_code'                      => '560079',
            'average_monthly_balance'       => '5000',
            'current_ca'                    => 'HDFC',
            'use_case'                      => 'Salary',
            'source_detail'                 => 'x_mobile',
            'X_Channel'                     => 'PG',
            'X_Subchannel'                  => 'Nitro',
        ];

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldReceive('trackOnboardingEvent')
                 ->withArgs(function($eventData, $merchant, $ex, $actualData) use ($expectedPayload) {
                        unset($actualData['event_submission_date']); //Because it changes day by day
                        $this->assertEquals($expectedPayload, $actualData);
                        $this->assertEquals(EventCode::X_CA_ONBOARDING_OPPORTUNITY_UPSERT, $eventData);
                        return true;
                 })
                 ->andReturnNull();

        //When
        $this->salesForceService->raiseEvent($merchant, $salesForceRequestDTO);

        unset($actualData['event_submission_date']); //Because it changes day by day

        $this->assertEquals($expectedPayload, $actualData);
    }

    public function testEventPayloadIsGeneratedForWebsiteEvent() {
        // Given
        $merchantData = $this->getMerchantData();
        $merchant = $this->createConfiguredMock(Entity::class, $merchantData);

        $salesForceRequestDTO = new SalesForceEventRequestDTO();

        $salesForceRequestDTO->setEventType(new SalesForceEventRequestType('RX_WEBSITE_SF_EVENTS'));

        $salesForceRequestDTO->setEventProperties([
                                                      'merchant_id'             => 'midtestca123',
                                                      'name'                    => 'Aditya',
                                                      'email'                   => 'aditya@example.com',
                                                      'contact_mobile'          => '9698988110',
                                                      'business_registered_pin' => '641035',
                                                      'business_type'           => 'Partnership',
                                                      'business_subcategory'    => 'Education',
                                                      'Ref_Website'             => 'razorpay.com/x',
                                                      'Traffic_Campaign'        => 'XWebsite Lead form',
                                                      'Traffic_Medium'          => 'Website',
                                                      'Traffic_Source'          => 'Paid',
                                                      'Product'                 => 'Current_Account'
                                                  ]);

        $actualData = null;

        // NOTE - Checking for method indirectly also checks for URL
        $this->salesForceClient->expects($this->once())
                               ->method('sendLeadUpsertEventsToSalesforce')
                               ->will($this->returnCallback(function (array $payload) use (&$actualData) {
                                   $actualData = $payload;
                                   return;
                               }));

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldNotReceive('trackOnboardingEvent');

        //When
        $this->salesForceService->raiseEvent($merchant, $salesForceRequestDTO);

        //Then
        $expectedPayload = [
            'merchant_id'             => 'midtestca123',
            'name'                    => 'Aditya',
            'email'                   => 'aditya@example.com',
            'contact_mobile'          => '9698988110',
            'business_registered_pin' => '641035',
            'business_type'           => 'Partnership',
            'business_subcategory'    => 'Education',
            'Ref_Website'             => 'razorpay.com/x',
            'Traffic_Campaign'        => 'XWebsite Lead form',
            'Traffic_Medium'          => 'Website',
            'Traffic_Source'          => 'Paid',
            'Product'                 => 'Current_Account',
        ];

        $this->assertEquals($expectedPayload, $actualData);
    }

    public function testSalesForcePayloadIsParsedAndMerchantDetailIsConstructed() {
        //Given
        $opportunities = ['Current Account', 'Some other thing'];

        $salesForceResponsePayload = [
            "totalSize" => 2,
            "done"      => true,
            "records"   => [
                [
                    "attributes"       => [
                        "type" => "Opportunity",
                        "url"  => "/services/data/v48.0/sobjects/Opportunity/0066F000016wT65QAE"
                    ],
                    "Account"          => [
                        "attributes"     => [
                            "type" => "Account",
                            "url"  => "/services/data/v48.0/sobjects/Account/0016F00002Kf9lcQAB"
                        ],
                        "Merchant_ID__c" => "random-merchant-id"
                    ],
                    "Type"             => "Current_Account",
                    "StageName"        => "Open",
                    "Loss_Reason__c"   => null,
                    "LastModifiedDate" => "2020-09-18T10:16:34.000+0000",
                    "Owner"            => [
                        "attributes" => [
                            "type" => "User",
                            "url"  => "/services/data/v48.0/sobjects/User/0056F00000BhL4mQAF"
                        ],
                        "Name"       => "Aditya"
                    ],
                    "Owner_Role__c"    => "Engineering"
                ]
                ,
                [
                    "attributes"       => [
                        "type" => "Opportunity",
                        "url"  => "/services/data/v48.0/sobjects/Opportunity/0066F000016wT65QAE"
                    ],
                    "Account"          => [
                        "attributes"     => [
                            "type" => "Account",
                            "url"  => "/services/data/v48.0/sobjects/Account/0016F00002Kf9lcQAB"
                        ],
                        "Merchant_ID__c" => "random-merchant-id"
                    ],
                    "Type"             => "Some other thing",
                    "StageName"        => "Closed",
                    "Loss_Reason__c"   => "Some random reason",
                    "LastModifiedDate" => "2020-09-20T10:16:34.000+0000",
                    "Owner"            => [
                        "attributes" => [
                            "type" => "User",
                            "url"  => "/services/data/v48.0/sobjects/User/0056F00000BhL4mQAF"
                        ],
                        "Name"       => "Akshay"
                    ],
                    "Owner_Role__c"    => null
                ]
            ]];

        $this->salesForceClient->expects($this->once())
                               ->method('getMerchantDetailsOnOpportunity')
                               ->willReturn($salesForceResponsePayload);

        //When

        $merchant = $this->fixtures->create('merchant', ['id' => '20000000000000']);

        app('basicauth')->setMerchant($merchant);

        $merchantDetails = $this->salesForceService->getMerchantDetailsOnOpportunity($merchant->getId(), $opportunities);

        //Then
        $expectedMerchantDetails = [[
                                        'merchantId'                  => 'random-merchant-id',
                                        'opportunityName'             => 'Current_Account',
                                        'opportunityStage'            => 'Open',
                                        'opportunityLossReason'       => null,
                                        'opportunityOwnerName'        => 'Aditya',
                                        'opportunityOwnerRole'        => 'Engineering',
                                        'opportunityLastModifiedTime' => 1600424194
                                    ], [
                                        'merchantId'                  => 'random-merchant-id',
                                        'opportunityName'             => 'Some other thing',
                                        'opportunityStage'            => 'Closed',
                                        'opportunityLossReason'       => 'Some random reason',
                                        'opportunityOwnerName'        => 'Akshay',
                                        'opportunityOwnerRole'        => null,
                                        'opportunityLastModifiedTime' => 1600596994
                                    ]];

        //Json Encode here to test serves 2 purposes
        //1. I don't have to construct the objects to test
        //2. It also exercises the custom serializer written for SalesforceMerchantOpportunityDetail
        $this->assertEquals(json_encode($expectedMerchantDetails), json_encode($merchantDetails));
    }

    public function testSalesforceRequestJobSuccessiveAuthErrorScenario() {
        //Given
        $merchantData = $this->getMerchantData();
        $merchant = $this->createConfiguredMock(Entity::class, $merchantData);

        $this->salesForceClient->expects($this->exactly(4))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                $this->getSalesForceResponseCase(self::RESPONSE_ACCESS_TOKEN_SUCCESS),
                $this->getSalesForceResponseCase(self::RESPONSE_OPPORTUNITY_UPSERT_INVALID_AUTH_FAILURE),
                $this->getSalesForceResponseCase(self::RESPONSE_ACCESS_TOKEN_SUCCESS),
                $this->getSalesForceResponseCase(self::RESPONSE_OPPORTUNITY_UPSERT_INVALID_AUTH_FAILURE));

        $salesForceRequestDTO = new SalesForceEventRequestDTO();
        $salesForceRequestDTO->setEventType(new SalesForceEventRequestType('CURRENT_ACCOUNT_INTEREST'));
        $salesForceRequestDTO->setEventProperties($this->getEventProperties());

        $this->validateOnboardingEvent();

        $traceMock = $this->createTraceMock();
        $traceMock->shouldReceive('traceException')
            ->once()
            ->andReturnUsing(function(\Throwable $exception, $level = null, $code = null, array $extraData = []) {
                    $this->assertEquals(ErrorCode::SERVER_ERROR_SALESFORCE_SERVICE_ERROR, $exception->getCode());
                    $this->assertEquals('Failed to push event to Salesforce', $exception->getMessage());
                }
            );

        //When
        $this->salesForceService->raiseEvent($merchant, $salesForceRequestDTO);
    }

    public function testSalesforceRequestJobOnceAuthErrorScenario() {
        //Given
        $merchantData = $this->getMerchantData();
        $merchant = $this->createConfiguredMock(Entity::class, $merchantData);

        $this->salesForceClient->expects($this->exactly(4))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                $this->getSalesForceResponseCase(self::RESPONSE_ACCESS_TOKEN_SUCCESS),
                $this->getSalesForceResponseCase(self::RESPONSE_OPPORTUNITY_UPSERT_INVALID_AUTH_FAILURE),
                $this->getSalesForceResponseCase(self::RESPONSE_ACCESS_TOKEN_SUCCESS),
                $this->getSalesForceResponseCase(self::RESPONSE_OPPORTUNITY_UPSERT_SUCCESS));

        $salesForceRequestDTO = new SalesForceEventRequestDTO();
        $salesForceRequestDTO->setEventType(new SalesForceEventRequestType('CURRENT_ACCOUNT_INTEREST'));
        $salesForceRequestDTO->setEventProperties($this->getEventProperties());

        $this->validateOnboardingEvent();

        $traceMock = $this->createTraceMock();
        $traceMock->shouldNotReceive('traceException');

        //When
        $this->salesForceService->raiseEvent($merchant, $salesForceRequestDTO);
    }

    public function testSalesforceRequestJobNoAuthErrorScenario() {
        //Given
        $merchantData = $this->getMerchantData();
        $merchant = $this->createConfiguredMock(Entity::class, $merchantData);

        $this->salesForceClient->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                $this->getSalesForceResponseCase(self::RESPONSE_ACCESS_TOKEN_SUCCESS),
                $this->getSalesForceResponseCase(self::RESPONSE_OPPORTUNITY_UPSERT_SUCCESS));

        $salesForceRequestDTO = new SalesForceEventRequestDTO();
        $salesForceRequestDTO->setEventType(new SalesForceEventRequestType('CURRENT_ACCOUNT_INTEREST'));
        $salesForceRequestDTO->setEventProperties($this->getEventProperties());

        $this->validateOnboardingEvent();

        $traceMock = $this->createTraceMock();
        $traceMock->shouldNotReceive('traceException');

        //When
        $this->salesForceService->raiseEvent($merchant, $salesForceRequestDTO);
    }

    public function testSalesforceRequestWithBankingWidgetCampaignId() {
        //Given
        $merchantData = $this->getMerchantData();
        $merchant = $this->createConfiguredMock(Entity::class, $merchantData);

        $salesForceRequestDTO = new SalesForceEventRequestDTO();
        $salesForceRequestDTO->setEventType(new SalesForceEventRequestType('CURRENT_ACCOUNT_INTEREST'));
        $salesForceRequestDTO->setEventProperties(
            array_merge($this->getEventProperties() , [
                'Campaign_ID' => 'abced_PG_X_Banking_Widget_abcde'
            ]));

        $expectedPayload = [
            'merchant_id' => '1DefeDEQE',
            'name' => 'Aditya',
            'email' => 'aditya@example.com',
            'activated' => 1,
            'signup_date' => '2020-08-19',
            'event_submission_date' => date('Y-m-d'),
            'interested_in_current_account' => 1,
            'pin_code' => '560079',
            'average_monthly_balance' => '5000',
            'current_ca' => 'HDFC',
            'use_case' => 'Salary',
            'Campaign_ID' => 'abced_PG_X_Banking_Widget_abcde',
            'source_detail' => 'x_dashboard',
            'X_Channel' => 'PG',
            'X_Subchannel' => 'Banking_Widget',
        ];

        $this->salesForceClient->expects($this->once())->method('sendEventToSalesForce')->with($expectedPayload);

        $this->salesForceService->raiseEvent($merchant, $salesForceRequestDTO);
    }

    /*
     * Test scenario: If source detail is sent as part of event_payload from client, use it directly
     * instead of looking in merchant_attributes
     */
    public function testSalesforceRequestWithSourceDetailSentFromClient() {
        //Given
        $merchantData = $this->getMerchantData();
        $merchant = $this->createConfiguredMock(Entity::class, $merchantData);

        $this->fixtures->create('merchant_attribute', // create merchant_attribute entry to validate this is not picked for request
            [
                'merchant_id'   => $merchant->getId(),
                'product'       => 'banking',
                'group'         => 'x_merchant_preferences',
                'type'          => 'x_signup_platform',
                'value'         => 'x_mobile',
                'updated_at'    => time(),
                'created_at'    => time()
            ]);

        $salesForceRequestDTO = new SalesForceEventRequestDTO();
        $salesForceRequestDTO->setEventType(new SalesForceEventRequestType('CURRENT_ACCOUNT_INTEREST'));
        $salesForceRequestDTO->setEventProperties(
            array_merge($this->getEventProperties() , [
                'source_detail' => 'x_lms'
            ]));

        $expectedPayload = [
            'merchant_id' => '1DefeDEQE',
            'name' => 'Aditya',
            'email' => 'aditya@example.com',
            'activated' => 1,
            'signup_date' => '2020-08-19',
            'event_submission_date' => date('Y-m-d'),
            'interested_in_current_account' => 1,
            'pin_code' => '560079',
            'average_monthly_balance' => '5000',
            'current_ca' => 'HDFC',
            'use_case' => 'Salary',
            'source_detail' => 'x_lms',
        ];

        $this->salesForceClient->expects($this->once())->method('sendEventToSalesForce')->with($expectedPayload);

        $this->salesForceService->raiseEvent($merchant, $salesForceRequestDTO);
    }

    private function createTraceMock() :\Razorpay\Trace\Logger
    {
        $webProcessor = $this->app['trace']->processor('web');
        $traceMock = \Mockery::mock('\Razorpay\Trace\Logger')->makePartial();
        $traceMock->shouldReceive('info', 'count');
        $traceMock->shouldReceive('processor')->andReturn($webProcessor);
        $this->app->instance('trace', $traceMock);

        return $traceMock;
    }

    private function mockSalesForceServiceClient(): SalesForceClient
    {
        $salesForceClient = $this->getMockBuilder(SalesForceClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['sendRequest', 'sendEventToSalesForce', 'sendLeadUpsertEventsToSalesforce', 'getMerchantDetailsOnOpportunity'])
            ->getMock();

        $salesForceClient->method('sendEventToSalesForce')
            ->willReturnCallback(function(array $eventPayload){
                (new SalesForceClient($this->app))->sendEventToSalesForce($eventPayload);
            });

        $this->app['salesforce'] = $salesForceClient;
        $this->app['rzp.mode'] = 'test';

        return $salesForceClient;
    }

    private function getSalesForceResponseCase(string $case) :\Requests_Response
    {
        $response = new \Requests_Response;

        switch ($case)
        {
            case self::RESPONSE_ACCESS_TOKEN_SUCCESS:
                $response->status_code = 200;
                $response->body = '{"access_token": "123"}';
                break;
            case self::RESPONSE_OPPORTUNITY_UPSERT_INVALID_AUTH_FAILURE:
                $response->status_code = 400;
                $response->body = '{"error": "invalid_grant"}';
                break;
            case self::RESPONSE_OPPORTUNITY_UPSERT_SUCCESS:
                $response->status_code = 200;
                $response->body = '{"Status":"SUCCESS"}';
                break;
        }

        return $response;
    }

    private function getMerchantData(): array
    {
        return [
            'getId'             => '1DefeDEQE',
            'getName'           => 'Aditya',
            'getEmail'          => 'aditya@example.com',
            'isActivated'       => true,
            'getCreatedAt'      => 1597842772,
            'getMerchantDetail' => $this->createConfiguredMock(\RZP\Models\Merchant\Detail\Entity::class, [
                'getBusinessName' => 'NEW BIZ',
                'getContactName'  => 'Aditya'
            ])
        ];
    }

    private function validateOnboardingEvent()
    {
        $expectedPayload = [
            'merchant_id'                   => '1DefeDEQE',
            'name'                          => 'Aditya',
            'email'                         => 'aditya@example.com',
            'activated'                     => 1,
            'signup_date'                   => '2020-08-19',
            'interested_in_current_account' => 1,
            'pin_code'                      => '560079',
            'average_monthly_balance'       => '5000',
            'current_ca'                    => 'HDFC',
            'use_case'                      => 'Salary',
            'source_detail'                 => 'x_dashboard',
        ];

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldReceive('buildRequestAndSend');
        $diagMock->shouldReceive('trackOnboardingEvent')
            ->once()
            ->withArgs(function($eventData, $merchant, $ex, $actualData) use ($expectedPayload) {
                unset($actualData['event_submission_date']); //Because it changes day by day
                $this->assertEquals($expectedPayload, $actualData);
                $this->assertEquals(EventCode::X_CA_ONBOARDING_OPPORTUNITY_UPSERT, $eventData);
                return true;
            })
            ->andReturnNull();
    }

    private function getEventProperties(): array
    {
        return [
            'interested_in_current_account' => 1,
            'pin_code'                      => '560079',
            'average_monthly_balance'       => '5000',
            'current_ca'                    => 'HDFC',
            'use_case'                      => 'Salary'
        ];
    }
}
