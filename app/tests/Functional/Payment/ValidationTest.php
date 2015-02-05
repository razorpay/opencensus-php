<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Payment\PaymentAuthFlowTrait;

class PaymentValidationTest extends TestCase
{
    use PaymentAuthFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/cardNumbers.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testShortCardNumber()
    {
        $this->startTest();
    }

    public function testNonNumericCardNumber()
    {
        $this->startTest();
    }

    public function testLongCardNumber()
    {
        $this->startTest();
    }

    public function testNonLuhnCardNumber()
    {
        $this->startTest();
    }

    public function testInvalidCardExpiryMonth()
    {
        $this->startTest();
    }

    public function testInvalidCardExpiryYear()
    {
        $this->startTest();
    }

    public function testInvalidCardExpiryDate()
    {
        if (date('n') === '1')
            $this->markTestSkipped();

        $this->startTest();
    }

    public function testCardNumberWithSpaces()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '40 1 2001 0384 43 33 5';

        $payment = $this->doAuthAndGetPayment($payment);
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

    public function testDescriptionMissing()
    {
        $payment = $this->getDefaultPaymentArray();

        unset($payment['description']);

        $payment = $this->doAuthAndGetPayment($payment);
    }

    public function testNotesMissing()
    {
        $payment = $this->getDefaultPaymentArray();

        unset($payment['notes']);

        $payment = $this->doAuthAndGetPayment($payment);
    }

    public function testContactWithDashAndBracket()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['contact'] = '+1234-(456)-(789)';

        $response['contact'] = '+1234456789';

        $payment = $this->doAuthAndGetPayment($payment, $response);
    }

    public function testPaymentInquiry()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndGetPayment($payment);

        $testData = & $this->testData[__FUNCTION__];

        $this->ba->proxyAuth();

        $request = ['method' => 'GET', 'url' => '/payments/'.$payment['id'].'/verify'];
        $content = $this->makeRequestAndGetContent($request);
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
