<?php

namespace RZP\Tests\Unit\Models\Dispute;

use Cache;
use Carbon\Carbon;
use RZP\Models\Dispute\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\CustomAssertions;

class EntityTest extends TestCase
{
    use CustomAssertions;

    public function testDomesticDisputeEntityCreation()
    {
        $payment = $this->fixtures->create('payment:captured');

        $reason = $this->fixtures->create('dispute_reason');

        $dispute = new Entity;

        $dispute->associateReason($reason);

        $dispute->payment()->associate($payment);

        $dispute->merchant()->associate($payment->merchant);

        $now = Carbon::now()->getTimestamp();
        $months = Carbon::now()->addMonths(2)->getTimestamp();

        $input = [
            'gateway_dispute_id' => 'D12206606',
            'gateway_dispute_status' => 'Open',
            'phase' => 'Chargeback',
            'raised_on' => $now,
            'expires_on' => $months,
            'amount' => 1000,
            'skip_email' => true,
            'reason_id' => '8Mz7zLrCzuGvES',
        ];

        $dispute->build($input);

        $this->assertEquals($dispute->getAmount(), 1000);
        $this->assertEquals($dispute->getCurrency(), 'INR');
        $this->assertNull($dispute->getGatewayAmount());
    }

    public function testInternationalDisputeAudInr()
    {
        $payment = $this->fixtures->create('payment:captured', ['amount' => 1000, 'currency' => 'AUD', 'base_amount' => 10000]);

        $reason = $this->fixtures->create('dispute_reason');

        $dispute = new Entity;

        $dispute->associateReason($reason);

        $dispute->payment()->associate($payment);

        $dispute->merchant()->associate($payment->merchant);

        $now = Carbon::now()->getTimestamp();
        $months = Carbon::now()->addMonths(2)->getTimestamp();

        $input = [
            'gateway_dispute_id' => 'D12206606',
            'gateway_dispute_status' => 'Open',
            'phase' => 'Chargeback',
            'raised_on' => $now,
            'expires_on' => $months,
            'gateway_amount' => 10000,
            'gateway_currency' => 'INR',
            'skip_email' => true,
            'reason_id' => '8Mz7zLrCzuGvES',
        ];

        $store = Cache::store();

        Cache::shouldReceive('driver')
            ->andReturnUsing(function() use ($store)
            {
                return $store;
            });

        $this->mockCache('currency:exchange_rates_INR', function () { return ['AUD' => 0.09];});

        $dispute->build($input);

        $this->assertEquals(909, $dispute->getAmount());
        $this->assertEquals('AUD', $dispute->getCurrency());
        $this->assertEquals(10000, $dispute->getBaseAmount());
        $this->assertEquals('INR', $dispute->getBaseCurrency());
        $this->assertEquals(10000, $dispute->getGatewayAmount());
        $this->assertEquals('INR', $dispute->getGatewayCurrency());
        $this->assertEquals(90000, $dispute->getConversionRate());

        $this->mockCache('currency:exchange_rates_INR', function () { return ['AUD' => 0.11];});

        $dispute->build($input);

        $this->assertEquals(1000, $dispute->getAmount());
        $this->assertEquals('AUD', $dispute->getCurrency());
        $this->assertEquals(10000, $dispute->getBaseAmount());
        $this->assertEquals('INR', $dispute->getBaseCurrency());
        $this->assertEquals(10000, $dispute->getGatewayAmount());
        $this->assertEquals('INR', $dispute->getGatewayCurrency());
        $this->assertEquals(110000, $dispute->getConversionRate());
    }

    public function testInternationalDisputeInrUsd()
    {
        $payment = $this->fixtures->create('payment:captured', ['amount' => 10000]);

        $reason = $this->fixtures->create('dispute_reason');

        $dispute = new Entity;

        $dispute->associateReason($reason);

        $dispute->payment()->associate($payment);

        $dispute->merchant()->associate($payment->merchant);

        $now = Carbon::now()->getTimestamp();
        $months = Carbon::now()->addMonths(2)->getTimestamp();

        $input = [
            'gateway_dispute_id' => 'D12206606',
            'gateway_dispute_status' => 'Open',
            'phase' => 'Chargeback',
            'raised_on' => $now,
            'expires_on' => $months,
            'gateway_amount' => 90,
            'gateway_currency' => 'USD',
            'skip_email' => true,
            'reason_id' => '8Mz7zLrCzuGvES',
        ];

        $store = Cache::store();

        Cache::shouldReceive('driver')
            ->andReturnUsing(function() use ($store)
            {
                return $store;
            });

        $this->mockCache('currency:exchange_rates_USD', function () { return ['INR' => 100];}, 6);

        $dispute->build($input);

        $this->assertEquals(9090, $dispute->getAmount());
        $this->assertEquals('INR', $dispute->getCurrency());
        $this->assertEquals(9090, $dispute->getBaseAmount());
        $this->assertEquals('INR', $dispute->getBaseCurrency());
        $this->assertEquals(90, $dispute->getGatewayAmount());
        $this->assertEquals('USD', $dispute->getGatewayCurrency());
        $this->assertEquals(100000000, $dispute->getConversionRate());

        $input['gateway_amount'] = 110;
        $dispute->build($input);

        $this->assertEquals(10000, $dispute->getAmount());
        $this->assertEquals('INR', $dispute->getCurrency());
        $this->assertEquals(10000, $dispute->getBaseAmount());
        $this->assertEquals('INR', $dispute->getBaseCurrency());
        $this->assertEquals(110, $dispute->getGatewayAmount());
        $this->assertEquals('USD', $dispute->getGatewayCurrency());
        $this->assertEquals(100000000, $dispute->getConversionRate());
    }

