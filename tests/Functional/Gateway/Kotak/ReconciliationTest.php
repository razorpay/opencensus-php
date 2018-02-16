<?php

namespace RZP\Tests\Functional\Gateway\Kotak;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ReconciliationTest extends TestCase
{
    use RequestResponseFlowTrait;
    use ReconciliationTrait;

    public function testReconciliationInTestMode()
    {
        $this->markTestSkipped('Re-write this test as per new flow, and to accommodate for all channels');

        $this->createSettlementsAndSettlementFile(3);

        // Added so that a new file name is created for next settlement
        $currentTime = Carbon::now(Timezone::IST);
        $currentTime->addSecond();
        Carbon::setTestNow($currentTime);

        $this->createSettlementsAndSettlementFile(
            2, Carbon::today(Timezone::IST)->subDays(5)->timestamp);

        $request = [
            'url' => '/settlements/reconcile/test/kotak',
            'method' => 'POST',
            'content' => []
        ];

        $this->ba->appAuth();

        $this->makeRequestAndGetContent($request);

        $ftas = $this->getEntities('fund_transfer_attempt', [], true);

        $attemptsWithUtr = $attemptsWithoutUtr = 0;

        foreach ($ftas['items'] as $attempt)
        {
            if ($attempt['utr'] === null)
            {
                $attemptsWithoutUtr++;
            }
            else
            {
                $attemptsWithUtr++;
            }
        }

        $this->assertEquals(2, $attemptsWithoutUtr);
        $this->assertEquals(3, $attemptsWithUtr);
    }
}
