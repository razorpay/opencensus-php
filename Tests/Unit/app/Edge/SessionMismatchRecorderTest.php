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

}
