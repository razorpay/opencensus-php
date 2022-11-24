<?php

namespace RZP\Tests\Functional\Gateway\Hdfc;

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase payments are used, also tests if payments are automatically
 * captured on successful payments. Hold Payments are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HdfcGatewayAuthTest extends TestCase
{
    use PaymentTrait;

    protected $successDebitNumbers = array(
        '4005559876540',
        '4012001037167778',
        '4012001037490014',
    );

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/cards.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->enableInternational();
    }

    public function testCardTimeout()
    {
        $this->startTest();

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals('RP00013', $hdfc['error_code2']);
    }

    public function testCreditCardSuccess()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4012001038443335';

        $payment = $this->doAuthAndGetPayment($payment);
    }

    public function testCreditCardAuthNotAvailable1()
    {
        $this->startTest();
    }

    public function testCreditCardAuthNotAvailable2()
    {
        $this->startTest();
    }

    public function testCreditCardAuthNotAvailable3()
    {
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'auth_response')
            {
                unset($content['auth'], $content['tranid'],
                      $content['postdate'], $content['avr'],
                      $content['ref'], $content['amt']);

                $content['error_code_tag'] = 'FSS0001';
                $content['error_service_tag'] = 'null';
                $content['error_text'] = '!ERROR!-FSS0001-Authentication Not Available';
                $content['result'] = 'FSS0001-Authentication Not Available';
            }
        });

        $this->startTest();
    }

    public function testAcsFailure()
    {
        $this->markTestSkipped("not being used");
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authenticate')
            {
                $content['PaRes'] = 'eNpVkM0KwjAQhM/2KfbmrYlWxUIMlLaioCj9ETxGu2qhTSGJgm9vim3F2w7zDTu7DsseCjFK8fpUyJ0R26PW4o5QFquxX58W1yOdzKnnzxZLb06ndNxCsVKNssOIvVDpspF84lJ3ykgvWwtbKGwK5P6SkZ8avG4Vz5SQukRpQL+1wRpuoqxsnS7UY0MuQmMBnktxqRBMAwXeKmF6vrO/7WTRbc3i/fGQBMkZ0nNqBayD7S5PYtgEKRzC0I5R238I2DNJfycjQwlG/j/2AQDkZqM=';
            }
        });

        $this->startTest();
    }

    public function testSignatureFailure1()
    {
        $this->startTest();
    }

    public function testSignatureFailure2()
    {
        $this->startTest();
    }

    public function testDebitCardSuccess()
    {
        $payment = $this->getDefaultPaymentArray();

        foreach ($this->successDebitNumbers as $number)
        {
            $payment['card']['number'] = $number;
            $this->doAuthAndGetPayment($payment);
        }
    }

    public function testRupayFailedPayment()
    {
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'auth_response')
            {
                $content = [
                    'Error'     => 'PY20007',
                    'ErrorText' => 'PY20007-Invalid Order Status.',
                    'paymentid' => $content['paymentid'],
                    'trackid'   => $content['trackid'],
                    'udf1'      => 'test',
                    'udf2'      => 'a@b.com',
                    'udf3'      => '9918899029',
                    'udf4'      => 'test',
                    'udf5'      => 'test',
                ];
            }
        });

        $this->startTest();
    }

    public function testMaestroCard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5081597022059105';
        $this->doAuthAndCapturePayment($payment);
        $paymentRes = $this->getLastPayment(true);
        $this->assertEquals($paymentRes['gateway'], 'hdfc');

        $payment['card']['number'] = '5049730032100035510';
        $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastPayment(true);
        $this->assertEquals($payment['gateway'], 'hdfc');
    }

    public function testCreditCardAuthNotApproved()
    {
        // is this not enrolled??
        $this->startTest();
    }

    public function testDebitCardAuthNotApproved()
    {
        // is this not enrolled??
        $this->startTest();
    }

    public function testMockOnLiveMode()
    {
        $this->app['config']->set('gateway.mock_hdfc', true);

        $this->ba->publicAuth('rzp_live_TheLiveAuthKey');

        // $this->fixtures->merchant->activate();

        $this->fixtures
             ->on('live')
             ->create('terminal', ['merchant_id' => '10000000000000']);

        $this->startTest();
    }

    public function testAuthNotEnrolledDeniedByRisk()
    {
        $this->hdfcPaymentMockResultCode('DENIED BY RISK', 'authorize');

        // For non 3dsecure case
        $this->makeRequestAndCatchException(
            function ()
            {
                $payment = $this->doAuthPayment();
            });

        $hdfc = $this->getLastEntity('hdfc', true);
        $this->assertTestResponse($hdfc);

        $payment = $this->getLastPayment(true);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['internal_error_code'], ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK);

    }

    public function testAuthEnrolledDeniedByRisk()
    {
        $this->hdfcPaymentMockResultCode('DENIED BY RISK', 'authorize');
        // For 3dsecure case
        $this->makeRequestAndCatchException(
            function ()
            {
                $payment = $this->getDefaultPaymentArray();
                $payment['card']['number'] = '4012001037490014';
                $payment = $this->doAuthPayment($payment);
            });

        $hdfc = $this->getLastEntity('hdfc', true);
        $payment = $this->getLastPayment(true);
        $this->assertTestResponse($hdfc);

        $payment = $this->getLastPayment(true);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['internal_error_code'], ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK);
    }

    public function testJsonpPaymentReturnFields()
    {
        $fields = array(
            'type',
            'request',
            'version',
            'payment_id',
            'gateway',
            'amount',
            'image',
            'magic',
            'http_status_code',
            );

        $dataFields = array(
            'TermUrl',
            'MD',
            'PaReq');

        $content = $this->startTest();

        $this->assertEquals($fields, array_keys($content));
        $this->assertEquals($dataFields, array_keys($content['request']['content']));
    }

    public function testEnrollResponseWithOnlyErrorText()
    {
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'enroll')
            {
                $content = [
                    'error_text' => '!ERROR!-GW00555-Terminal ID is Deactivated, Please contact PG Helpdesk.',
                    'trackid'    => $content['trackid'],
                    'udf1'       => 'test',
                    'udf2'       => 'a@b.com',
                    'udf3'       => '9918899029',
                    'udf4'       => 'test',
                    'udf5'       => 'test',
                ];
            }
        });

        $this->startTest();
    }

    public function testMetadataErrorResponseWithFeatureFlag()
    {
        $this->hdfcPaymentMockResultCode('DENIED BY RISK', 'authorize');

        $content = $this->startTest();

        $this->assertArrayHasKey('metadata', $content['error']);

        $this->assertArrayHasKey('payment_id', $content['error']['metadata']);

    }

    public function testDetailedErrorResponseForCard()
    {
        $this->hdfcPaymentMockResultCode('DENIED BY RISK', 'authorize');

        $content = $this->startTest();

        $this->assertArrayHasKey('metadata', $content['error']);

        $this->assertArrayHasKey('payment_id', $content['error']['metadata']);

        $this->assertArrayHasKey('reason', $content['error']);

        $payment = $this->getLastEntity('payment', true);
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceDefaultValues($testData['request']['content']);

        return $this->runRequestResponseFlow($testData);
    }
}
