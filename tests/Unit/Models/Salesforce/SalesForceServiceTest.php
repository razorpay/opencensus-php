<?php

namespace RZP\Tests\Unit\Models\SalesForceServiceTest;

use PHPUnit\Framework\TestCase as TestCase;
use RZP\Models\Merchant\Entity;
use RZP\Models\SalesForce\SalesForceEventRequestDTO;
use RZP\Models\SalesForce\SalesForceEventRequestType;
use RZP\Models\SalesForce\SalesForceService;
use RZP\Services\SalesForceClient;

class SalesForceServiceTest extends TestCase {

    private $salesForceService;
    private $salesForceClient;

    protected function setUp() {
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
            'business_name'                 => 'NEW BIZ',
            'contact_name'                  => 'Aditya',
            'interested_in_current_account' => 1,
            'pin_code'                      => '560079',
            'average_monthly_balance'       => '5000',
            'current_ca'                    => 'HDFC',
            'use_case'                      => 'Salary',
        ];

        unset($actualData['event_submission_date']); //Because it changes day by day

        $this->assertEquals($expectedPayload, $actualData);
    }

}
