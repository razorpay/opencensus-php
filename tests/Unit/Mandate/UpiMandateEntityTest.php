<?php

namespace RZP\Tests\Unit\Mandate;

use RZP\Models\UpiMandate;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Unit\MocksAppServices;

class UpiMandateEntityTest extends TestCase
{
    use MocksAppServices;

    /**
     * @var UpiMandate\Entity
     */
    protected $mandate;


    public function testEntitiesLiveConnections()
    {
        $this->app['basicauth']->setModeAndDbConnection('live');

        $this->createUpiMandate();

        $this->assertSame('live', $this->mandate->getConnectionName());
    }

    public function testEntitiesTestConnections()
    {
        $this->app['basicauth']->setModeAndDbConnection('test');

        $this->createUpiMandate();

        $this->assertSame('test', $this->mandate->getConnectionName());
    }

    public function testEntitiesLiveReadConnections()
    {
        $this->app['basicauth']->setModeAndDbConnection('live');

        UpiMandate\Entity::first();

        // This just confirms that no exception is thrown while fetching
        // If we do any write Laravel will stick to write connection
        $this->assertTrue(true);
    }

    public function testEntitiesTestReadConnections()
    {
        $this->app['basicauth']->setModeAndDbConnection('test');

        UpiMandate\Entity::first();

        // This just confirms that no exception is thrown while fetching
        // If we do any write Laravel will stick to write connection
        $this->assertTrue(true);
    }

    public function testGatewayData()
    {
        $this->createUpiMandate();

        $this->assertNull($this->mandate->getGatewayData());

        $this->createUpiMandate([
            'gateway_data' => [
                'a' => 1,
                'b' => 2,
            ],
        ]);

        $this->assertSame([
            'a' => 1,
            'b' => 2,
        ], $this->mandate->getGatewayData());
    }

    public function testToArray()
    {
        $this->createUpiMandate([
            'gateway_data' => [
                'a' => 1,
                'b' => 2,
            ],
            'late_confirmed'    => true,
            'used_count'        => 2,
            'confirmed_at'      => 1595866793,
        ]);

        $this->assertArraySubset([
            'status'         => 'initiated',
            'max_amount'     => 2000,
            'frequency'      => 'monthly',
            'merchant_id'    => '100000Razorpay',
            'recurring_type' => 'before',
            'gateway_data' => [
                'a' => 1,
                'b' => 2,
            ],
            'late_confirmed'    => true,
            'used_count'        => 2,
            'confirmed_at'      => 1595866793,
        ], $this->mandate->toArray());
    }

    /************************ Helpers ****************************/

    protected function createUpiMandate(array $values = [])
    {
        $this->mandate = new UpiMandate\Entity();

        $this->mandate->forceFill(array_merge([
            'status'         => 'initiated',
            'max_amount'     => 2000,
            'frequency'      => 'monthly',
            'merchant_id'    => '100000Razorpay',
            'recurring_type' => 'before'
        ], $values));

        $this->mandate->save();
    }
}
