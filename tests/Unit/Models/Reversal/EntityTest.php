<?php

namespace Unit\Models\Reversal;

use Mockery;
use RZP\Models\Reversal\Entity;
use RZP\Tests\TestCase;

class EntityTest extends TestCase
{
    public function testCustomerLazyRead()
    {
        $mockCustomerRepo = Mockery::mock('\RZP\Models\Customer\Repository', [$this->app]);
        $mockCustomerEntity = new \RZP\Models\Customer\Entity();
        $mockCustomerId = '100000customer';
        $mockCustomerEntity->fill([
            'id' => $mockCustomerId,
            'name' => 'RzpCustomerName',
            'email' => 'RzpCustomer@email.com',
            'contact' => '9999999999',
            'merchant_id' => '100000Razorpay'
        ]);
        $mockCustomerRepo->shouldReceive('find')->with($mockCustomerId)->andReturn($mockCustomerEntity);

        $mockRepoManager = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);
        $this->app->instance('repo', $mockRepoManager);
        $mockRepoManager->shouldReceive('driver')->with('customer')->andReturn($mockCustomerRepo);


        $reversal = new Entity();
        $reversal->setAttribute('customer_id', $mockCustomerId);
        $this->assertEquals($mockCustomerEntity, $reversal->customer);
    }
}
