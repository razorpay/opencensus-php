<?php

namespace RZP\Tests\Functional\Transfer;

use Mail;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Merchant\BalancePositiveAlert;
use RZP\Mail\Merchant\NegativeBalanceAlert;
use RZP\Mail\Merchant\NegativeBalanceThresholdAlert;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class TransferWithNegativeBalanceTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';

    /**
     * @var string
     */
    protected $linkedAccountId;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/TransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $this->linkedAccountId = $account['id'];
    }

    // Negative Balance Tests
    public function testTransferInsufficientBalanceWithNegativeBalance()
    {
        Mail::fake();

        $this->fixtures->merchant->editBalance(-400000);

        $this->fixtures->create('balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '10000000000000',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['transfer'],
                'negative_limit_auto'           => 500000,
                'negative_limit_manual'         => 500000
            ]
        );
        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $payment = $this->doAuthAndCapturePayment();

        $transfers[0] = [
            'account'  => 'acc_' . $account->getId(),
            'amount'   => 50000,
            'currency' => 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals(80, $viewData['percentage']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_threshold_alert', $mail->view);

            return true;
        });
    }

    public function testTransferInsufficientBalanceWithNegativeBalanceCrossingThreshold()
    {
        Mail::fake();

        $this->fixtures->merchant->editBalance(-400000);

        $this->fixtures->create('balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '10000000000000',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['transfer'],
                'negative_limit_auto'           => 500000,
                'negative_limit_manual'         => 500000
            ]
        );
        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $payment = $this->doAuthAndCapturePayment();

        $transfers[0] = [
            'account'  => 'acc_' . $account->getId(),
            'amount'   => 50000,
            'currency' => 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals(80, $viewData['percentage']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_threshold_alert', $mail->view);

            return true;
        });
    }

    public function testTransferInsufficientBalanceWithReserveBalance()
    {
        Mail::fake();

        $this->fixtures->merchant->editBalance(100);

        $this->fixtures->create('balance',
            [
                'type'                  => 'reserve_primary',
                'merchant_id'           => '10000000000000',
                'balance'               => 50000
            ]
        );

        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $payment = $this->doAuthAndCapturePayment();

        $transfers[0] = [
            'account'  => 'acc_' . $account->getId(),
            'amount'   => 1000,
            'currency' => 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testTransferInsufficientBalanceWithNegativeAndReserveBalance()
    {
        Mail::fake();

        $this->fixtures->merchant->editBalance(100);

        $this->fixtures->create('balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '10000000000000',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['transfer'],
                'negative_limit_auto'           => 500000,
                'negative_limit_manual'         => 0

            ]
        );

        $this->fixtures->create('balance',
            [
                'type'                  => 'reserve_primary',
                'merchant_id'           => '10000000000000',
                'balance'               => 50000
            ]
        );

        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $payment = $this->doAuthAndCapturePayment();

        $transfers[0] = [
            'account'  => 'acc_' . $account->getId(),
            'amount'   => 1000,
            'currency' => 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testTransferWithFeeInsufficientBalanceWithNegativeBalance()
    {
        Mail::fake();

        $this->fixtures->create('balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '10000000000000',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['transfer'],
                'negative_limit_auto'           => 500000,
                'negative_limit_manual'         => 500000

            ]
        );

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->merchant->editBalance(1000);

        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $payment = $this->doAuthAndCapturePayment();

        $transfers[0] = [
            'account'  => 'acc_' . $account->getId(),
            'amount'   => 1000,
            'currency' => 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testTransferWithFeeInsufficientBalanceWithReserveBalance()
    {
        Mail::fake();

        $this->fixtures->create('balance',
            [
                'type'                  => 'reserve_primary',
                'merchant_id'           => '10000000000000',
                'balance'               => 50000
            ]
        );

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->merchant->editBalance(1000);

        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $payment = $this->doAuthAndCapturePayment();

        $transfers[0] = [
            'account'  => 'acc_' . $account->getId(),
            'amount'   => 1000,
            'currency' => 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testTransferWithFeeInsufficientBalanceWithNegativeAndReserveBalance()
    {
        Mail::fake();

        $this->fixtures->create('balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '10000000000000',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['transfer'],
                'negative_limit_auto'           => 0,
                'negative_limit_manual'         => 0

            ]
        );

        $this->fixtures->create('balance',
            [
                'type'                  => 'reserve_primary',
                'merchant_id'           => '10000000000000',
                'balance'               => 50000
            ]
        );

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->merchant->editBalance(1000);

        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $payment = $this->doAuthAndCapturePayment();

        $transfers[0] = [
            'account'  => 'acc_' . $account->getId(),
            'amount'   => 1000,
            'currency' => 'INR',
        ];

        $this->transferPayment($payment['id'], $transfers);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }
}
