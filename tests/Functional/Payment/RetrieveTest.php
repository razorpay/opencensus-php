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

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentRetrieveTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $payment = $this->fixtures->create('payment:captured', ['fee' => 23000]);

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
        $this->assertTrue(isset($payment['acquirer_data']));

        $this->assertEquals(null, $payment['acquirer_data']['auth_code']);
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

        $this->assertEquals(1, $payment['count']);
        $this->assertEquals($email, $payment['items'][0]['email']);
    }

    public function testRetrievePaymentsOnMerchantDashboardWhenInputContactIsGivenExpectsPaymentsWithGivenContact()
    {
        $this->ba->proxyAuth();

        $contact = '987654321';

        $this->fixtures->create('payment', [
            'contact'    => '+91' . $contact,
        ]);

        $this->fixtures->create('payment', [
            'contact'    => $contact,
        ]);

        $this->fixtures->create('payment', [
            'contact'    => '+918888888888',
        ]);

        $request = $this->request;

        $request['content'] = array('contact' => $contact);

        $payments = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $payments['count']);
        $this->assertEquals('payment', $payments['items'][0]['entity']);
        $this->assertEquals($contact, $payments['items'][0]['contact']);
    }

    public function testRetrievePaymentsOnMerchantDashboardWhenInputContactAndCountryCodeIsGivenExpectsPaymentsOfGivenContactWithAndWithoutCountryCode()
    {
        $this->ba->proxyAuth();

        $contact = '987654321';

        $this->fixtures->create('payment', [
            'contact'    => '+91' . $contact,
        ]);

        $this->fixtures->create('payment', [
            'contact'    => $contact,
        ]);

        $this->fixtures->create('payment', [
            'contact'    => '+918888888888',
        ]);

        $request = $this->request;

        $request['content'] = array('country_code' => '+91', 'contact' => $contact);

        $payments = $this->makeRequestAndGetContent($request);

        $this->assertEquals(2, $payments['count']);
        $this->assertEquals('payment', $payments['items'][0]['entity']);
        $this->assertEquals('payment', $payments['items'][1]['entity']);
        $this->assertEquals($contact, $payments['items'][0]['contact']);
        $this->assertEquals('+91' . $contact, $payments['items'][1]['contact']);
    }

    public function testRetrievePaymentHavingNullContact()
    {
        $payment = $this->fixtures->create('payment:captured', ['contact' => null]);

        $id = $payment->getPublicId();

        $request = $this->request;
        $request['url'] .= '/' . $id;

        $payment = $this->makeRequestAndGetContent($request);

        $this->assertEquals($id, $payment['id']);
        $this->assertNull($payment['contact']);
    }

    public function testRetrievePaymentWithCardIIN()
    {
        $this->ba->adminAuth();

        $payments = $this->getEntities('payment', ['iin' => '111111'], true);

        $this->assertEquals($payments['count'], 0);
    }

    public function testRetrievePaymentWithEmailAndLast4()
    {
        $payment = $this->fixtures->create('payment:captured', ['email' => 'test@test.test']);

        $this->ba->adminAuth();

        $params = [
            'email' => 'test@test.test',
            'last4' => '1112',
        ];

        $payments = $this->getEntities('payment', $params, true);

        $this->assertEquals(0, $payments['count']);

        $params['last4'] = '1111';

        $payments = $this->getEntities('payment', $params, true);

        $this->assertEquals(1, $payments['count']);
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

    public function testRetrievePaymentWithCardDetails()
    {
        $payment = $this->fixtures->create('payment:captured', ['fee' => 23000]);

        $this->ba->privateAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $payment->getPublicId();

        $response = $this->startTest();

        $this->assertNotEmpty($response['card_id']);
        $this->assertNotEmpty($response['card']['id']);
        $this->assertEquals($response['card_id'], $response['card']['id']);
    }

    public function testRetrieveMultiplePaymentsWithCardDetails()
    {
        $payment = $this->fixtures->create('payment:captured', ['fee' => 23000]);
        $payment = $this->fixtures->create('payment:netbanking_captured', ['fee' => 23000]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchAuthorizedPaymentsOnAppAuth()
    {
        $this->fixtures->create('payment:authorized');

        $this->ba->adminAuth();

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

        $payment = $this->fixtures->create('payment:authorized', [
            'notes' => [
                'order_id' => 'es_random_1'
            ]
        ]);

        $paymentId = $payment->getId();

        $esMock = $this->createEsMock(['search']);

        $expectedSearchParams = $this->testData["testSearchEsForNotesExpectedSearchParams"];

        $expectedSearchRes    = [
            'hits' => [
                'hits' => [
                    [
                        '_id' => $paymentId,
                    ]
                ],
            ],
        ];

        $esMock->expects($this->once())
               ->method('search')
               ->with($expectedSearchParams)
               ->willReturn($expectedSearchRes);

        $response = $this->startTest();

        $this->assertEquals('es_random_1', $response['items'][0]['notes']['order_id']);
    }

    public function testSearchEsForNotesWithMerchantIdInQueryParamsOnProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('payment:authorized', ['notes' => ['order_id' => 'es_random_1']]);

        $this->createEsMock(['search'])
             ->expects($this->never())
             ->method('search');

        $this->startTest();
    }

    public function testSearchEsForNotesOnAdminAuth()
    {
        $payments = $this->fixtures->times(4)->create('payment:authorized', ['notes' => ['order_id' => 'es_random_1']]);

        $esMock = $this->createEsMock(['search']);

        $expectedSearchParams = $this->testData["testSearchEsForNotesOnAdminAuthExpectedSearchParams"];

        foreach ($payments as $payment)
        {
            $expectedSearchRes['hits']['hits'][] = ['_id' => $payment->getId()];
        }

        $esMock->expects($this->once())
               ->method('search')
               ->with($expectedSearchParams)
               ->willReturn($expectedSearchRes);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSearchEsForNotesOnAdminAuthRestricted()
    {
        $this->fixtures->edit('org', '100000razorpay', ['type' => 'restricted']);

        $this->fixtures->create('payment:authorized', ['notes' => ['order_id' => 'es_random_1']]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSearchEsWithoutQueryParams()
    {
        $this->ba->proxyAuth();

        $this->createEsMock(['search'])
             ->expects($this->never())
             ->method('search');

        $this->startTest();
    }

    public function testSearchEsEntityNotPresentInMySql()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('payment:authorized', ['notes' => ['order_id' => 'es_random_1']]);

        $esMock = $this->createEsMock(['search']);

        $expectedSearchRes    = [
            'hits' => [
                'hits' => [
                    [
                        '_id' => '10000000000000',
                    ]
                ],
            ],
        ];

        $esMock->expects($this->once())
               ->method('search')
               ->willReturn($expectedSearchRes);

        $this->startTest();
    }

    public function testSearchEsForNotesPrivateAuth()
    {
        $this->markTestSkipped('Notes on ES has been allowed on private auth - transaction tracker');

        $this->ba->privateAuth();

        $this->fixtures->create('payment:authorized', ['notes' => ['order_id' => 'es_random_1']]);

        $this->createEsMock(['search'])
             ->expects($this->never())
             ->method('search');

        $this->startTest();
    }

    public function testSearchEsForStatus()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('payment:authorized', ['notes' => ['order_id' => 'es_random_1']]);

        $this->createEsMock(['search'])
             ->expects($this->never())
             ->method('search');

        $this->startTest();
    }
}
