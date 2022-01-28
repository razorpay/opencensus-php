<?php

namespace RZP\Tests\Functional\Payout;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payout;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\Admin\BankingScorecard as XScorecardMail;

class BankingScorecardTest extends TestCase
{
    use PayoutTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    private $merchantId = '10000000100001';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BankingScorecardTestData.php';

        parent::setUp();

        $this->fixtures->create('merchant', ['id' => $this->merchantId, 'email' => 'ayush.singhal@gmail.com']);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($this->merchantId, 'rzp_test_' . $this->merchantId);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testBankingScorecardMailCheck()
    {
        $this->fixtures->create('balance',
            [
                'id'             => '10000000100001',
                'type'           => 'primary',
                'account_type'   => 'shared',
                'balance'        => 100000000000
            ]);

        Mail::fake();

        $this->ba->publicAuth();

        $days = $this->createPayoutEntities();

        $this->ba->cronAuth();

        $this->fixtures->edit('balance', '10000000100001',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
            ]);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout')->toArray();

        $monthTaxCount = 2 * $days;
        $monthTpv = round(($payout['amount'] * 2 * 1.0 * $days) / 1000000000, 2);
        $monthFeesCollected = round(($payout['fees'] * 2 * $days) / 100, 2);

        $yesterdayTpv = round(($payout['amount'] * 2 * 1.0) / 1000000000, 2);
        $yesterdayFeesCollected = round(($payout['fees'] * 2) / 100, 2);

        Mail::assertSent(XScorecardMail::class, function ($mail) use ($monthTaxCount, $monthTpv, $monthFeesCollected, $yesterdayTpv, $yesterdayFeesCollected)
        {
            $mailData = $mail->viewData;

            // assert for month data
            $this->assertEquals($monthTaxCount, $mailData['month_tax_count']);
            $this->assertEquals($monthTpv, round($mailData['month_tpv'],2));
            $this->assertEquals($monthFeesCollected, round($mailData['month_fees_collected'],2));

            // assert for yesterday data
            $this->assertEquals(2, $mailData['yesterday_tax_count']);
            $this->assertEquals($yesterdayTpv, round($mailData['yesterday_tpv'],2));
            $this->assertEquals($yesterdayFeesCollected, round($mailData['yesterday_fees_collected'],2));

            return true;
        });
    }

    public function createPayoutEntities()
    {
        $start = Carbon::yesterday(Timezone::IST)->startOfMonth()->startOfDay();
        $end = Carbon::today(Timezone::IST)->startOfDay();

        $from = $start;

        $noOfDays = $from->diffInDays($end);

        for ( $i = 0; $i < $noOfDays; $i++ )
        {
            $time = $start->copy();

            $this->fixtures->create('payout',
                [
                    'created_at'        => $time->addDay($i)->addHours(1)->timestamp,
                    'updated_at'        => $time->timestamp,
                    'status'            => Payout\Status::PROCESSED,
                    'merchant_id'       => $this->merchantId,
                    'balance_id'        => '10000000100001',
                    'amount'            => 100000000,
                    'pricing_rule_id'   => '1nvp2XPMmaRLxb',
                ]
            );

            $this->fixtures->create('payout',
                [
                    'created_at'        => $time->addHours(10)->timestamp,
                    'updated_at'        => $time->timestamp,
                    'status'            => Payout\Status::PROCESSED,
                    'merchant_id'       => $this->merchantId,
                    'balance_id'        => '10000000100001',
                    'amount'            => 100000000,
                    'pricing_rule_id'   => '1nvp2XPMmaRLxb',
                ]
            );
        }

        return $noOfDays;
    }
}
