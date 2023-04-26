<?php

namespace RZP\Tests\Functional\Helpers\Salesforce;

use Mockery;
use RZP\Models;

trait SalesforceTrait
{
    protected $salesforceMock;

    protected function setUpSalesforceMock(): void
    {
        $this->salesforceMock = Mockery::mock('RZP\Services\SalesForceClient', $this->app)->makePartial();

        $this->salesforceMock->shouldAllowMockingProtectedMethods();

        $this->app['salesforce'] = $this->salesforceMock;
    }

    protected function mockSalesforceRequest($expectedMerchantId, $expectedResponse, $method = 'getSalesPOCForMerchantID'): void
    {
        $this->salesforceMock->shouldReceive($method)
                             ->times(1)
                             ->with(Mockery::on(function($actualMerchantId) use ($expectedMerchantId) {
                                 return $actualMerchantId === $expectedMerchantId;
                             }))
                             ->andReturnUsing(function() use ($expectedResponse) {
                                 return $expectedResponse;
                             });

    }
}
