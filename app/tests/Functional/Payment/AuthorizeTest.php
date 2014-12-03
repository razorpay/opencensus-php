<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;

/**
 * Tests that support payments (capture/refund) are working fine.
 * creates a hold payment using card 13 and then attempts to capture it followed by refund it
 * Is successful if captured successfully folowed by successful refund.
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class AuthorizeTest extends TestCase
{
    use PaymentAuthFlowTrait;

    protected $testData = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/authorize.php';

        parent::setUp();

        $this->setupPublicBasicAuthParams();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testInvalidEmailInPayment()
    {
        $this->startTest();
    }

    public function testJsonpPayment()
    {
        $content = $this->startTest();
    }

    public function testEmailMissing()
    {
        unset($this->payment['email']);
        $this->startTest();
    }

    public function testContactTooShort()
    {
        $this->startTest();
    }

    public function testContactTooLong()
    {
        $this->startTest();
    }

    public function testContactWithDashAndBracket()
    {
        $this->startTest();
    }

    public function testContactWithPlusAndNumbers()
    {
        $this->startTest();
    }

    public function testNonInrCurrency()
    {
        $this->startTest();
    }

    public function testCardMissing()
    {
        unset($this->payment['card']);

        $this->startTest();
    }

    public function testPaymentCardAsString()
    {
        $this->startTest();
    }

    public function testAmountBelowMin()
    {
        $this->startTest();
    }

    public function testAmountVeryHigh()
    {
        $this->startTest();
    }

    public function testAmountLessThan50ForNetBanking()
    {
        $this->fixtures->createTerminalEntityForAtomGateway();
        $this->startTest();
    }

    public function testAmountNonNumeric()
    {
        $this->startTest();
    }

    public function testAmountMissing()
    {
        $this->startTest();
    }

    public function testDescriptionMissing()
    {
        unset($this->payment['description']);

        $this->startTest();
    }

    public function testDescriptionAsArray()
    {
        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['description']['key'] = 'value';

        $this->startTest();
    }

    public function testDescriptionTooLarge()
    {
        $testData = & $this->testData[__FUNCTION__];

        $largeText = implode(',', range(1,1000,1));

        $testData['request']['content']['description'] = $largeText;

        $this->startTest();
    }

    public function testUdfMissing()
    {
        unset($this->payment['udf']);

        $this->startTest();
    }

    public function testUdfStringNotArray()
    {
        $this->startTest();
    }

    public function testExcessValuesInUdf()
    {
        $testData = & $this->testData[__FUNCTION__];

        foreach (range(1, 16, 1) as $i)
        {
            $testData['request']['content']['udf'][$i] = 'value';
        }

        $this->startTest();
    }

    public function testArrayInUdfValue()
    {
        $this->startTest();
    }

    public function testArrayInUdfKey()
    {
        $this->startTest();
    }

    public function testUdfKeyLarge()
    {
        $testData = & $this->testData[__FUNCTION__];

        $largeKey = implode(',', range(1,100,1));
        $testData['request']['content']['udf'][$largeKey] = 'value';

        $this->startTest();
    }

    public function testUdfValueLarge()
    {
        $testData = & $this->testData[__FUNCTION__];

        $largeValue = implode(',', range(1,100,1));
        $testData['request']['content']['udf']['key'] = $largeValue;

        $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceValuesRecursively($this->payment, $testData['request']['content']);

        $testData['request']['content'] = $this->payment;

        return $this->runRequestResponseFlow($testData);
    }
}