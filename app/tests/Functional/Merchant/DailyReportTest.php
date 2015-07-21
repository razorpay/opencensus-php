<?php

namespace Tests\Functional\Merchant;

use Carbon\Carbon;
use Mockery;
use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;
use Tests\Functional\Settlement\SettlementTrait;

class DailyReportTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;

    public function setUp()
    {
        parent::setUp();
    }

    public function testDailyReport()
    {
        $this->startTime = time();

        $this->setUpFixture();

        $content = $this->initiateSettlements();

        $setl = $this->getLastEntity('settlement', true);

        $id = substr($setl['id'], 5, 14);
        $setl = (new \Models\Settlement\Repository)->findOrFail($id);
        $createdAt = Carbon::today('Asia/Kolkata')->subDays(1)->timestamp + 5;
        $setl['created_at'] = $createdAt + 100;
        $setl->saveOrFail();

        \Mail::shouldReceive('queue')
              ->once()
              ->with(
                    Mockery::any(),
                    Mockery::on(function ($data)
                        {
                            $testData = array(
                                'captured' => ['payments' => ['count' => 4], 'sum' => 4000000],
                                'authorized' => ['payments' => ['count' => 4], 'sum' => 4000000],
                                'refunds' => ['refunds' => ['count' => 2], 'sum' => 200000],
                                'settlement' => ['merchant_id' => '10000000000000', 'amount' => 3508800],
                                'merchant' => ['id' => '10000000000000', 'activated' => true],
                            );
                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }),
                    Mockery::any());

        $this->generateDailyReport();
    }

    protected function setUpFixture()
    {
        $createdAt = Carbon::today('Asia/Kolkata')->subDays(5)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(5)->timestamp + 10;

        $capturedPayments = $this->fixtures->times(4)->create(
            'payment:captured',
            ['captured_at' => $capturedAt,
             'created_at' => $createdAt,
             'updated_at' => $createdAt + 10]);

        $r = range(0,1);

        foreach ($r as $i)
        {
            $attrs = [
                'payment' => $capturedPayments[0],
                'amount' => '100000',
                 'created_at' => $createdAt + 20,
                 'updated_at' => $createdAt + 20];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);
        }

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(1)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(1)->timestamp + 10;

        $capturedPayments = $this->fixtures->times(4)->create(
            'payment:captured',
            ['captured_at' => $capturedAt,
             'created_at' => $createdAt,
             'updated_at' => $createdAt + 10]);

        foreach ($r as $i)
        {
            $attrs = [
                'payment' => $capturedPayments[0],
                'amount' => '100000',
                 'created_at' => $createdAt + 20,
                 'updated_at' => $createdAt + 20];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);
        }

        $authorizedPayments = $this->fixtures->times(4)->create(
            'payment:authorized',
            ['created_at' => $createdAt,
             'updated_at' => $createdAt + 10]);

        $this->fixtures->links['merchant']->activate('10000000000000');
        $this->fixtures->create('merchant:bank_account', ['merchant_id' => '10000000000000']);
    }
}