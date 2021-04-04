<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Mail\Merchant\DailyReport as DailyReportMail;
use RZP\Models\Transaction;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Holidays;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;

class DailyReportTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;
    use CustomBrandingTrait;

    protected $settleAtTimestamp;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function setupDailyReport()
    {
        Mail::fake();

        $this->ba->publicTestAuth();

        $this->startTime = time();

        $this->setUpFixture();

        $content = $this->initiateSettlements('axis', $this->settleAtTimestamp);

        $this->assertNotEquals($content['axis']['count'], 0);

        $setl = $this->getLastEntity('settlement', true);

        $createdAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp + 5;

        $this->fixtures->settlement->edit($setl['id'], ['created_at' => $createdAt]);
    }

    public function testDailyReportForCustomOrg()
    {
        $this->setupDailyReport();

        $org = $this->createCustomBrandingOrgAndAssignMerchant('10000000000000');

        $this->generateDailyReport();

        Mail::assertQueued(DailyReportMail::class, function ($mail) use ($org)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('emails.merchant.daily_report', $mail->view);

            $this->assertCustomBrandingMailViewData($org, $viewData);

            return true;
        });
    }

    public function testDailyReportForRazorpayOrg()
    {
        $this->setupDailyReport();

        $testData = [
            'captured'    => ['count' => '4', 'sum' => '4000000'],
            'authorized'  => ['count' => '4', 'sum' => '4000000'],
            'refunds'     => ['count' => '2', 'sum' => '200000'],
            'settlements' => ['count' => '1', 'sum' => '3705600'],
        ];

        $this->generateDailyReport();

        Mail::assertQueued(DailyReportMail::class, function ($mail) use ($testData)
        {
            $viewData = $mail->viewData;

            $this->assertArraySelectiveEquals($testData, $viewData);

            $this->assertEquals('emails.merchant.daily_report', $mail->view);

            $this->assertRazorpayOrgMailData($viewData);

            return $mail->hasTo('test@razorpay.com');
        });
    }

    protected function setUpFixture()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(5)->timestamp + 5;
        $capturedAt = Carbon::today(Timezone::IST)->subDays(5)->timestamp + 10;

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

        $createdAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp + 5;
        $capturedAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp + 10;

        $this->settleAtTimestamp = (new Transaction\Core)->calculateSettledAtTimestamp($capturedAt, 3);

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

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->create('merchant:bank_account', ['merchant_id' => '10000000000000']);
    }
}
