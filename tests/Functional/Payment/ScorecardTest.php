<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Carbon\Carbon;

class ScorecardTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/ScorecardTestData.php';

        parent::setUp();
    }

    public function testScorecard()
    {
        $this->ba->publicAuth();

        $prEntities = $this->createPaymentEntities();

        $this->ba->appAuth();

        $this->startTest();
    }

    public function createPaymentEntities()
    {
        $prEntities = array();

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(1)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(1)->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

        return $payments;
    }
}