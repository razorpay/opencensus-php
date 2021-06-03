<?php

namespace RZP\Tests\Unit\Services;

use ReflectionClass;
use RZP\Services\Shield;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\RecurringType;
use RZP\Constants\Shield as ShieldConstants;

class ShieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('applications.shield.mock', true);
    }

    public function testCreateRule()
    {
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

    public function testPopulatePaymentDetailsForRecurring()
    {
        $class = new ReflectionClass(Shield::class);
        $populatePaymentDetailsMethod = $class->getMethod('populatePaymentDetails');
        $populatePaymentDetailsMethod->setAccessible(true);

        $token = $this->fixtures->create('customer:emandate_token', [
            'max_amount'     => 10000
        ]);

        $payment = $this->fixtures->create('payment:captured', [
            'token_id'       => $token->getId(),
            'recurring'      => true,
            'recurring_type' => RecurringType::AUTO,
        ]);

        $input = [];

        $populatePaymentDetailsMethod->invokeArgs(new Shield($this->app), [$payment, &$input]);

        $this->assertEquals(RecurringType::AUTO, $input[ShieldConstants::RECURRING_TYPE]);

        $this->assertEquals($token->getId(), $input[ShieldConstants::TOKEN_ID]);

        $this->assertEquals($token->getMaxAmount(), $input[ShieldConstants::TOKEN_MAX_AMOUNT]);
    }
}
