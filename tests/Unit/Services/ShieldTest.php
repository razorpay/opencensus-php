<?php

namespace RZP\Tests\Unit\Services;

use RZP\Exception;
use RZP\Tests\TestCase;

class ShieldTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('applications.shield.mock', true);
    }

    public function testCreateRule()
    {
        $client = $this->getClient();

        $input = [
            'expression' => 'amount > 10000'
        ];

        $result = $this->app['shield']->createRule($input);

        $this->assertSame([
            'id'         => '12345678',
            'expression' => 'amount > 10000',
            'is_active'  => true,
            'created_at' => 1518608813,
            'updated_at' => 1518608813
        ], $result);
    }
}
