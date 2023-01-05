<?php

namespace Functional\Gateway\File;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Mail\Gateway\CaptureFile\Base as CaptureMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

use Mail;
use Queue;

use RZP\Services\Mock\BeamService;

use Mockery;
use RZP\Models\Gateway\File;

class AxisCaptureFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp(): void
    {
        Carbon::setTestNow();
        $this->testDataFilePath = __DIR__ . '/helpers/AxisCaptureFileTestData.php';

        parent::setUp();
        $this->app['rzp.mode'] = Mode::TEST;

        $orgAttr = [
            'id'    =>  'CLTnQqDj9Si8bx',
        ];
        $this->fixtures->org->create($orgAttr);

        $this->fixtures->create('org_hostname', [
            'org_id' => $orgAttr['id'],
            'hostname' => 'test.axis.com',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => 'CLTnQqDj9Si8bx']);

        $this->mockCardVault();
        $this->mockCps();
        Mail::fake();

        $terminalAttr = [
            'gateway_terminal_id'   =>  'meowmeow',
            'merchant_id'           =>  '10000000000000',
            'gateway'               =>  'paysecure',
            'gateway_acquirer'      =>  'axis',
        ];

        $this->fixtures->terminal->create($terminalAttr);

        $beamServiceMock = $this->getMockBuilder(BeamService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['beamPush'])
            ->getMock();

        $this->app['beam'] = $beamServiceMock;
    }

    public function testFileCreatedSuccessfully()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6074451248942255';
        $payment['bank'] = 'axis';
        $x = $this->doAuthAndCapturePayment($payment);
        $this->fixtures->card->edit($x['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $payment['card']['number'] = '6074452159762526';
        $payment['bank'] = 'axis';
        $x = $this->doAuthAndCapturePayment($payment);
        $this->fixtures->card->edit($x['card_id'], ['vault_token' => 'XXXXXXXXXXY']);

        $payment['card']['number'] = '6074458984500924';
        $payment['bank'] = 'axis';
        $x = $this->doAuthAndCapturePayment($payment);
        $this->fixtures->card->edit($x['card_id'], ['vault_token' => 'XXXXXXXXXYY']);

        $this->ba->adminAuth();

        $this->app['beam']->method('beamPush')
            ->will($this->returnCallback(
                function ($pushData, $intervalInfo, $mailInfo, $synchronous)
                {
                    $this->assertEquals('axis_rupay_capture_file', $pushData['job_name']);

                    $this->assertEquals(1, count($pushData['files']));

                    return [
                        'failed' => 'cat',
                        'success' => 'it ran',
                    ];
                }));

        $content = $this->startTest();

        $file = $this->getLastEntity('file_store', true);

        $expectedFileDetails = [
            'type'        => 'axis_paysecure',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['items'][0]['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileDetails, $file);

        Mail::assertQueued(CaptureMail::class, function ($mail)
        {
            $this->assertEmpty($mail->attachments);
            self::assertEquals('capturefiles@razorpay.com', $mail->from[0]['address']);
            self::assertEquals('example@axisbank.com', $mail->to[0]['address']);
            return true;
        });
    }

    public function testFileCreatedSuccessfullyWithFullRefund()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6074451248942255';
        $payment['bank'] = 'axis';
        $x = $this->doAuthAndCapturePayment($payment);
        $this->fixtures->card->edit($x['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6074452159762526';
        $payment['bank'] = 'axis';
        $x = $this->doAuthAndCapturePayment($payment);
        $this->fixtures->card->edit($x['card_id'], ['vault_token' => 'XXXXXXXXXXY']);

        $this->refundPayment($x['id']);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6074458984500924';
        $payment['bank'] = 'axis';
        $payment['amount'] = '10000';
        $x = $this->doAuthAndCapturePayment($payment);
        $this->fixtures->card->edit($x['card_id'], ['vault_token' => 'XXXXXXXXXYY']);

        $refundEntity = $this->getDbLastEntity('refund');

        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $this->app['beam']->method('beamPush')
            ->will($this->returnCallback(
                function ($pushData, $intervalInfo, $mailInfo, $synchronous)
                {
                    $this->assertEquals('axis_rupay_capture_file', $pushData['job_name']);

                    $this->assertEquals(1, count($pushData['files']));

                    return [
                        'failed' => 'cat',
                        'success' => 'it ran',
                    ];
                }));

        $content = $this->startTest();

        $file = $this->getLastEntity('file_store', true);

        $expectedFileDetails = [
            'type'        => 'axis_paysecure',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['items'][0]['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileDetails, $file);

        Mail::assertQueued(CaptureMail::class, function ($mail)
        {
            $this->assertEmpty($mail->attachments);
            self::assertEquals('capturefiles@razorpay.com', $mail->from[0]['address']);
            self::assertEquals('example@axisbank.com', $mail->to[0]['address']);
            return true;
        });
    }

    public function testFileCreatedSuccessfullyWithPartialRefund()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6074451248942255';
        $payment['bank'] = 'axis';
        $x = $this->doAuthAndCapturePayment($payment);
        $this->fixtures->card->edit($x['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6074452159762526';
        $payment['bank'] = 'axis';
        $x = $this->doAuthAndCapturePayment($payment);
        $this->fixtures->card->edit($x['card_id'], ['vault_token' => 'XXXXXXXXXXY']);
        sleep(1);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6074458984500924';
        $payment['bank'] = 'axis';
        $payment['amount'] = '10000';
        $x = $this->doAuthAndCapturePayment($payment);
        $this->fixtures->card->edit($x['card_id'], ['vault_token' => 'XXXXXXXXXYY']);

        sleep(1);

        $this->refundPayment($x['id'], '5000');

        $refundEntity = $this->getDbLastEntity('refund');

        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $this->app['beam']->method('beamPush')
            ->will($this->returnCallback(
                function ($pushData, $intervalInfo, $mailInfo, $synchronous)
                {
                    $this->assertEquals('axis_rupay_capture_file', $pushData['job_name']);

                    $this->assertEquals(1, count($pushData['files']));

                    return [
                        'failed' => 'cat',
                        'success' => 'it ran',
                    ];
                }));

        $content = $this->startTest();

        $file = $this->getLastEntity('file_store', true);

        $expectedFileDetails = [
            'type'        => 'axis_paysecure',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['items'][0]['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileDetails, $file);

        Mail::assertQueued(CaptureMail::class, function ($mail)
        {
            $this->assertEmpty($mail->attachments);
            self::assertEquals('capturefiles@razorpay.com', $mail->from[0]['address']);
            self::assertEquals('example@axisbank.com', $mail->to[0]['address']);
            return true;
        });
    }

    protected function mockCps()
    {
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('fetchAuthorizationData')
            ->andReturnUsing(function( array $input)
            {
                $responseItems = [];
                $response = $this->mockCpsAuthFetchResponse($input['payment_ids']);
                foreach ($response['items'] as $item)
                {
                    $responseItems[$item['payment_id']] = $item;
                }
                return $responseItems;
            });
    }

    protected function mockCpsAuthFetchResponse($input)
    {
        $res = array_map(function($paymentId)
        {
            return [
                'id'         => 'acasd123',
                'payment_id' => $paymentId,
                'auth_code'  => 'A1233V',
                'rrn'        => '123456789101',
            ];
        }, $input);

        return [
            'count'  => sizeof($input),
            'entity' => 'authorize',
            'items'  => $res,
        ];
    }
}

