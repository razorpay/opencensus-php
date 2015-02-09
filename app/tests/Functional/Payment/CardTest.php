<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class CardTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CardTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testUnsupportedCardNetworks()
    {
        $numbers = array(
            '378282246310005',
            '3566002020360505',
            '6011111111111117',
            '30569309025904',
            '38520000023237',
            '62304123456789018');

        foreach ($numbers as $number)
        {
            $this->testData[__FUNCTION__]['request']['content']['card']['number'] = $number;
            $this->startTest();
        }
    }

    public function testSupportedCardNetworks()
    {
        $supportedCards = array(
            ['5546199799745013',        'MasterCard'],
            ['5555 5555 5555 4444',     'MasterCard'],
            ['4000401234561233',        'Visa'],
            ['42 4242 42 4242 4242',    'Visa'],
            ['6759649826438453',        'Maestro']
        );

        foreach ($supportedCards as $cardData)
        {
            $number = $cardData[0];

            $cardInfo = [
                'iin' => substr(str_replace(' ' , '', $number), 0, 6),
                'last4' => substr($number, -4, 4),
                'network' => $cardData[1],
                'international' => NULL,
                'type' => 'unknown',
            ];

            $payment = $this->getDefaultPaymentArray();
            $payment['card']['number'] = $cardData[0];

            $this->ba->publicAuth();
            $payment = $this->doAuthAndGetPayment($payment);

            $card = $this->getLastCard();

            $this->assertArraySelectiveEquals($cardInfo, $card);
            $this->assertArrayNotHasKey('number', $card);
        }
    }

    protected function getLastCard()
    {
        $this->ba->proxyAuth();

        $request = array(
            'method' => 'GET',
            'url' => '/cards?count=1');

        $content = $this->makeRequestAndGetContent($request);

        $this->assertSame('collection', $content['entity']);
        $this->assertSame(1, $content['count']);

        return $content['items'][0];
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceDefualtValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData);
    }
}
