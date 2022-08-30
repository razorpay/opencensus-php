<?php

namespace RZP\Tests\Functional\Payment;

use EE;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentValidationTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CardValidationTestData.php';

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

    public function testMinAmountCheckNonInr()
    {
        $this->startTest();
    }

    public function testInvalidCardExpiryDate()
    {
        // if (date('n') === '1')
        $this->markTestSkipped();

        $this->startTest();
    }

    public function testCardNumberWithSpaces()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '40 1 2001 0384 43 33 5';

        $payment = $this->doAuthAndGetPayment($payment);
    }

    public function testCardCvvLengthNot3()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['cvv'] = '4111';

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testInvalidMethod()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'unselected';

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $payment = $this->doAuthPayment($payment);
        });
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

    public function testNotesArrayNotDictionary()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['notes'] = ['100304034'];

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

        $this->verifyPayment($payment['id']);
    }


    public function testInvalidCallbackUrl()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['callback_url'] = 'invalidUrl';
        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
                $this->doAuthPayment($payment);
        },
        \RZP\Exception\BadRequestValidationFailureException::class,
        'The callback url format is invalid.');
    }

    public function testInvalidCallbackUrlDomainCheck()
    {
        $this->fixtures->merchant->addFeatures(['callback_url_validation']);

        $payment = $this->getDefaultPaymentArray();
        $payment['callback_url'] = 'https://google.com';
        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
                $this->doAuthPayment($payment);
        },
        \RZP\Exception\BadRequestValidationFailureException::class,
        'Invalid callback url');
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData);
    }
}
