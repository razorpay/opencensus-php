<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use Mockery;
use RZP\Models\Payment;

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
        $this->testDataFilePath = __DIR__.'/helpers/PaymentRetrieveTestData.php';

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

    public function testRetrievePaymentWithEmail()
    {
        $this->ba->proxyAuth();

        $payments = $this->retrievePaymentsDefault();

        //GIVEN
        $email = $payments['items'][0]['email'];

        $request = $this->request;
        $request['content'] = array('email' => mb_strtoupper($email));

        //WHEN
        $payment = $this->makeRequestAndGetContent($request);

        $this->assertEquals($email, $payment['items'][0]['email']);
    }

    public function testRetrievePaymentWithCardIIN()
    {
        $this->ba->appAuth();

        $payments = $this->getEntities('payment', ['iin' => '111111'], true);

        $this->assertEquals($payments['count'], 0);
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
                'class' => 'RZP\Exception\ExtraFieldsException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
            ],
        );

        $this->startTest($testData);
    }

    public function testFetchAuthorizedPaymentsOnProxyAuth()
    {
        $this->fixtures->create('payment:authorized');

        $this->ba->proxyAuth();

        $testData = array(
            'request' => [
                'url' => '/payments',
                'method' => 'get',
                'content' => ['status' => 'authorized'],
            ],
            'response' => [
                'content' => [
                    'count' => 1,
                ],
            ],
        );

        $this->startTest($testData);
    }

    public function testFetchWrongMerchantIdOnProxyAuth()
    {
        $this->ba->proxyAuth();

        $testData = array(
            'request' => [
                'url' => '/payments',
                'method' => 'get',
                'content' => ['status' => 'authorized', 'merchant_id' => '12345678901234'],
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
                'class' => 'RZP\Exception\ExtraFieldsException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
            ],
        );

        $this->startTest($testData);

        unset($testData['request']['content']['merchant_id']);
        $testData['request']['content']['method'] = 'card';

        $this->startTest($testData);
    }

    public function testMoreThan100InPrivateAuth()
    {
        $data = $this->testData[__FUNCTION__];

        $e = null;

        $this->runRequestResponseFlow($data, function()
        {
            $content = $this->getEntities('payment', ['count' => 1000]);
        });
    }

    public function testSearchEsForNotes()
    {
        $this->ba->proxyAuth();

        $payment = $this->fixtures->create('payment:authorized', ['notes'=>['order_id'=>'es_random_1']]);
        $paymentId = $payment->getId();

        $mockEs = $this->mockEsClient();

        $mockEs->shouldReceive('searchNotes')
               ->once()
               ->with(
                    Mockery::on(function ($data)
                    {
                        $testData = array(
                            'type' => 'payments',
                            'body' => [
                                'size' => 10,
                                'query' => [
                                    'filtered' => [
                                        'query' => [
                                            'multi_match' => [
                                                'query' => 'es_random_1',
                                                'type' => 'cross_fields',
                                                'fields' => ['notes.*']
                                            ]
                                        ],
                                        'filter' => [
                                            'term' => [
                                                'merchant_id' => "10000000000000"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                        );
                        $this->assertArraySelectiveEquals($testData, $data);
                        return true;
                    }))
               ->andReturn([$paymentId]);

        $testData = $this->testData[__FUNCTION__];

        $response = $this->startTest($testData);
        $this->assertEquals('es_random_1', $response['items'][0]['notes']['order_id']);
    }

    public function testSearchEsForNotesWithMerchantIdInQueryParamsOnProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('payment:authorized', ['notes'=>['order_id'=>'es_random_1']]);

        $mockEs = $this->mockEsClient();

        $mockEs->shouldNotReceive('searchNotes');

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testSearchEsForNotesOnAdminAuth()
    {
        $payments = $this->fixtures->times(4)->create('payment:authorized', ['notes'=>['order_id' => 'es_random_1']]);

        foreach ($payments as $payment)
        {
            $paymentIds[] = $payment->getId();
        }

        $mockEs = $this->mockEsClient();

        $mockEs->shouldReceive('searchNotes')
            ->once()
            ->with(
                Mockery::on(function ($data)
                {
                    $testData = array(
                        'type' => 'payments',
                        'body' => [
                            'size' => 1000,
                            'query' => [
                                'filtered' => [
                                    'query' => [
                                        'multi_match' => [
                                            'query' => 'es',
                                            'type' => 'cross_fields',
                                            'fields' => ['notes.*']
                                        ]
                                    ],
                                    'filter' => []
                                ]
                            ]
                        ],
                    );
                    $this->assertArraySelectiveEquals($testData, $data);
                    return true;
                }))
            ->andReturn($paymentIds);

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testSearchEsWithoutQueryParams()
    {
        $this->ba->proxyAuth();

        $mockEs = $this->mockEsClient();

        $mockEs->shouldNotReceive('searchNotes');

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testSearchEsEntityNotPresentInMySql()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('payment:authorized', ['notes'=>['order_id'=>'es_random_1']]);

        $mockEs = $this->mockEsClient();

        $mockEs->shouldReceive('searchNotes')
               ->once()
               ->with(Mockery::any())
               ->andReturn(['rand_payment_id']);

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testSearchEsForNotesPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->fixtures->create('payment:authorized', ['notes'=>['order_id'=>'es_random_1']]);

        $mockEs = $this->mockEsClient();

        $mockEs->shouldNotReceive('searchNotes');

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testSearchEsForStatus()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('payment:authorized', ['notes'=>['order_id'=>'es_random_1']]);

        $mockEs = $this->mockEsClient();

        $mockEs->shouldNotReceive('searchNotes');

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    protected function mockEsClient()
    {
        $clientBuilder = Mockery::mock('RZP\Services\EsClient')->makePartial();

        $this->app->instance('es', $clientBuilder);

        return $clientBuilder;
    }
}
