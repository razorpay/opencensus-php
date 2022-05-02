<?php

namespace RZP\Tests\Functional\Refund;

use DB;
use Mail;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Method;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Merchant\BalancePositiveAlert;
use RZP\Mail\Merchant\NegativeBalanceAlert;
use RZP\Mail\Merchant\NegativeBalanceThresholdAlert;

class RefundWithNegativeBalanceTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RefundTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->ba->privateAuth();
    }

    public function startTest($paymentId = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $paymentId, $amount);

        return $this->runRequestResponseFlow($testData);
    }


    protected function setRequestData(& $request, $id = null, $amount = null)
    {
        if ($amount !== null)
        {
            $request['content']['amount'] = $amount;
        }

        $url = '/payments/'.$id.'/refund';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    //Negative Balance Tests
    public function testRefundWithZeroBalance()
    {
        Mail::fake();

        $this->negativeRefundFixtures('balance', 0, 0, 0);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 0]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    //refund flow allowed for negative
    public function testRefundWithZeroBalanceRefundFlow()
    {
        Mail::fake();

        $this->negativeRefundFixtures('balance', 50000, 0, 0);

        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 0]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals(100, $viewData['percentage']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_threshold_alert', $mail->view);

            return true;
        });
    }

    //refund flow allowed for negative
    public function testRefundWithNegativeBalance()
    {
        Mail::fake();

        $this->negativeRefundFixtures('balance', 210000, 0, 0);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => -4800]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
    }

    public function testRefundWithNegativeBalanceMultipleBreach()
    {
        Mail::fake();

        $this->negativeRefundFixtures('balance', 500000, 0, 0);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => -240000]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => -255000]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => -375000]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class, 2);
    }

    //refund flow allowed for negative
    public function testRefundWithNegativeBalanceAndReserveBalance()
    {
        Mail::fake();

        $this->negativeRefundFixtures('balance', 5000, 0, 500000);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => -4800]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
    }

    //refund flow allowed for reserve
    public function testRefundWithZeroBalanceWithReserveBalanceCrossingThreshold()
    {
        Mail::fake();

        $this->negativeRefundFixtures('balance', 0, 0, 50000);

        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 0]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals(100, $viewData['percentage']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_threshold_alert', $mail->view);

            return true;
        });
    }

    public function testRefundWithReserveBalance()
    {
        Mail::fake();

        $this->negativeRefundFixtures('balance', 0, 0, 500000);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => -4800]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
    }

    public function testRefundWithZeroRefundCredits()
    {
        Mail::fake();

        $this->negativeRefundFixtures('credits', 0, 0, 0);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000',
            [
                'balance'           => 0,
                'refund_credits'    => 0
            ]
        );

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    //International Currency Tests for negative balance
    public function testRefundWithZeroBalanceInternationalCurrency()
    {
        Mail::fake();

        $merchant = $this->fixtures->merchant;
        $merchant->enableInternational();
        $merchant->edit('10000000000000', ['convert_currency' => '1']);

        $this->negativeRefundFixtures('balance', 50000, 0, 0);

        $payment = $this->defaultAuthPayment(['amount' => 200, 'currency' => 'USD']);

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 0]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertQueued(NegativeBalanceAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals('-20 INR', $viewData['balance']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_alert', $mail->view);

            return true;
        });
    }

    public function testRefundWithNegativeBalanceAndInternationalCurrency()
    {
        Mail::fake();

        $merchant = $this->fixtures->merchant;
        $merchant->enableInternational();
        $merchant->edit('10000000000000', ['convert_currency' => '1']);

        $this->negativeRefundFixtures('balance', 210000, 0, 0);

        //should not fail 2USD -> 142 INR
        $payment = $this->defaultAuthPayment(['amount' => 200, 'currency' => 'USD']);
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => -4800]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
    }

    public function testRefundWithNegativeAndReserveBalanceAndInternationalCurrency()
    {
        Mail::fake();

        $merchant = $this->fixtures->merchant;
        $merchant->enableInternational();
        $merchant->edit('10000000000000', ['convert_currency' => '1']);

        $this->negativeRefundFixtures('balance', 21000, 0, 1000);

        //should fail: 20USD -> 1426 INR
        $payment = $this->defaultAuthPayment(['amount' => 2000, 'currency' => 'USD']);
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => -4800]);

        $this->startTest($payment['id'], (string) $payment['amount']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
    }

    private function negativeRefundFixtures(string $refundSource,
                                            int $negativeLimit,
                                            int $refundCredits = 0,
                                            int $reserveAmount = 0)
    {
        $this->fixtures->base->editEntity('merchant', '10000000000000', ['refund_source' => $refundSource]);

        if($negativeLimit !== 0)
        {
            $this->fixtures->create('balance_config',
                [
                    'id'                            => '100yz000yz00yz',
                    'balance_id'                    => '10000000000000',
                    'type'                          => 'primary',
                    'negative_transaction_flows'   => ['refund'],
                    'negative_limit_auto'           => $negativeLimit,
                    'negative_limit_manual'         => $negativeLimit

                ]
            );
        }

        if($refundSource === 'credits')
        {
            $this->fixtures->create('credits',
                [
                    'type'  => 'refund',
                    'value' => $refundCredits,
                ]
            );
        }

        if($reserveAmount !== 0)
        {
            $this->fixtures->create('balance',
                [
                    'id'                            => '100xy000xy00xy',
                    'merchant_id'                   => '10000000000000',
                    'type'                          => 'reserve_primary',
                    'balance'                       => $reserveAmount
                ]
            );
        }
    }

}
