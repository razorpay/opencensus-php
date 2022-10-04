<?php

namespace RZP\Tests\Functional\CustomerToken;

use RZP\Models\Card\Network;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class DualVaultSupportTest extends TestCase
{
    use PaymentTrait;
    use TerminalTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TokenTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['network_tokenization', 'allow_network_tokens']);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testCreationOfLocalRazorpayTokensAndPaymentThroughLocalTokenWhenNewCardPayment()
    {
        $this->mockSession();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->fixtures->merchant->addFeatures(['network_tokenization_paid']);

        $payment = $this->getDefaultPaymentArray();
        $payment['_']['library'] = 'razorpayjs';
        $payment['save'] = 1;

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $token = $payment->localToken;
        $paymentCard = $payment->card;
        $tokenCard = $token->card;
        $tokenCustomer = $token->customer;

        $this->assertNotNull($payment['token_id']);
        $this->assertEquals('401200', $tokenCard['iin']);
        $this->assertEquals($token['card_id'], $tokenCard['id']);
        $this->assertEquals('10000000000000', $token['merchant_id']);
        $this->assertEquals('100000Razorpay', $tokenCustomer['merchant_id']);
        $this->assertEquals('rzpvault', $tokenCard['vault']);
        $this->assertEquals('credit', $tokenCard['type']);
        $this->assertNull($paymentCard['trivia']);
    }

    public function testCreationOfLocalNetworkTokensAndPaymentThroughLocalTokenWhenNewCardPaymentGivenMerchantIsOnboardedOntoNetwork()
    {
        $this->mockSession();

        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid']);
        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid'], '100000Razorpay');

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $payment = $this->getDefaultPaymentArray();
        $payment['_']['library'] = 'razorpayjs';
        $payment['save'] = 1;

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $token = $payment->localToken;
        $paymentCard = $payment->card;
        $tokenCard = $token->card;
        $tokenCustomer = $token->customer;

        $this->assertNotNull($payment['token_id']);
        $this->assertEquals('401200', $tokenCard['iin']);
        $this->assertEquals($token['card_id'], $tokenCard['id']);
        $this->assertEquals('10000000000000', $token['merchant_id']);
        $this->assertEquals('100000Razorpay', $tokenCustomer['merchant_id']);
        $this->assertEquals('visa', $tokenCard['vault']);
        $this->assertEquals('credit', $tokenCard['type']);
        $this->assertNull($paymentCard['trivia']);
    }

    public function testCreationOfLocalTokenAndPaymentThroughLocalTokenWhenSavedActualCardPayment()
    {
        $this->mockSession();

        $this->mockCardVaultWithCryptogram(null, true);

        $payment = $this->getDefaultPaymentArray();

        $this->fixturesToCreateToken();

        $payment[Payment::CARD] = array('cvv'  => '111');

        $payment[Payment::TOKEN] = 'token_10002gcustcard';

        $payment['user_consent_for_tokenisation'] = 1;

        $payment['_']['library'] = 'checkoutjs';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $token = $payment->localToken;
        $paymentCard = $payment->card;
        $tokenCard = $token->card;
        $tokenCustomer = $token->customer;

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('rzpvault', $tokenCard['vault']);
        $this->assertEquals('10000000000000', $token['merchant_id']);
        $this->assertEquals('100000Razorpay', $tokenCustomer['merchant_id']);
        $this->assertNull($paymentCard['trivia']);
    }

    public function testCreationOfLocalAndPaymentThroughLocalTokenWhenSavedTokenisedCardPayment()
    {
        $this->mockSession();

        $this->mockCardVaultWithCryptogram(null, true);

        $payment = $this->getDefaultPaymentArray();

        $this->fixturesToCreateToken('visa');

        $payment[Payment::CARD] = array('cvv'  => '111');

        $payment[Payment::TOKEN] = 'token_10002gcustcard';

        $payment['user_consent_for_tokenisation'] = 1;

        $payment['_']['library'] = 'checkoutjs';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $token = $payment->localToken;
        $paymentCard = $payment->card;
        $tokenCard = $token->card;
        $tokenCustomer = $token->customer;

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('rzpvault', $tokenCard['vault']);
        $this->assertEquals('10000000000000', $token['merchant_id']);
        $this->assertEquals('100000Razorpay', $tokenCustomer['merchant_id']);
        $this->assertNull($paymentCard['trivia']);
    }

    public function testPaymentThroughGlobalTokenWhenSavedCardAndConsentIsNotGiven()
    {
        $this->mockSession();

        $this->mockCardVaultWithCryptogram(null, true);

        $payment = $this->getDefaultPaymentArray();

        $this->fixturesToCreateToken();

        $payment[Payment::CARD] = array('cvv'  => '111');

        $payment[Payment::TOKEN] = 'token_10002gcustcard';

        $payment['_']['library'] = 'checkoutjs';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $token = $payment->globalToken;
        $paymentCard = $payment->card;
        $tokenCard = $token->card;
        $tokenCustomer = $token->customer;

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('rzpvault', $tokenCard['vault']);
        $this->assertEquals('100000Razorpay', $token['merchant_id']);
        $this->assertEquals('100000Razorpay', $tokenCustomer['merchant_id']);
        $this->assertNull($paymentCard['trivia']);
    }

    public function testLocalTokenDedupeWhenSavedCardSecondPayment()
    {
        $this->markTestSkipped("Current dedupe logic will not work, respective team will pick this up");

        $this->mockSession();

        $this->mockCardVaultWithCryptogram(null, true);

        $payment = $this->getDefaultPaymentArray();

        $this->fixturesToCreateToken();

        $payment[Payment::CARD] = array('cvv'  => '111');

        $payment[Payment::TOKEN] = 'token_10002gcustcard';

        $payment['user_consent_for_tokenisation'] = 1;

        $payment['_']['library'] = 'checkoutjs';

        $response1 = $this->doAuthPayment($payment);

        $payment1 = $this->getDbEntityById('payment', $response1['razorpay_payment_id']);

        $token1 = $payment1->localToken;

        $response2 = $this->doAuthPayment($payment);

        $payment2 = $this->getDbEntityById('payment', $response2['razorpay_payment_id']);

        $token2 = $payment2->localToken;

        $this->assertEquals($token1['id'], $token2['id']);
    }

    public function testGlobalAndLocalTokenDedupeLogicWhenNewCardSecondPayment()
    {
        $this->markTestSkipped("Current dedupe logic will not work, respective team will pick this up");

        $this->mockSession();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->fixtures->merchant->addFeatures(['network_tokenization_paid']);

        $payment = $this->getDefaultPaymentArray();
        $payment['_']['library'] = 'razorpayjs';
        $payment['save'] = 1;

        $response1 = $this->doAuthPayment($payment);

        $payment1 = $this->getDbEntityById('payment', $response1['razorpay_payment_id']);

        $token1 = $payment1->localToken;

        $tokensBeforeSecondPayment = $this->getEntities('token', [
            'customer_id' => '10000gcustomer'
        ], true);

        $response2 = $this->doAuthPayment($payment);

        $tokensAfterSecondPayment = $this->getEntities('token', [
            'customer_id' => '10000gcustomer'
        ], true);

        $payment2 = $this->getDbEntityById('payment', $response2['razorpay_payment_id']);

        $token2 = $payment2->localToken;

        $this->assertEquals($token1['id'], $token2['id']);
        $this->assertEquals($tokensBeforeSecondPayment['count'], $tokensAfterSecondPayment['count']);
    }

    protected function fixturesToCreateToken($vault = 'rzpvault')
    {
        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '100000Razorpay',
                'name'              =>  'test',
                'iin'               =>  '401200',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '3335',
                'type'              =>  'debit',
                'vault'             =>  $vault,
                'vault_token'       =>  'NDAxMjAwMTAzODQ0MzMzNQ==',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '10002gcustcard',
                'token'         => '10003cardToken',
                'customer_id'   => '10000gcustomer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '100000Razorpay',
            ]
        );
    }

    protected function extractTokenIds($tokens): array
    {
        $tokenIds = [];

        foreach ($tokens as $token) {
            $tokenIds[] = $token['id'];
        }

        return $tokenIds;
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = ['test_app_token' => $appToken];

        $this->session($data);
    }
}
