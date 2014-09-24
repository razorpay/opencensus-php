<?php

namespace Tests\Functional\Payment;

use Laracasts\TestDummy\Factory;
use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

/**
 * Tests that retreieving of payments is working fine.
 * creates a payment using testdummy & attempts to retrieve it
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class PaymentRetrieveTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        parent::setUp();

        $payment = $this->createEntity('payment', ['merchant_id' => '363e4efa820b0c06208ccd99']);

        $this->setupPrivateBasicAuthParams();

        $this->request = array(
            'method' => 'GET',
            'url' => '/payments');
    }

    protected function retrievePaymentsDefault()
    {
        return $this->makeRequestAndGetContent($this->request);
    }

    /**
    * Tests the /payments & /payments/$id route.
    * Should return valid json with list of all payments in case 1
    * Should return valid json with details of payment specified by id in case 2
    *
    * @group testRetrievePayment
    */
    public function testRetrievePayment()
    {
        //GIVEN - Nothing
        //WHEN
        $content = $this->makeRequestAndGetContent($this->request);
    }

    /**
     * @group testRetrievePaymentWithId
     */
    public function testRetrievePaymentWithId()
    {
        $payments = $this->retrievePaymentsDefault();

        //GIVEN
        $id = $payments['data'][0]['id'];

        //WHEN
        $request = $this->request;
        $request['url'] .= '/'.$id;

        $payment = $this->makeRequestAndGetContent($request);

        $this->assertEquals($id, $payment['id']);
    }

    public function testRetrievePaymentWithCount()
    {
        $payments = $this->retrievePaymentsDefault();

        //GIVEN
        $status = $payments['data'][0]['status'];
        $id = $payments['data'][0]['id'];

        $request = $this->request;
        $request['content'] = array('count' => 1);
        //WHEN
        $payment = $this->makeRequestAndGetContent($request);

        $this->assertEquals($id, $payment['data'][0]['id']);
    }

    /**
     * @group testRetrievePaymentWithCreateAt
     */
    public function testRetrievePaymentsWithCreatedAt()
    {
        $payments = $this->retrievePaymentsDefault();
        $id = $payments['data'][0]['id'];

        //GIVEN
        $created_at = $payments['data'][0]['created_at'];

        //WHEN
        $response = $this->call('GET', "/v1/payments/?created=".$created_at, array(), array(), $this->auth);
        $content = $response->getContent();

        //THEN
        $this->assertJson($content);
        $payment = json_decode($content, true);

        $this->assertEquals($id, $payment['data'][0]['id']);
    }
}
