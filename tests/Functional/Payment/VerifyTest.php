<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

/**
 * Tests for refund payments
 *
 * For refund payments, first we need to create a
 * captured payment. By default, an captured payment entity
 * is provided. However, it doesn't have a corresponding record
 * in hdfc gateway.
 *
 * So refund tests which supposedly hit hdfc gateway for refund,
 * should first call for a normal hdfc authorized + captured payment
 * instead of utilizing the default created payment entity.
 */

class VerifyTest extends TestCase
{
    use PaymentTrait;

    protected $payment = null;

    public function setUp()
    {
        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->ba->privateAuth();
    }

    public function testVerifySingleFailedPayments()
    {
        $createdAt = time() - 3*60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $this->ba->appAuth();

        $this->runVerifyForMaxPeriod();
    }

    public function testVerifyMultipleFailedPayments()
    {
        $createdAt = time() - 3 * 60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $createdAt = time() - 4 * 60;

        $payment2 = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $this->ba->appAuth();

        $result = [
            'filter'  => 'payments_failed',
            'all'     => 2,
            'none'    => 0,
        ];

        $this->runVerifyForMaxPeriod($result);
    }

    public function testVerifySingleCreatedPayments()
    {
        $createdAt = time() - 3*60;

        $payment = $this->fixtures->create(
            'payment:netbanking_created', ['created_at' => $createdAt]);

        $this->ba->appAuth();

        $verifiedResultArray = [
            'filter'  => 'payments_created',
            'all'     => 1,
            'none'    => 0,
        ];

        $filter = $verifiedResultArray['filter'];

        $time = new Carbon('now');

        $request = [
            'url' => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $verifiedResultArray['all'], $filter);

        foreach (range(0, 5) as $index)
        {
            $time->addSeconds(150);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $this->assertContent($content, $verifiedResultArray['all'], $filter);
        }
    }

    protected function runVerifyForMaxPeriod($verifiedResultArray = null)
    {
        if ($verifiedResultArray === null)
        {
            $verifiedResultArray = [
                'filter'  => 'payments_failed',
                'all'     => 1,
                'none'    => 0,
            ];
        }

        $filter = $verifiedResultArray['filter'];

        $time = new Carbon('now');

        $request = [
            'url' => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $verifiedResultArray['all'], $filter);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $verifiedResultArray['all'], $filter);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $verifiedResultArray['none'], $filter);

        $time->addMinutes(45);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $verifiedResultArray['all'], $filter);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $verifiedResultArray['none'], $filter);

        foreach (range(1, 7) as $day)
        {
            $time->addDay(1);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $this->assertContent($content, $verifiedResultArray['all'], $filter);

            $content = $this->makeRequestAndGetContent($request);

            $this->assertContent($content, $verifiedResultArray['none'], $filter);
        }

        $time->addDay(1);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $verifiedResultArray['none'], $filter);

        Carbon::setTestNow();
    }

    protected function assertContent(array $content, $verified, $filter)
    {
        unset($content['totalTime']);

        $defaultParams = [
            'filter'            => $filter,
            'verified'          => $verified,
            'authorized/failed' => 0,
            'timed out'         => 0,
            'error'             => 0,
            'authorizedTime'    => 0,
        ];

        $this->assertEquals($defaultParams, $content);
    }
}
