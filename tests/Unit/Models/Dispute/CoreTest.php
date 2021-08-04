<?php

namespace RZP\Tests\Unit\Models\Dispute;

use Carbon\Carbon;
use RZP\Models\Dispute\Entity;
use RZP\Models\Dispute\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;

class CoreTest extends TestCase
{
    public function testCustomerDisputeFDInstanceRoute()
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

        $reflection = new \ReflectionClass('RZP\Models\Dispute\Core');

        $disputeCore = $reflection->newInstanceWithoutConstructor();

        $getFdInstanceMethod = $reflection->getMethod('getFreshdeskInstance');

        $getFdInstanceMethod->setAccessible(true);

        $dispute->setCreatedAt(Constants::FD_IND_INSTANCE_ROLLOUT_TS + 1);

        $fdInstance = $getFdInstanceMethod->invokeArgs($disputeCore, [$dispute]);

        $this->assertEquals($fdInstance, FreshdeskConstants::URLIND);

        $dispute->setCreatedAt(Constants::FD_IND_INSTANCE_ROLLOUT_TS - 1);

        $fdInstance = $getFdInstanceMethod->invokeArgs($disputeCore, [$dispute]);

        $this->assertEquals($fdInstance, FreshdeskConstants::URL);
    }
}
