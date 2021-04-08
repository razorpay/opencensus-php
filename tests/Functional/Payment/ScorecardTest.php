<?php

namespace RZP\Tests\Functional\Payment;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Mail\Admin\Scorecard as ScorecardMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class ScorecardTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/ScorecardTestData.php';

        parent::setUp();
    }

    public function testScorecard()
    {
        Mail::fake();

        $this->ba->publicAuth();

        $prEntities = $this->createPaymentEntities();

        $this->ba->cronAuth();

        $this->startTest();

        Mail::assertSent(ScorecardMail::class);
    }

    public function createPaymentEntities()
    {
        $prEntities = array();

        $createdAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp + 5;
        $capturedAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

        return $payments;
    }
}
