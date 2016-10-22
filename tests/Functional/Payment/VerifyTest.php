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

    public function testVerifyAllPayments()
    {
        $createdAt = time() - 60 * 60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $request = array(
            'url' => '/payments/verify/all',
            'method' => 'post'
        );

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        unset($content['totalTime']);

        $this->assertEquals(
            [
                'filter'            => 'all',
                'verified'          => 1,
                'authorized/failed' => 0,
                'timed out'         => 0,
                'error'             => 0,
                'authorizedTime'    => 0,
            ],
            $content);
    }

    public function testVerifyMultipleFailedPayments()
    {
        $createdAt = time() - 60 * 60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $createdAt = time() - 60 * 60 - 86400;

        $payment2 = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $request = [
            'url' => '/payments/verify/all',
            'method' => 'post'
        ];

        $this->ba->appAuth();

        //TODO: add verify result and call runVerifyForMaxPeriod
    }

    public function testVerifySingleFailedPayments()
    {
        $createdAt = time() - 3*60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $this->ba->appAuth();

        $this->runVerifyForMaxPeriod();
    }

    protected function runVerifyForMaxPeriod($verifiedResultArray = ['all' =>1, 'partial' => 0,'none'=> 0])
    {
        $time = new Carbon('now');

        $request = [
            'url' => '/payments/verify/all',
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, ['verified' => $verifiedResultArray['all']]);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, ['verified' => $verifiedResultArray['all']]);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, ['verified' => $verifiedResultArray['none']]);

        $time->addMinutes(45);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, ['verified' => $verifiedResultArray['all']]);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, ['verified' => $verifiedResultArray['none']]);

        foreach (range(1, 7) as $day)
        {
            $time->addDay(1);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $this->assertContent($content, ['verified' => $verifiedResultArray['all']]);

            $content = $this->makeRequestAndGetContent($request);

            $this->assertContent($content, ['verified' => $verifiedResultArray['none']]);
        }

        $time->addDay(1);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, ['verified' => $verifiedResultArray['none']]);
    }

    protected function assertContent(array $content, array $param = [])
    {
        unset($content['totalTime']);

        $defaultParams = [
            'filter'            => 'all',
            'verified'          => 1,
            'authorized/failed' => 0,
            'timed out'         => 0,
            'error'             => 0,
            'authorizedTime'    => 0,
        ];

        $defaultParams = array_merge($defaultParams, $param);

        $this->assertEquals($defaultParams, $content);

    }
}
