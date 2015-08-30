<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;
use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

/**
 * Tests that retreieving of payments is working fine.
 * creates a payment using testdummy & attempts to retrieve it
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class PaymentRetrieveTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->ba->privateAuth();

        $payment = $this->fixtures->create('payment:captured');

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
        $content = $this->makeRequestAndGetContent($this->request);
    }

    /**
     * @group testRetrievePaymentWithId
     */
    public function testRetrievePaymentWithId()
    {
        $payments = $this->retrievePaymentsDefault();

        //GIVEN
        $id = $payments['items'][0]['id'];

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
        $status = $payments['items'][0]['status'];
        $id = $payments['items'][0]['id'];

        $request = $this->request;
        $request['content'] = array('count' => 1);
        //WHEN
        $payment = $this->makeRequestAndGetContent($request);

        $this->assertEquals($id, $payment['items'][0]['id']);
    }

    /**
     * @group testRetrievePaymentWithCreateAt
     */
    public function testRetrievePaymentsWithCreatedAt()
    {
        $this->markTestSkipped();

        $payments = $this->retrievePaymentsDefault();
        $id = $payments['items'][0]['id'];

        //GIVEN
        $created_at = $payments['items'][0]['created_at'];

        //WHEN
        $response = $this->call('GET', "/v1/payments/?created=".$created_at, array(), array(), $this->ba->getCreds());
        $content = $response->getContent();

        //THEN
        $this->assertJson($content);
        $payment = json_decode($content, true);

        $this->assertEquals($id, $payment['items'][0]['id']);
    }

    public function testFetchAuthorizedPaymentsOnAppAuth()
    {
        $this->fixtures->create('payment:authorized');

        $this->ba->appAuth();

        $payments = $this->getEntities('payment', ['status' => 'authorized']);

        $this->assertEquals($payments['count'], 1);
        $this->assertEquals($payments['items'][0]['status'], 'authorized');
    }

    public function testFetchAuthorizedPaymentsOnPrivateAuth()
    {
        $this->fixtures->create('payment:authorized');

        $this->ba->privateAuth();

        $testData = array(
            'request' => [
                'url' => '/payments',
                'method' => 'get',
                'content' => ['status' => 'authorized'],
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => 'EE\Exception\ExtraFieldsException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
            ],
        );

        $this->startTest($testData);
    }

    public function testMoreThan100InPrivateAuth()
    {
        $e = null;

        try
        {
            $content = $this->getEntities('payment', ['count' => 1000]);
        }
        catch (\Exception $e)
        {
            ;
        }

        $this->assertEquals('EE\Exception\BadRequestValidationFailureException', get_class($e));
    }
}
