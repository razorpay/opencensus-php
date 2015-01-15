<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;

/**
 * Tests that support payments (capture/refund) are working fine.
 * creates a hold payment using card 13 and then attempts to capture it followed by refund it
 * Is successful if captured successfully folowed by successful refund.
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class SupportTest extends TestCase
{
    use PaymentAuthFlowTrait;

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Tests the support payments, calls capture & refund
     * @group testSupport
     * @group testCapture
     * @group testRefund
     */
    public function testSupport()
    {
        //
        // GIVEN
        // create an auth payment using card 1
        //
        $this->ba->publicAuth();
        $testData = array(
            'request' => [
                'content' => [
                    'card' => [
                        'number' => '4012001038443335',
                    ],
                ],
            ],
            'response' => [
                'content' => [],
            ]
        );

        $this->replaceDefualtValues($testData['request']['content']);

        $payment = $this->runRequestResponseFlow($testData);

        // get its payment id
        $this->assertArrayHasKey('id', $payment);
        $id = $payment['id'];

        // get amount
        $amount = '50000';

        $this->ba->privateAuth();
        $this->capture($id, $amount);

        $this->refund($id);
    }

    /**
     * Tests capture payments, attempts to capture all past payments & ensures that the payment specified by id is captured.
     */
    private function capture($id, $amount)
    {
        // WHEN
        // call for capture of payments
        $response = $this->call('POST', '/v1/payments/'.$id.'/capture', array('amount'=>$amount), array(), $this->ba->getCreds());
        $content = $response->getContent();

        // THEN
        // ensure output is json
        $this->assertJson($content);
        $capture = json_decode($content, true);

        // Check if payment id matches, and captured sucessfully
        $this->assertArrayHasKey('id', $capture);
        $this->assertEquals($id, $capture['id']);
        $this->assertArrayHasKey('status', $capture);
        $this->assertEquals('captured', $capture['status']);
    }

    /**
     * Tests refund payments, attempts to refund payment specified by the id & ensures that it is refunded.
     */
    private function refund($id)
    {
        //WHEN
        //call for refund of payments
        $response = $this->call('POST', '/v1/payments/'.$id.'/refund',  array(), array(), $this->ba->getCreds());
        $content = $response->getContent();

        //THEN
        //ensure output is json
        $this->assertJson($content);
        $refund = json_decode($content, true);

        $this->assertEquals('refund', $refund['entity']);
    }
}