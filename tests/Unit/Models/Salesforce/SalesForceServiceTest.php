<?php

namespace RZP\Tests\Unit\Models\SalesForceServiceTest;

use RZP\Models\Merchant\Entity;
use RZP\Services\SalesForceClient;
use RZP\Models\SalesForce\SalesForceService;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Models\SalesForce\SalesForceEventRequestDTO;
use RZP\Models\SalesForce\SalesForceEventRequestType;

class SalesForceServiceTest extends OAuthTestCase {

    private $salesForceService;
    private $salesForceClient;

    protected function setUp(): void {
        parent::setUp();
        $this->salesForceClient = $this->createMock(SalesForceClient::class);
        $this->salesForceService = new SalesForceService($this->salesForceClient);
    }

    public function testEventPayloadIsGeneratedForAMerchantInteresteedInCA() {
        //Given
        $merchantData = [
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
        $merchant = $this->createConfiguredMock(Entity::class, $merchantData);

        $salesForceRequestDTO = new SalesForceEventRequestDTO();
        $salesForceRequestDTO->setEventType(new SalesForceEventRequestType('CURRENT_ACCOUNT_INTEREST'));
        $salesForceRequestDTO->setEventProperties([
            'interested_in_current_account' => 1,
            'pin_code'                      => '560079',
            'average_monthly_balance'       => '5000',
            'current_ca'                    => 'HDFC',
            'use_case'                      => 'Salary'
        ]);

        $actualData = null;
        $this->salesForceClient->expects($this->any())
                               ->method('sendEventToSalesForce')
                               ->will($this->returnCallback(function (array $payload) use (&$actualData) {
                                   $actualData = $payload;
                                   return;
                               }));


        //When
        $this->salesForceService->raiseEvent($merchant, $salesForceRequestDTO);


        //Then
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
        ];

        unset($actualData['event_submission_date']); //Because it changes day by day

        $this->assertEquals($expectedPayload, $actualData);
    }

    public function testEventPayloadIsGeneratedForWebsiteEvent() {
        ////Given
        $merchantData = [
            'getId'             => 'midtestca123',
            'getName'           => 'Aditya',
            'getEmail'          => 'aditya@example.com',
            'isActivated'       => true,
            'getCreatedAt'      => 1597842772,
            'getMerchantDetail' => $this->createConfiguredMock(\RZP\Models\Merchant\Detail\Entity::class, [
                'getBusinessName' => 'NEW BIZ',
                'getContactName'  => 'Aditya'
            ])
        ];

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
            'Product'                 => 'Current_Account'
        ];

        $this->assertEquals($expectedPayload, $actualData);
    }

    public function testSalesForcePayloadIsParsedAndMerchantDetailIsConstructed() {
        //Given
        $merchantId = 'random-merchant-id';
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
        $merchantDetails = $this->salesForceService->getMerchantDetailsOnOpportunity($merchantId, $opportunities);

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

}
