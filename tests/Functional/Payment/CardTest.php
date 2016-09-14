<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CardTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testFetchCardDetails()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndGetPayment($payment);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'. $payment['id']. '/card';

        $this->ba->proxyAuth();

        $card = $this->startTest();

        $this->assertEquals($card['id'], $payment['card_id']);
    }

    public function testUnsupportedCardNetworks()
    {
        $numbers = array(
            '3566002020360505',
            '6011111111111117',
//            '30569309025904',
//            '38520000023237',
            '62304123456789018');

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

        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testSupportedCardNetworks()
    {
        $supportedCards = array(
            ['5546199799745013',        'MasterCard'],
            ['5555 5555 5555 4444',     'MasterCard'],
            ['4000401234561233',        'Visa'],
            ['42 4242 42 4242 4242',    'Visa'],
//            ['5021653933333338',        'Maestro'] // hdfc not giving error on maestro currently
        );

        foreach ($supportedCards as $cardData)
        {
            $number = $cardData[0];

            $cardInfo = [
                'iin' => substr(str_replace(' ' , '', $number), 0, 6),
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

    public function testCardWhenNotEnabledOnLive()
    {
        $this->fixtures->merchant->disableCard('10000000000000');
        $this->fixtures->merchant->activate('10000000000000');

        $this->ba->publicLiveAuth();

        $payment = $this->getDefaultPaymentArray();

        $testData['request']['content'] = $payment;

        $content = $this->startTest($testData);
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceDefualtValues($testData['request']['content']);

        return $this->runRequestResponseFlow($testData);
    }
}
