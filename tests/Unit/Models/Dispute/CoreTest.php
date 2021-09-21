<?php

namespace RZP\Tests\Unit\Models\Dispute;

use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Models\Dispute\Entity;
use RZP\Models\Dispute\Constants;
use Illuminate\Support\Facades\DB;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Dispute\Reason\Service as DisputeReasonService;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;

class CoreTest extends TestCase
{
    public function testGetReasonAndNetworkCode()
    {
        $reasons = $this->getReasonsForGetReasonAndNetworkCode();

        foreach ($reasons as $reason)
        {
            DB::table(Table::DISPUTE_REASON)->insert($reason);
        }

        $tests = $this->getTestsForGetReasonAndNetworkCode();

        foreach ($tests as $test)
        {
            $response = DisputeReasonService::getNewInstance()->getReasonAndNetworkCode(
                $test['input_reason_code'], $test['network']
            );

            $this->assertEquals($test['expected_network_code'], $response['network_code']);

            $this->assertEquals('this is the code', $response['reason_code']);
        }
    }

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

    private function getReasonsForGetReasonAndNetworkCode()
    {
        $reasons = [
            [
                'id'                  => str_random(14),
                'network'             => 'Visa',
                'gateway_code'        => '81',
                'gateway_description' => '',
                'code'                => 'this is the code',
                'description'         => '',
                'created_at'          => time(),
                'updated_at'          => time(),
            ],
            [
                'id'                  => str_random(14),
                'network'             => 'RuPay',
                'gateway_code'        => '123',
                'gateway_description' => '',
                'code'                => 'this is the code',
                'description'         => '',
                'created_at'          => time(),
                'updated_at'          => time(),
            ],
            [
                'id'                  => str_random(14),
                'network'             => 'RZP',
                'gateway_code'        => 'RZP00',
                'gateway_description' => '',
                'code'                => 'this is the code',
                'description'         => '',
                'created_at'          => time(),
                'updated_at'          => time(),
            ]
        ];

        return $reasons;
    }

    private function getTestsForGetReasonAndNetworkCode()
    {
        $tests = [
            [
                'network'                => 'Visa',
                'input_reason_code'      => '10.3',
                'expected_network_code'  => 'Visa-81',
            ],
            [
                'network'                => 'Visa',
                'input_reason_code'      => '12.7',
                'expected_network_code'  => 'RZP-RZP00',
            ],
            [
                'network'                => 'MasterCard',
                'input_reason_code'      => '123',
                'expected_network_code'  => 'RZP-RZP00',
            ],
            [
                'network'                => 'RuPay',
                'input_reason_code'      => '123',
                'expected_network_code'  => 'RuPay-123',
            ],
            [
                'network'                => 'Visa',
                'input_reason_code'      => '13.3',
                'expected_network_code'  => 'RZP-RZP00',
            ]
        ];

        return $tests;
    }
}
