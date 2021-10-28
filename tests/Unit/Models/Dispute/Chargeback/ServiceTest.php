<?php

namespace Unit\Models\Dispute\Chargeback;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Dispute\Chargeback\Service;

class ServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testIsSunday()
    {
        $testCases = [
            [
                'now'      => Carbon::create(2021, 9, 28, 12, null, null, Timezone::IST)->getTimestamp(),
                'isSunday' => false,
            ],
            [
                'now'      => Carbon::create(2021, 9, 26, 12, null, null, Timezone::IST)->getTimestamp(),
                'isSunday' => true,
            ],
        ];

        $service = new Service();

        foreach ($testCases as $testCase)
        {
            $actualValue = $service->isSunday($testCase['now']);

            $this->assertEquals($testCase['isSunday'],$actualValue);
        }

    }

    /*
     * for CBK And RR it should add 3 days
     * for rest only 2 days
     * addition of days should exclude sundays
     */
    public function testGetExpiryDate()
    {
        $testCases = [
            [
                'now'         => Carbon::create(2021, 9, 28, 12, null, null, Timezone::IST),
                'disputeType' => 'CBK',
                'want'        => "01/10/2021"
            ],
            [
                'now'         => Carbon::create(2021, 9, 25, 12, null, null, Timezone::IST),
                'disputeType' => 'CBK',
                'want'        => "29/09/2021"
            ],
            [
                'now'         => Carbon::create(2021, 9, 26, 12, null, null, Timezone::IST),
                'disputeType' => 'RR',
                'want'        => "29/09/2021"
            ],
            [
                'now'         => Carbon::create(2021, 9, 26, 12, null, null, Timezone::IST),
                'disputeType' => 'PREARB',
                'want'        => "28/09/2021"
            ],
        ];

        $service = new Service();

        foreach ($testCases as $testCase)
        {
            Carbon::setTestNow($testCase['now']);

            $actualValue = $service->getExpiryDate($testCase['disputeType']);

            $this->assertEquals($testCase['want'], $actualValue);
        }
    }
}