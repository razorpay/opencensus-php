<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Razorpay\IFSC\Bank;
use Redis;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

trait PaymentVerifyTrait {

    protected function setupRedisMock($paymentArray = [])
    {
        return;
    }

    protected function setupRedisMockForBlockedGateway($paymentArray = [])
    {
        $redisMock = $this->getMockBuilder(Redis::class)
            ->setMethods(['hGetAll', 'set', 'get', 'setex', 'client', 'exists', 'hDel', 'hSet', 'incr', 'expire', 'hGet'])
            ->getMock();

        Redis::shouldReceive('connection')
            ->andReturn($redisMock);

        $redisMock->method('hGetAll')
            ->willReturn(
                [], [
                    'ebs' => Carbon::now()->getTimestamp() + 900]
                , [
                'ebs' => Carbon::now()->getTimestamp() + 900
            ], [
                'ebs' => Carbon::now()->getTimestamp() + 900
            ], [
                'ebs' => Carbon::now()->getTimestamp() + 900
            ], [
                'ebs' => Carbon::now()->getTimestamp() + 900
            ], [
                'ebs' => Carbon::now()->getTimestamp() + 900
            ]);

        $redisMock->method('hDel')
            ->willReturn([]);

        $redisMock->method('hSet')
            ->willReturn(null);

        $redisMock->method('incr')
            ->willReturn(101);

        $redisMock->method('set')
            ->willReturnCallback(
                function($arg) use ($paymentArray)
                {
                    foreach($paymentArray as $payment)
                    {
                        if($payment['id'] . '_verify' === $arg)
                        {
                            return null;
                        }
                    }
                    return true;
                });

        $redisMock->method('get')
            ->willReturn(1);
    }

    protected function setupRedisMockForBlockedPayments()
    {
        $redisMock = $this->getMockBuilder(Redis::class)
            ->setMethods(['hGetAll', 'set', 'get', 'setex', 'client', 'exists', 'hDel', 'hSet', 'incr', 'expire', 'hGet'])
            ->getMock();

        Redis::shouldReceive('connection')
            ->andReturn($redisMock);

        $redisMock->method('hGetAll')
            ->willReturn(
                [],
                [
                    'ebs' => Carbon::now()->getTimestamp() + 900
                ],
                [
                    'ebs' => Carbon::now()->getTimestamp() + 900
                ]);

        $redisMock->method('hDel')
            ->willReturn([]);

        $redisMock->method('hSet')
            ->willReturn(null);

        $redisMock->method('set')
            ->willReturn(1);

        $redisMock->method('get')
            ->willReturn(1);
    }

