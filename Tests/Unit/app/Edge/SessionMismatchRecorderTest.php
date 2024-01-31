<?php

namespace Tests\Unit\app\Edge;

use Mockery;

use App\Edge\SessionMismatchRecorder;
use App\Merchant\GenericMerchant;
use App\Providers\GenericUser;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;

class SessionMismatchRecorderTest extends BaseTestCase
{

    private SessionMismatchRecorder $edgeMismatchRecorder;

    const API_PATH = 'users/login';
    const API_METHOD = 'post';

    const EDGE_DATA = [
        'user_id' => 'userID',
        'merchant_id' => 'merchantID',
    ];

    public function setUp(): void
    {
        parent::setUp();
    }

    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // added to test facade construction, not used anywhere else
        $this->edgeMismatchRecorder = $app['edgeMismatchRecorder'];

        return $app;
    }

    /**
     * @throws \ReflectionException
     */
    private function getData(SessionMismatchRecorder $edgeMismatchRecorder, string $field) : array
    {
        $class  = new \ReflectionClass(get_class($edgeMismatchRecorder));
        $property = $class->getProperty($field);
        $property->setAccessible(true);
        return $property->getValue($edgeMismatchRecorder);
    }

    /**
     * @throws \ReflectionException
     */
    private function setData(SessionMismatchRecorder $edgeMismatchRecorder, string $field, array $value) : void
    {
        $class  = new \ReflectionClass(get_class($edgeMismatchRecorder));
        $property = $class->getProperty($field);
        $property->setAccessible(true);
        $property->setValue($edgeMismatchRecorder, $value);
    }

    public function testEdgeHeaderKeys()
    {
        $this->assertSame("x-edge-jwt-merchant-id", SessionMismatchRecorder::HEADER_KEY_EDGE_MERCHANT_ID);
        $this->assertSame("x-edge-jwt-user-id", SessionMismatchRecorder::HEADER_KEY_EDGE_USER_ID);
    }

     /**
     * @throws \ReflectionException
     */
    public function testSetLegacyDataNullUser()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $this->setData($edgeMismatchRecorder, 'edgeData', self::EDGE_DATA);

        $edgeMismatchRecorder->setLegacyData(self::API_PATH, self::API_METHOD, null);
        $legacyData = $this->getData($edgeMismatchRecorder, 'legacyData');
        $this->assertEmpty($legacyData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetEdgeDataNullHeaders()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $edgeMismatchRecorder->setEdgeData(self::API_PATH, self::API_METHOD, null);
        $edgeData = $this->getData($edgeMismatchRecorder, 'edgeData');
        $this->assertEmpty($edgeData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetEdgeDataEmptyHeaders()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $edgeMismatchRecorder->setEdgeData(self::API_PATH, self::API_METHOD, []);
        $edgeData = $this->getData($edgeMismatchRecorder, 'edgeData');
        $this->assertEmpty($edgeData);
    }

    private function genericUserMock(string $userId) : MockObject
    {
        return $this->getMockBuilder(GenericUser::class)
            ->setConstructorArgs([[
                "id" => $userId
            ]])
            ->setMethods(['currentMerchant'])
            ->getMock();
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetLegacyDataEdgeDataNotSet()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();

        $genericUserMock = $this->genericUserMock('userID');
        $genericUserMock->expects($this::never())->method('currentMerchant');

        $edgeMismatchRecorder->setLegacyData(self::API_PATH, self::API_METHOD, $genericUserMock);
        $legacyData = $this->getData($edgeMismatchRecorder, 'legacyData');
        $this->assertEmpty($legacyData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetLegacyDataNullCurrentMerchant()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $this->setData($edgeMismatchRecorder, 'edgeData', self::EDGE_DATA);

        $genericUserMock = $this->genericUserMock('userID');
        $genericUserMock->expects($this::once())->method('currentMerchant')->with()->willReturn(null);

        $edgeMismatchRecorder->setLegacyData(self::API_PATH, self::API_METHOD, $genericUserMock);
        $legacyData = $this->getData($edgeMismatchRecorder, 'legacyData');
        $this->assertSame([
            'merchant_id' => '',
            'user_id'   => 'userID'
        ], $legacyData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetLegacyDataEmptyCurrentMerchant()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $this->setData($edgeMismatchRecorder, 'edgeData', self::EDGE_DATA);

        $genericUserMock = $this->genericUserMock('userID');
        $genericUserMock->expects($this::once())->method('currentMerchant')->with()->willReturn(new GenericMerchant([]));

        $edgeMismatchRecorder->setLegacyData(self::API_PATH, self::API_METHOD, $genericUserMock);
        $legacyData = $this->getData($edgeMismatchRecorder, 'legacyData');
        $this->assertSame([
            'merchant_id' => '',
            'user_id'   => 'userID'
        ], $legacyData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetLegacyDataValidCurrentMerchant()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $this->setData($edgeMismatchRecorder, 'edgeData', self::EDGE_DATA);

        $genericUserMock = $this->genericUserMock('userID');
        $genericUserMock->expects($this::once())
            ->method('currentMerchant')->with()
            ->willReturn(new GenericMerchant(['id' => 'merchantID']));

        $edgeMismatchRecorder->setLegacyData(self::API_PATH, self::API_METHOD, $genericUserMock);
        $legacyData = $this->getData($edgeMismatchRecorder, 'legacyData');
        $this->assertSame([
            'merchant_id' => 'merchantID',
            'user_id'   => 'userID'
        ], $legacyData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetEdgeDataEdgeHeadersAbsent()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();

        $edgeMismatchRecorder->setEdgeData(self::API_PATH, self::API_METHOD, [ "random" => ["value"] ]);
        $edgeData = $this->getData($edgeMismatchRecorder, 'edgeData');
        $this->assertEmpty($edgeData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetEdgeDataEdgeHeadersMalformed()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();

        $edgeMismatchRecorder->setEdgeData(self::API_PATH, self::API_METHOD, [
            SessionMismatchRecorder::HEADER_KEY_EDGE_USER_ID => [ "key" => "value" ], // associative array is malformed
            SessionMismatchRecorder::HEADER_KEY_EDGE_MERCHANT_ID => ["value"], // sequential array is not malformed
        ]);
        $edgeData = $this->getData($edgeMismatchRecorder, 'edgeData');
        $this->assertEmpty($edgeData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetEdgeDataOnlyUserHeaderPresent()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();

        $edgeMismatchRecorder->setEdgeData(self::API_PATH, self::API_METHOD, [
            SessionMismatchRecorder::HEADER_KEY_EDGE_USER_ID => [ 'userID' ],
        ]);
        $edgeData = $this->getData($edgeMismatchRecorder, 'edgeData');
        $this->assertSame([
            'merchant_id' => '',
            'user_id' => 'userID',
        ], $edgeData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetEdgeDataOnlyMerchantHeaderPresent()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();

        $edgeMismatchRecorder->setEdgeData(self::API_PATH, self::API_METHOD, [
            SessionMismatchRecorder::HEADER_KEY_EDGE_MERCHANT_ID => [ 'merchantID' ],
        ]);
        $edgeData = $this->getData($edgeMismatchRecorder, 'edgeData');
        $this->assertSame([
            'merchant_id' => 'merchantID',
            'user_id' => '',
        ], $edgeData);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetEdgeDataEdgeHeadersPresent()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();

        $edgeMismatchRecorder->setEdgeData(self::API_PATH, self::API_METHOD, [
            SessionMismatchRecorder::HEADER_KEY_EDGE_MERCHANT_ID => [ 'merchantID' ],
            SessionMismatchRecorder::HEADER_KEY_EDGE_USER_ID => [ 'userID' ],
        ]);
        $edgeData = $this->getData($edgeMismatchRecorder, 'edgeData');
        $this->assertSame([
            'merchant_id' => 'merchantID',
            'user_id' => 'userID',
        ], $edgeData);
    }

    protected function requestMock(): Request
    {

        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['route', 'header', 'server'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('route')->willReturn(null);
        $requestMock->expects($this->any())->method('header')->willReturn('header_value');
        $requestMock->expects($this->any())->method('server')->willReturn('header_value');

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);
        return $requestMock;
    }

    public function testRecordMismatchesNoData()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $request = $this->requestMock();
        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance('trace', $trace);

        $trace->shouldReceive('error')->times(0);
        $trace->shouldReceive('warning')->times(0);
        $trace->shouldReceive('info')->times(0);
        $edgeMismatchRecorder->recordMismatches($request);
    }

    /**
     * @throws \ReflectionException
     */
    public function testRecordMismatchesSameData()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $this->setData($edgeMismatchRecorder, 'edgeData', self::EDGE_DATA);
        $this->setData($edgeMismatchRecorder, 'legacyData', self::EDGE_DATA);
        $request = $this->requestMock();
        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance('trace', $trace);

        $trace->shouldReceive('error')->times(0);
        $trace->shouldReceive('warning')->times(0);
        $trace->shouldReceive('info')->times(0);
        $edgeMismatchRecorder->recordMismatches($request);
    }

    /**
     * @throws \ReflectionException
     */
    public function testRecordMismatchesMismatchedData()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $this->setData($edgeMismatchRecorder, 'edgeData', self::EDGE_DATA);
        $this->setData($edgeMismatchRecorder, 'legacyData', [
            'user_id' => 'userID1',
            'merchant_id' => 'merchantID1',
        ]);
        $request = $this->requestMock();
        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance('trace', $trace);

        $trace->shouldReceive('error')->times(0);
        $trace->shouldReceive('warning')->times(0);
        $trace->shouldReceive('info')->times(1);
        $edgeMismatchRecorder->recordMismatches($request);
    }

    /**
     * @throws \ReflectionException
     */
    public function testRecordMismatchesEdgeDataAbsent()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $this->setData($edgeMismatchRecorder, 'legacyData', self::EDGE_DATA);
        $request = $this->requestMock();
        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance('trace', $trace);

        $trace->shouldReceive('error')->times(0);
        $trace->shouldReceive('warning')->times(0);
        $trace->shouldReceive('info')->times(0);
        $edgeMismatchRecorder->recordMismatches($request);
    }

    /**
     * @throws \ReflectionException
     */
    public function testRecordMismatchesLegacyDataAbsent()
    {
        $edgeMismatchRecorder = new SessionMismatchRecorder();
        $this->setData($edgeMismatchRecorder, 'edgeData', self::EDGE_DATA);
        $request = $this->requestMock();
        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance('trace', $trace);

        $trace->shouldReceive('error')->times(0);
        $trace->shouldReceive('warning')->times(0);
        $trace->shouldReceive('info')->times(0);
        $edgeMismatchRecorder->recordMismatches($request);
    }
}