    public function testInternationalDisputeUsdUsd()
    {
        $payment = $this->fixtures->create('payment:captured', ['amount' => 100, 'currency' => 'USD', 'base_amount' => 10000]);

        $reason = $this->fixtures->create('dispute_reason');

        $dispute = new Entity;

        $dispute->associateReason($reason);

        $dispute->payment()->associate($payment);

        $dispute->merchant()->associate($payment->merchant);

        $now = Carbon::now()->getTimestamp();
        $months = Carbon::now()->addMonths(2)->getTimestamp();

        $input = [
            'gateway_dispute_id' => 'D12206606',
            'gateway_dispute_status' => 'Open',
            'phase' => 'Chargeback',
            'raised_on' => $now,
            'expires_on' => $months,
            'gateway_amount' => 100,
            'gateway_currency' => 'USD',
            'skip_email' => true,
            'reason_id' => '8Mz7zLrCzuGvES',
        ];

        $store = Cache::store();

        Cache::shouldReceive('driver')
            ->andReturnUsing(function() use ($store)
            {
                return $store;
            });

        $this->mockCache('currency:exchange_rates_USD', function () { return ['INR' => 90];}, 1);

        $dispute->build($input);

        $this->assertEquals(100, $dispute->getAmount());
        $this->assertEquals('USD', $dispute->getCurrency());
        $this->assertEquals(9090, $dispute->getBaseAmount());
        $this->assertEquals('INR', $dispute->getBaseCurrency());
        $this->assertEquals(100, $dispute->getGatewayAmount());
        $this->assertEquals('USD', $dispute->getGatewayCurrency());
        $this->assertNull($dispute->getConversionRate());

        $this->mockCache('currency:exchange_rates_USD', function () { return ['INR' => 101];}, 1);

        $input['gateway_amount'] = 101;

        $dispute->build($input);

        $this->assertEquals(100, $dispute->getAmount());
        $this->assertEquals('USD', $dispute->getCurrency());
        $this->assertEquals(10303, $dispute->getBaseAmount());
        $this->assertEquals('INR', $dispute->getBaseCurrency());
        $this->assertEquals(101, $dispute->getGatewayAmount());
        $this->assertEquals('USD', $dispute->getGatewayCurrency());
        $this->assertNull($dispute->getConversionRate());
    }

    public function testInternationalDisputeAudUsd()
    {
        $payment = $this->fixtures->create('payment:captured', ['amount' => 1000, 'currency' => 'AUD', 'base_amount' => 10000]);

        $reason = $this->fixtures->create('dispute_reason');

        $dispute = new Entity;

        $dispute->associateReason($reason);

        $dispute->payment()->associate($payment);

        $dispute->merchant()->associate($payment->merchant);

        $now = Carbon::now()->getTimestamp();
        $months = Carbon::now()->addMonths(2)->getTimestamp();

        $input = [
            'gateway_dispute_id' => 'D12206606',
            'gateway_dispute_status' => 'Open',
            'phase' => 'Chargeback',
            'raised_on' => $now,
            'expires_on' => $months,
            'gateway_amount' => 100,
            'gateway_currency' => 'USD',
            'skip_email' => true,
            'reason_id' => '8Mz7zLrCzuGvES',
        ];

        $store = Cache::store();

        Cache::shouldReceive('driver')
            ->andReturnUsing(function() use ($store)
            {
                return $store;
            });

        $this->mockCache('currency:exchange_rates_USD', function () { return ['INR' => 101, 'AUD' => 8];}, 3);

        $dispute->build($input);

        $this->assertEquals(808, $dispute->getAmount());
        $this->assertEquals('AUD', $dispute->getCurrency());
        $this->assertEquals(10201, $dispute->getBaseAmount());
        $this->assertEquals('INR', $dispute->getBaseCurrency());
        $this->assertEquals(100, $dispute->getGatewayAmount());
        $this->assertEquals('USD', $dispute->getGatewayCurrency());
        $this->assertEquals(8000000, $dispute->getConversionRate());

        $this->mockCache('currency:exchange_rates_USD', function () { return ['INR' => 101, 'AUD' => 11];}, 3);

        $dispute->build($input);

        $this->assertEquals(1000, $dispute->getAmount());
        $this->assertEquals('AUD', $dispute->getCurrency());
        $this->assertEquals(10201, $dispute->getBaseAmount());
        $this->assertEquals('INR', $dispute->getBaseCurrency());
        $this->assertEquals(100, $dispute->getGatewayAmount());
        $this->assertEquals('USD', $dispute->getGatewayCurrency());
        $this->assertEquals(11000000, $dispute->getConversionRate());
    }

    protected function mockCache($key, $callable, $times = 2)
    {
        Cache::shouldReceive('get')
            ->times($times)
            ->with($key)
            ->andReturnUsing($callable);
    }
}
