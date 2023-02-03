<?php

namespace RZP\Tests\Functional\Order;

use Carbon\Carbon;
use RZP\Models\Order;
use RZP\Models\UpiMandate;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\UpiMandate\Frequency;
use RZP\Models\Feature\Constants as Feature;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiRecurringOrderTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/UpiRecurringOrderTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $this->fixtures->create('terminal:dedicated_mindgate_recurring_terminal', ['merchant_id'=> '10000000000000']);
    }

    public function testCreateUpiRecurringOrder()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertEquals($order[Order\Entity::ID], $upiMandate[UpiMandate\Entity::ORDER_ID]);

        $this->assertEquals($order[Order\Entity::MERCHANT_ID], $upiMandate[UpiMandate\Entity::MERCHANT_ID]);

        $this->assertNotNull($upiMandate[UpiMandate\Entity::CUSTOMER_ID]);

        $this->assertNotNull($upiMandate[UpiMandate\Entity::FREQUENCY]);

        $this->assertNotNull($upiMandate[UpiMandate\Entity::RECURRING_VALUE]);

        $frequency = $upiMandate[UpiMandate\Entity::FREQUENCY];
        $recurringValue = $upiMandate[UpiMandate\Entity::RECURRING_VALUE];

        $this->assertEquals(UpiMandate\Frequency::$frequencyToRecurringValueMap[$frequency], $recurringValue);

        $this->assertNotNull($upiMandate[UpiMandate\Entity::RECURRING_TYPE]);
    }

    public function testCreateOrderWithMaxAmountLesserThanMinLimit()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNull($upiMandate);

        $this->assertNull($order);
    }

    public function testCreateOrderWithMaxAmountGreaterThanMaxLimit()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNull($upiMandate);

        $this->assertNull($order);
    }

    public function testCreateOrderWithStartTimeGreaterThanEndTime()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertNull($upiMandate);
    }

    public function testCreateOrderWithoutFrequency()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $this->assertNotNull($upiMandate['start_time']);

        $this->assertNotNull($upiMandate['end_time']);
    }

    public function testCreateOrderWithAsPresentedFrequency()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $this->assertNotNull($upiMandate['start_time']);

        $this->assertNotNull($upiMandate['end_time']);

        $this->assertEquals(null, $upiMandate['recurring_value']);
    }

    public function testCreateOrderWithoutStartAndEndTime()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $this->assertNotNull($upiMandate['start_time']);

        $this->assertNotNull($upiMandate['end_time']);

        $startTime = Carbon::createFromTimestamp($upiMandate['start_time']);
        $endTime = Carbon::createFromTimestamp($upiMandate['end_time']);

        $this->assertTrue($startTime->lessThan($endTime));

        $this->assertSame(10, $startTime->diffInYears($endTime));
    }

    public function testCreateOrderWithStartTimeAndNoEndTime()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $startTime = Carbon::createFromTimestamp($upiMandate['start_time']);
        $endTime = Carbon::createFromTimestamp($upiMandate['end_time']);

        $this->assertTrue($startTime->lessThan($endTime));
    }

    public function testCreateOrderWithEndTimeAndNoStartTime()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $this->assertNotNull($upiMandate['start_time']);

        $this->assertNotNull($upiMandate['end_time']);

        $startTime = Carbon::createFromTimestamp($upiMandate['start_time']);
        $endTime = Carbon::createFromTimestamp($upiMandate['end_time']);

        $this->assertTrue($startTime->lessThan($endTime));
    }

    public function testCreateOrderAmountGreaterThanMaxAmount()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNull($upiMandate);

        $this->assertNull($order);
    }

    public function testPreferencesForUpiRecurringOrder()
    {
        $this->testCreateUpiRecurringOrder();

        $order = $this->getDbLastEntity('order');

        $this->ba->publicAuth();

        $testData['request']['content'] = ['order_id' => $order->getPublicId()];

        $this->startTest($testData);
    }

    /**
     * Function to generate unsupported frequency testdata
     * @return array
     */
    public function functionGenerateUnsupportedFrequency()
    {
        $cases = [];

        $cases['daily'] = [
            Frequency::DAILY,
            PublicErrorCode::BAD_REQUEST_ERROR,
            'Not a valid frequency: daily'
        ];

        $cases['weekly'] = [
            Frequency::WEEKLY,
            PublicErrorCode::BAD_REQUEST_ERROR,
            'Not a valid frequency: weekly'
        ];

        $cases['bimonthly'] = [
            Frequency::BIMONTHLY,
            PublicErrorCode::BAD_REQUEST_ERROR,
            'Not a valid frequency: bimonthly'
        ];

        $cases['quarterly'] = [
            Frequency::QUARTERLY,
            PublicErrorCode::BAD_REQUEST_ERROR,
            'Not a valid frequency: quarterly'
        ];

        $cases['half_yearly'] = [
            Frequency::HALF_YEARLY,
            PublicErrorCode::BAD_REQUEST_ERROR,
            'Not a valid frequency: half_yearly'
        ];

        $cases['yearly'] = [
            Frequency::YEARLY,
            PublicErrorCode::BAD_REQUEST_ERROR,
            'Not a valid frequency: yearly'
        ];

        $cases['invalid_frequency'] = [
            'random',
            PublicErrorCode::BAD_REQUEST_ERROR,
            'Not a valid frequency: random'
        ];

        $cases['empty_frequency'] = [
            '',
            PublicErrorCode::BAD_REQUEST_ERROR,
            'The frequency field is required.',
        ];

        return $cases;
    }

    /**
     * @dataProvider functionGenerateUnsupportedFrequency
     *
     * @param      $frequency
     * @param null $exceptionCode
     * @param null $exceptionMessage
     */
    public function testCreateOrderWithUnsupportedFrequency($frequency, $exceptionCode = null, $exceptionMessage = null)
    {
        $testData['request'] = $this->getRequestForRecurringOrder([
             'content'  => [
                 'token'   =>  [
                     'max_amount' => 150000,
                     'frequency'  => $frequency,
                 ]
             ]
          ]);

        $testData['response'] = $this->getResponsePayloadForFailedRecurringOrder([
            'content'   => [
                'error'    => [
                    'code'        => $exceptionCode,
                    'description' => $exceptionMessage,
                ]
             ]
           ]);

        $testData['exception'] = [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ];

        $this->runRequestResponseFlow($testData);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNull($upiMandate);

        $this->assertNull($order);
    }

    /**
     * Function to generate supported frequency testdata
     * @return array
     */
    public function functionGenerateSupportedFrequency()
    {
        $cases['as_presented'] = [
            Frequency::AS_PRESENTED,
        ];

        $cases['monthly'] = [
            Frequency::MONTHLY,
        ];

        return $cases;
    }

    /**
     * @dataProvider functionGenerateSupportedFrequency
     *
     * @param $frequency
     */
    public function testCreateOrderWithSupportedFrequency($frequency)
    {
        $testData['request'] =  $this->getRequestForRecurringOrder([
              'content' => [
                  'token'  => [
                      'max_amount' => 150000,
                      'frequency'  => $frequency,
                  ]
              ]
            ]
        );

        $testData['response'] = $this->getResponsePayloadForSuccessfulRecurringOrder();

        $this->runRequestResponseFlow($testData);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastOrder();

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $this->assertNotNull($upiMandate['start_time']);

        $this->assertNotNull($upiMandate['end_time']);

        $this->assertSame($frequency, $upiMandate['frequency']);
    }

    /**
     * helper testdata function to get recurring order details
     * @return array[]
     */
    private function getRequestForRecurringOrder($overrideWith = []): array
    {
        $request =  [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ];

        return array_merge_recursive($request, $overrideWith);
    }

    /**
     * Helper test data function to return failure response
     * @return array
     */
    private function getResponsePayloadForFailedRecurringOrder($overrideWith = []){
        $response = [
            'status_code' => 400,
        ];

        return array_merge_recursive($response, $overrideWith);

    }

    /**
     * Helper test data function to return successful response.
     * @return array
     */
    private function getResponsePayloadForSuccessfulRecurringOrder(){
        return [
            'content'     => [
                'amount'    => 50000,
                'currency'  => 'INR',
            ],
            'status_code' => 200,
        ];
    }

}

