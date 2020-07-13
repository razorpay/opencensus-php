<?php

namespace RZP\Tests\Functional\Payout;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payout;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Mail\Admin\BankingScorecard as XScorecardMail;

class BankingScorecardTest extends TestCase
{
    use PayoutTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BankingScorecardTestData.php';

        parent::setUp();
    }

    public function testBankingScorecardMailCheck()
    {
        Mail::fake();

        $this->ba->publicAuth();

        $this->createPayoutEntities();

        $this->ba->appAuth();

        $this->startTest();

        Mail::assertSent(XScorecardMail::class);
    }

    public function createPayoutEntities()
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp + 5;

        return $this->fixtures->times(5)->create('payout',
            [
                'created_at' => $createdAt,
                'updated_at' => $createdAt + 10,
                'status'     => Payout\Status::PROCESSED
            ]
        );
    }
}
