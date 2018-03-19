<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

/**
 * Tests for acknowledging payments.
 */

class AcknowledgeTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AcknowledgeTestData.php';

        parent::setUp();
    }

    /**
     * Tests acknowledging a captured payment.
     * Also, catches an exception while acknowledging an already acknowledged payment.
     */
    public function testAcknowledgeCapturedPayment()
    {
        $payment = $this->fixtures->create('payment:captured');

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        $payment = $payment->toArrayPublic();

        $testData['request']['url'] = '/payments/' . $payment['id'] . '/acknowledge';

        $response = $this->sendRequest($testData['request']);

        $response->assertStatus(204);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull( $payment['acknowledged_at']);

        $this->ba->privateAuth();

        $this->startTest($testData);
    }

    /**
     * Tests acknowledging an authorized payment.
     */
    public function testAcknowledgeAuthorizedPayment()
    {
        $payment = $this->fixtures->create('payment:authorized');

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        $payment = $payment->toArrayPublic();

        $testData['request']['url'] = '/payments/' . $payment['id'] . '/acknowledge';

        $response = $this->sendRequest($testData['request']);

        $response->assertStatus(204);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull( $payment['acknowledged_at']);
    }
}