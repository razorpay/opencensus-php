<?php

namespace RZP\Tests\Functional\Payment;

use Mockery;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CardTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testFetchCardDetails()
    {
        $this->disbaleCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndGetPayment($payment);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'. $payment['id']. '/card';

        $this->ba->proxyAuth();

        $card = $this->startTest();

        $this->assertEquals(true, isset($card['issuer']));

        $this->assertEquals($card['id'], $payment['card_id']);
    }

    public function testFetchCardRecurring()
    {
        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        return $this->runRequestResponseFlow($testData);
    }

    public function testFetchCardRecurringForDebit()
    {
        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        return $this->runRequestResponseFlow($testData);
    }

    public function testFetchCardRecurringForDebitWithNullIssuer()
    {
        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        return $this->runRequestResponseFlow($testData);
    }

    public function testFetchCardRecurringForNonSupportedDebitBank()
    {
        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        return $this->runRequestResponseFlow($testData);
    }

    public function testUnsupportedCardNetworks()
    {
        $numbers = array(
            '3566002020360505',
            '6011111111111117',
//            '30569309025904',
//            '38520000023237',
//            '62304123456789018'
        );

        foreach ($numbers as $number)
        {
            $this->testData[__FUNCTION__]['request']['content']['card']['number'] = $number;
            if (substr($number, 0, 2) === '37')
                $this->testData[__FUNCTION__]['request']['content']['card']['cvv'] ='1111';
            else
                $this->testData[__FUNCTION__]['request']['content']['card']['cvv'] ='111';
            $this->startTest();
        }
    }

    public function testBlockedCard()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4532111111175901';

        $this->fixtures->create( 'iin', ['iin' => '453211', 'country' => 'US', 'enabled' => 0]);

        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals('PAYMENT_FAILED_DUE_TO_BLOCKED_CARD', $riskEntity['reason']);

        $this->assertEquals(-1, $riskEntity['risk_score']);
    }

    public function testSupportedCardNetworks()
    {
        $supportedCards = array(
            ['5546199799745013',        'MasterCard'],
            ['5555 5555 5555 4444',     'MasterCard'],
            ['4000401234561233',        'Visa'],
            ['42 4242 42 4242 4242',    'Visa'],
            ['6078020203525771',        'RuPay'],
            ['5021653933333338',        'Maestro']
        );

        foreach ($supportedCards as $cardData)
        {
            $number = $cardData[0];

            $cardInfo = [
                //'iin' => "554619",
                'last4' => substr($number, -4, 4),
                'network' => $cardData[1],
                // 'international' => null,
//                'type' => 'unknown',
            ];

            $payment = $this->getDefaultPaymentArray();
            $payment['card']['number'] = $cardData[0];

            $this->ba->publicAuth();
            $payment = $this->doAuthAndGetPayment($payment);

            $card = $this->getLastEntity('card', true);

            $this->assertArraySelectiveEquals($cardInfo, $card);
            $this->assertArrayNotHasKey('number', $card);
        }
    }

    public function testBlankExpiryForMaestro()
    {
        $maestroNumber = '5021653933333338';

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = $maestroNumber;
        $payment['card']['expiry_month'] = "";
        $payment['card']['expiry_year'] = "";
        $payment['card']['cvv'] = "";

        $payment = $this->doAuthAndGetPayment($payment);

        $cardInfo = [
            'iin' => "999999",
            'last4' => substr($maestroNumber, -4),
            'network' => 'Maestro',
        ];

        $card = $this->getLastEntity('card', true);

        $this->assertArraySelectiveEquals($cardInfo, $card);
        $this->assertArrayNotHasKey('number', $card);
    }

    public function testNoCvvFieldForMaestro()
    {
        $maestroNumber = '5021653933333338';

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = $maestroNumber;
        unset($payment['card']['expiry_month']);
        unset($payment['card']['expiry_year']);
        unset($payment['card']['cvv']);

        $payment = $this->doAuthAndGetPayment($payment);

        $cardInfo = [
            'iin' => "999999",
            'last4' => substr($maestroNumber, -4),
            'network' => 'Maestro',
        ];

        $card = $this->getLastEntity('card', true);

        $this->assertArraySelectiveEquals($cardInfo, $card);
        $this->assertArrayNotHasKey('number', $card);
    }

    public function testUpdateSavedCard()
    {
        // Create card with missing fields
        $card = $this->fixtures->create(
                'card',
                [
                    'iin'     => '453211',
                    'issuer'  => null,
                    'country' => null,
                    'network' => 'MasterCard',
                ]);

        // Create IIN with missing info relating to card
        $iin = $this->fixtures->create(
                'iin',
                [
                    'iin'     => '453211',
                    'issuer'  => 'ICIC',
                    'country' => 'IN',
                    'network' => 'Visa',
                ]);

        $amexCard = $this->fixtures->create(
                'card',
                [
                    'iin'     => '553212',
                    'country' => null,
                    'network' => 'American Express',
                ]);

        $amexIin = $this->fixtures->create(
                'iin',
                [
                    'iin'     => '553212',
                    'country' => 'IN',
                    'network' => 'American Express',
                ]);

        $this->ba->cronAuth();

        $this->startTest();

        $card = $this->getEntityById('card', $card->getId(), true);

        // Assert that card info has been populated
        $this->assertEquals($iin->getCountry(), $card['country']);
        $this->assertEquals($iin->getNetwork(), $card['network']);

        $amexCard = $this->getEntityById('card', $amexCard->getId(), true);

        // Assert that Amex country did not get updated
        $this->assertNull($amexCard['country']);
    }

    public function testCardWhenNotEnabledOnLive()
    {
        $this->fixtures->merchant->disableCard('10000000000000');
        $this->fixtures->merchant->activate('10000000000000');

        $this->ba->publicLiveAuth();

        $payment = $this->getDefaultPaymentArray();

        $testData['request']['content'] = $payment;

        $content = $this->startTest($testData);
    }

    public function testCreditCardNotEnabledOnLive()
    {
        $this->fixtures->merchant->disableCreditCard('10000000000000');
        $this->fixtures->merchant->enableDebitCard('10000000000000');
        $this->fixtures->merchant->activate('10000000000000');

        $this->ba->publicLiveAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4111111111111111';

        $testData['request']['content'] = $payment;

        $content = $this->startTest($testData);

        $this->fixtures->merchant->enableCreditCard('10000000000000');
    }

    public function testBinValidationWithFeature()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['bin_issuer_validator']);

        $this->fixtures->iin->edit('401200', ['issuer' => 'HDFC', 'type' => 'debit']);
        $response = parent::startTest();
        $this->assertEquals('HDFC', $response['issuer']);
        $this->assertEquals('debit', $response['type']);

        $this->fixtures->iin->edit('401200', ['issuer' => 'HDFC', 'type' => 'credit']);
        $response = parent::startTest();
        $this->assertEquals('HDFC', $response['issuer']);
        $this->assertEquals('credit', $response['type']);

        $this->fixtures->iin->edit('401200', ['issuer' => 'KKBK', 'type' => 'debit']);
        $response = parent::startTest();
        $this->assertEquals('Others', $response['issuer']);
        $this->assertEquals('debit', $response['type']);

        $this->fixtures->iin->edit('401200', ['issuer' => 'KKBK', 'type' => 'credit']);
        $response = parent::startTest();
        $this->assertEquals('Others', $response['issuer']);
        $this->assertEquals('credit', $response['type']);

        $this->fixtures->iin->edit('401200', ['issuer' => 'HDFC', 'type' => '']);
        $response = parent::startTest();
        $this->assertEquals('HDFC', $response['issuer']);
        $this->assertEquals('', $response['type']);

        $this->fixtures->iin->edit('401200', ['issuer' => 'KKBK', 'type' => '']);
        $response = parent::startTest();
        $this->assertEquals('Others', $response['issuer']);
        $this->assertEquals('', $response['type']);
    }

    public function testBinValidationWithOutFeature()
    {
        $this->ba->publicAuth();

        parent::startTest();
    }

    public function testBinValidation()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['bin_issuer_validator']);

        parent::startTest();
    }

    public function testFetchCardDetailsForRearchPayment()
    {
        $this->markTestSkipped();

        $this->enablePgRouterConfig();

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry)
            {
                return [
                    'body' => [
                        'data'=>[
                            'card' => [
                                'id'                => 'GrClIcbRtTUxxb',
                                'merchant_id'       => '10000000000000',
                                'name'              =>  '',
                                'network'           =>  'RuPay',
                                'expiry_month'      =>  '01',
                                'expiry_year'       =>  '2099',
                                'iin'               =>  '999999',
                                'last4'             =>  '1111',
                                'vault_token'       => 'NjA3Mzg0OTcwMDAwNDk0Nw==',
                                'vault'             => 'rzpvault',
                            ],
                        ]
                    ]
                ];
            });

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['url'] = "/admin/card/card_GrClIcbRtTUxxb";

        $card = $this->startTest();

        $this->assertEquals($card['id'], 'card_GrClIcbRtTUxxb');
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