    protected function runCreateVerify()
    {
        $this->setupRedisMock();

        $verifiedResultArray = [
            'filter' => 'payments_created',
            'all'    => 1,
            'none'   => 0,
        ];

        $filter = $verifiedResultArray['filter'];

        $time = Carbon::now(Timezone::IST);

        $request = [
            'url'    => '/payments/verify/' . $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => $verifiedResultArray['all'],
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        foreach(range(0, 2) as $index)
        {
            $time->addSeconds(150);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'success' => $verifiedResultArray['all'],
                'filter'  => $filter,
            ];

            $this->assertContent($content, $resultData);
        }

        $minutesArray = [12, 30, 2 * 60, 6 * 60, 60 * 24, 2 * 60 * 24, 3 * 60 * 24, 4 * 60 * 24];

        foreach($minutesArray as $minutes)
        {
            Carbon::setTestNow();

            $time = Carbon::now(Timezone::IST);

            $time->addMinutes($minutes);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'success' => $verifiedResultArray['all'],
                'filter'  => $filter,
            ];
            $this->assertContent($content, $resultData);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'success' => $verifiedResultArray['none'],
                'filter'  => $filter,
            ];
            $this->assertContent($content, $resultData);
        }

        Carbon::setTestNow();
    }

    protected function runVerifyForMaxPeriod($verifiedResultArray = null)
    {
        $this->setupRedisMock();

        if($verifiedResultArray === null)
        {
            $verifiedResultArray = [
                'filter' => 'payments_failed',
                'all'    => 1,
                'none'   => 0,
            ];
        }

        $filter = $verifiedResultArray['filter'];

        $request = [
            'url'    => '/payments/verify/' . $filter,
            'method' => 'post'
        ];

        $minutesArray = [0, 12, 30, 2 * 60, 6 * 60, 60 * 24, 2 * 60 * 24, 3 * 60 * 24, 4 * 60 * 24];

        foreach($minutesArray as $minutes)
        {
            $time = Carbon::now(Timezone::IST);

            $time->addMinutes($minutes);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'success' => $verifiedResultArray['all'],
                'filter'  => $filter,
            ];
            $this->assertContent($content, $resultData);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'success' => $verifiedResultArray['none'],
                'filter'  => $filter,
            ];

            $this->assertContent($content, $resultData);

            Carbon::setTestNow();
        }

        $time->addDay(5);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => $verifiedResultArray['none'],
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    protected function assertContent(array $content, array $param)
    {
        // We dont want to check time taken for payments
        unset($content['total_time']);

        unset($content['authorize_time']);

        unset($content['fetch_time']);

        // TODO : built it from previous values
        unset($content['verifiable_count']);

        $defaultParams = [
            'success'        => 0,
            'authorized'     => 0,
            'timeout'        => 0,
            'error'          => 0,
            'not_applicable' => 0,
            'unknown'        => 0,
            'bucket_filter'  => [],
            'request_error'  => 0
        ];

        $total = array_sum($defaultParams);

        $defaultParams = array_merge($defaultParams, $param);

        $defaultParams['verified_payments'] = $defaultParams['success'] +
            $defaultParams['authorized'] + $defaultParams['timeout'] +
            $defaultParams['error'] + $defaultParams['not_applicable'] + $defaultParams['unknown'];

        if($defaultParams['not_applicable'] === 0)
        {
            unset($defaultParams['not_applicable']);
        }

        $this->assertEquals($defaultParams, $content);
    }

    protected function createFailedPayment($data)
    {

        $this->getErrorInCallback();

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $payment = $this->doAuthAndCapturePayment($payment);
            }
        );

        $payment = $this->getDbLastEntityPublic('payment');

        $this->resetMockServer();

        return $payment;
    }

    protected function createMultipleFailedPaymentWithOrder($data)
    {
        $payments = [];

        $this->getErrorInCallback();

        $order = $this->fixtures->create('order', ['id' => '100000000order', 'amount' => 50000, 'payment_capture' => true]);

        $this->assertEquals('created', $order['status']);

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

        $payment["order_id"] = 'order_' . $order["id"];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            }
        );
        $paymentdata = $this->getDbLastEntityPublic('payment');

        $payments[] = $paymentdata['id'];


        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            }
        );

        $paymentdata = $this->getDbLastEntityPublic('payment');

        $payments[] = $paymentdata['id'];

        $this->resetMockServer();

        return $payments;

    }

    protected function createFailedPaymentWithOrderWithConfig($data, $configArr)
    {
        $payments = [];

        $this->getErrorInCallback();

        $order = $this->createOrderForBank(Bank::UBIN, $configArr);

        $this->assertEquals('created', $order['status']);

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

        $payment["order_id"] = $order["id"];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            }
        );
        $paymentdata = $this->getDbLastEntityPublic('payment');

        $payments[] = $paymentdata['id'];

        $this->resetMockServer();

        return $payments;
    }

    protected function createOrderForBank($bank, $configArr)
    {

        $request = [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'netbanking',
                'account_number'  => '0040304030403040',
                'bank'            => $bank,
                'payment'         => $configArr,
                'payment_capture' => 1,
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->ba->publicAuth();

        return $content;
    }
}

