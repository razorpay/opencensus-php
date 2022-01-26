<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use RZP\Mail\Merchant\CreditsAdditionSuccess;
use RZP\Mail\Merchant\ReserveBalanceAdditionSuccess;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Traits\TestsWebhookEvents;

class SelfServeCreditVATest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    protected $user = NULL;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/SelfServeCreditsTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->initializeMerchantAndBankAccount();
    }

    //Tests for fund Addition via Bank Transfer

    public function initializeMerchantAndBankAccount()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.refund_credit.merchant_id', '10000000000000');

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id' => '10000000000001',
            'business_type' => 1
        ]);

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->create('bank_account', ['merchant_id' => '10000000000001','ifsc_code'  => 'UTIB0CCH274', "entity_id" => '10000000000001']);

        $this->user = $this->fixtures->user->createUserForMerchant('10000000000001');

    }

    public function testVACreation($creditType='refund_credit')
    {
        $user = $this->getDbLastEntity('user');

        $this->testData[__FUNCTION__]['request']['content']['type'] = $creditType;

        $this->ba->proxyAuth('rzp_test_10000000000001', $this->user->getId());

        $response = $this->startTest();

        return $response;
    }

    public function testValidateVACreation()
    {
        $response = $this->testVACreation();

        $payerBA = $this->getDbLastEntity('bank_account');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($response['id'], $merchantDetails->getFundAdditionVAIds()['refund_credit']);

        $this->assertEquals($response['allowed_payers'][0]['bank_account']['account_number'], $payerBA->getAccountNumber());

        $this->assertEquals($response['allowed_payers'][0]['bank_account']['ifsc'], $payerBA->getIfscCode());

    }

    public function testValidateVACreationForCreditWhenAlreadyPresent()
    {
        $va_creation_response = $this->testVACreation();

        $va_fetch_response = $this->testVACreation();

        $this->assertEquals($va_fetch_response['id'] , $va_creation_response['id']);

        $this->assertEquals($va_fetch_response['notes']['merchant_id'] , $va_creation_response['notes']['merchant_id']);

        $this->assertEquals($va_fetch_response['notes']['type'] , $va_creation_response['notes']['type']);

        $this->assertEquals($va_fetch_response['receivers'][0]['id'] , $va_creation_response['receivers'][0]['id']);

        $this->assertEquals($va_fetch_response['allowed_payers'][0]['bank_account']['account_number'] , $va_creation_response['allowed_payers'][0]['bank_account']['account_number']);

        $merchant = $this->getDbEntityById('merchant', '10000000000001');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($va_creation_response['id'], $merchantDetails->getFundAdditionVAIds()['refund_credit']);
    }

    public function testVAIdsCreationOfDifferentTypes()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.fee_credit.merchant_id', '10000000000000');

        $vaCreationRefund = $this->testVACreation();

        $vaCreationFee = $this->testVACreation('fee_credit');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($vaCreationRefund['id'], $merchantDetails->getFundAdditionVAIds()['refund_credit']);

        $this->assertEquals($vaCreationFee['id'], $merchantDetails->getFundAdditionVAIds()['fee_credit']);

        $this->assertEquals('10000000000001' , $vaCreationRefund['notes']['merchant_id']);

        $this->assertEquals('refund_credit' , $vaCreationRefund['notes']['type']);

        $this->assertEquals('10000000000001' , $vaCreationFee['notes']['merchant_id']);

        $this->assertEquals('fee_credit' , $vaCreationFee['notes']['type']);
    }

    public function testFundAdditionWebhookWithFundAdditionInvalidType()
    {

        $this->ba->directAuth();

        $this->fundAdditionToVirtualAccount();

        $bankTransfer = $this->getDbLastEntity('bank_transfer');

        $this->testData[__FUNCTION__]['request']['content']['payload']['bank_transfer']['entity']['id'] = $bankTransfer->getPublicId();

        $this->startTest();
    }

    public function fundAdditionToVirtualAccount($type = 'refund_credit')
    {

        $response = $this->testVACreation($type);

        $bankAccount = $response['receivers'][0];

        $allowedPayer = $response['allowed_payers'][0];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $bankAccount['account_number'];

        $this->testData[__FUNCTION__]['request']['content']['payee_ifsc']    = $bankAccount['ifsc'];

        $this->testData[__FUNCTION__]['request']['content']['payer_account'] = $allowedPayer['bank_account']['account_number'];

        $this->testData[__FUNCTION__]['request']['content']['payer_ifsc']    = $allowedPayer['bank_account']['ifsc'];

        $this->startTest();
    }

    public function testFundAdditionViaWebhook()
    {
        Mail::fake();

        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event)
            {
                $this->assertEquals('refund_credit', $event['payload']['virtual_account']['entity']['notes']['type'] );
                $this->assertEquals('10000000000001', $event['payload']['virtual_account']['entity']['notes']['merchant_id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['payment_id'], $event['payload']['payment']['entity']['id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['virtual_account_id'], $event['payload']['virtual_account']['entity']['id'] );
                $this->testData['addFundsViaWebhook']['request']['content'] = $event;
            }
        );
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount();

        $response = $this->startTest($this->testData['addFundsViaWebhook']);

        $credit = $this->getDbLastEntity('credits');

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($payment->getPublicId(), $credit->getCampaign());

        $this->assertEquals('10000000000001', $credit->getMerchantId());

        $this->assertEquals($payment->getAmount() - $payment->getFee(), $credit->getValue());

        $balance = $this->getDbEntityById('balance', '100def000def00');

        $this->assertEquals($payment->getAmount() - $payment->getFee(), $balance->getRefundCredits());

        Mail::assertQueued(CreditsAdditionSuccess::class, function ($mail)
        {
            $this->assertEquals("10000000000001", $mail->viewData['merchant_id']);
            $this->assertEquals("Refund Credit",  $mail->viewData['account_type']);
            return true;
        });
    }

    public function testFundAdditionViaWebhookWithInvalidPaymentId()
    {
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount();

        $bankTransfer = $this->getDbLastEntity('bank_transfer');

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['notes']['merchant_id'] = '10000000000001';

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['notes']['type'] = 'refund_credit';

        $this->testData[__FUNCTION__]['request']['content']['payload']['bank_transfer']['entity']['id'] = $bankTransfer->getPublicId();

        $this->startTest();
    }

    public function testFundAdditionViaWebhookWithInvalidVAId()
    {
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount();

        $payment = $this->getDbLastEntity('payment');

        $bankTransfer = $this->getDbLastEntity('bank_transfer');

        $virtualAccount = $this->getDbLastEntity('virtual_account');

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['notes']['merchant_id'] = '10000000000001';

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['notes']['type'] = 'refund_credit';

        $this->testData[__FUNCTION__]['request']['content']['payload']['bank_transfer']['entity']['id'] = $bankTransfer->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['id'] = $virtualAccount->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['fee'] = 0;

        $this->startTest();
    }

    public function testValidateVACreationForReserveBalance()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $response = $this->testVACreation('reserve_balance');

        $payerBA = $this->getDbLastEntity('bank_account');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($response['id'], $merchantDetails->getFundAdditionVAIds()['reserve_balance']);

        $this->assertEquals($response['allowed_payers'][0]['bank_account']['account_number'], $payerBA->getAccountNumber());

        $this->assertEquals($response['allowed_payers'][0]['bank_account']['ifsc'], $payerBA->getIfscCode());
    }

    public function testFundAdditionViaWebhookForReserveBalance()
    {
        Mail::fake();

        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event)
            {
                $this->assertEquals('reserve_balance', $event['payload']['virtual_account']['entity']['notes']['type'] );
                $this->assertEquals('10000000000001', $event['payload']['virtual_account']['entity']['notes']['merchant_id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['payment_id'], $event['payload']['payment']['entity']['id'] );
                $this->assertEquals($event['payload']['bank_transfer']['entity']['virtual_account_id'], $event['payload']['virtual_account']['entity']['id'] );
                $this->testData['addFundsViaWebhook']['request']['content'] = $event;
            }
        );
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount('reserve_balance');

        $this->startTest($this->testData['addFundsViaWebhook']);

        $reserveBalance = $this->getDbLastEntity('balance');

        $payment = $this->getDbLastEntity('payment');

        $adjustment = $this->getDbLastEntity('adjustment');

        $this->assertEquals('10000000000001', $adjustment->getMerchantId());

        $this->assertEquals($payment->getAmount() - $payment->getFee(), $reserveBalance->getBalance());

        Mail::assertQueued(ReserveBalanceAdditionSuccess::class, function ($mail)
        {
            $this->assertEquals("10000000000001", $mail->viewData['merchant_id']);
            $this->assertEquals("Reserve Balance",  $mail->viewData['account_type']);
            return true;
        });
    }

    public function testValidateVACreationForCreditWhenAlreadyPresentForReserveBalance()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.reserve_balance.merchant_id', '10000000000000');

        $va_creation_response = $this->testVACreation('reserve_balance');

        $va_fetch_response = $this->testVACreation('reserve_balance');

        $this->assertEquals($va_fetch_response['id'] , $va_creation_response['id']);

        $this->assertEquals($va_fetch_response['notes']['merchant_id'] , $va_creation_response['notes']['merchant_id']);

        $this->assertEquals($va_fetch_response['notes']['type'] , $va_creation_response['notes']['type']);

        $this->assertEquals($va_fetch_response['receivers'][0]['id'] , $va_creation_response['receivers'][0]['id']);

        $this->assertEquals($va_fetch_response['allowed_payers'][0]['bank_account']['account_number'] , $va_creation_response['allowed_payers'][0]['bank_account']['account_number']);

        $merchant = $this->getDbEntityById('merchant', '10000000000001');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($va_creation_response['id'], $merchantDetails->getFundAdditionVAIds()['reserve_balance']);
    }

    public function virtualAccountUpdation()
    {
        $this->app['config']->set('banking_account.razorpay_fund_addition_accounts.fee_credit.merchant_id', '10000000000000');

        $vaCreationRefund = $this->testVACreation();

        $vaCreationFee = $this->testVACreation('fee_credit');

        $merchantDetails = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertEquals($vaCreationRefund['id'], $merchantDetails->getFundAdditionVAIds()['refund_credit']);

        $this->assertEquals($vaCreationFee['id'], $merchantDetails->getFundAdditionVAIds()['fee_credit']);
    }

    public function testVACreationIfBankAccountDoesNotExist()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000002']);

        $this->fixtures->edit('merchant_detail','10000000000001', [
            'merchant_id' => '10000000000002',
            'business_type' => 1
        ]);

        $user = $this->fixtures->user->createUserForMerchant('10000000000002');

        $this->testData[__FUNCTION__]['request']['content']['type'] = 'refund_credit';

        $this->ba->proxyAuth('rzp_test_10000000000002', $user->getId());

        $response = $this->startTest();
    }
    public function testFundAdditionViaWebhookWithNotesNotPresent()
    {
        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000001'
            ]
        );
        $this->fundAdditionToVirtualAccount();

        $payment = $this->getDbLastEntity('payment');

        $bankTransfer = $this->getDbLastEntity('bank_transfer');

        $virtualAccount = $this->getDbLastEntity('virtual_account');

        $this->testData[__FUNCTION__]['request']['content']['payload']['bank_transfer']['entity']['id'] = $bankTransfer->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['id'] = $payment->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['virtual_account']['entity']['id'] = $virtualAccount->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['payload']['payment']['entity']['fee'] = 0;

        $this->startTest();
    }

}
