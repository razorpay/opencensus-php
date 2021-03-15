<?php

namespace RZP\Tests\Unit\Services;

use Exception;
use Illuminate\Support\Facades\Redis;

use RZP\Tests\TestCase;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Services\CredcaseSigner;

class CredcaseSignerTest extends TestCase
{
    use MocksRazorx;

    const PAYLOAD_1            = '{"order_id":"order_00000000000001","payment_id":"pay_00000000000001"}';
    const PUBLIC_KEY_1         = 'rzp_test_1DP5mmOlF5G5ag';
    const EXPECTED_SIGNATURE_1 = 'c58c146fce3b7d5a517c7e0b0319f5deabc2f5787ac9fabddc62bd9ee8313e5f';

    const PUBLIC_KEY_1_ENCRYPTED_SECRET = 'd141cd18f3caff56f69795d78c9c30a871ca0cc20fd883ad10e1bb2fa35f26c5e72229cf8a3042fd764e63f5cd'; // Raw: thisissupersecret.

    /** @var mixed Mocked BasicAuth */
    protected $ba;

    /** @var mixed Mocked Redis connection */
    protected $redis;

    public function setUp()
    {
        parent::setUp();

        // Disables mock so service actually calls mocked redis.
        $this->app['config']->set('services.credcase_signer.mock', false);

        $this->mockBasicAuth();
    }

    public function testSignWhenRazorxTreatmentIsOff()
    {
        $this->mockRazorxTreatmentV2(CredcaseSigner::RAZORX_FEATURE, 'off');

        $this->ba->expects($this->once())
            ->method('sign')
            ->with(self::PAYLOAD_1, self::PUBLIC_KEY_1)
            ->willReturn(self::EXPECTED_SIGNATURE_1);

        $signature = (new CredcaseSigner)->sign(self::PAYLOAD_1, self::PUBLIC_KEY_1);

        $this->assertEquals(self::EXPECTED_SIGNATURE_1, $signature);
    }

    public function testSignWhenRazorxTreatmentIsOn()
    {
        $this->mockRazorxTreatmentV2(CredcaseSigner::RAZORX_FEATURE, 'on');

        $this->mockRedis();

        $this->redis->expects($this->once())
            ->method('get')
            ->with('credcase:ks:v1:rzp_test_1DP5mmOlF5G5ag')
            ->willReturn(self::PUBLIC_KEY_1_ENCRYPTED_SECRET);

        $signature = (new CredcaseSigner)->sign(self::PAYLOAD_1, self::PUBLIC_KEY_1);

        $this->assertEquals(self::EXPECTED_SIGNATURE_1, $signature);
    }

    public function testSignWhenRazorxTreatmentIsOnAndRedisFails()
    {
        $this->mockRazorxTreatmentV2(CredcaseSigner::RAZORX_FEATURE, 'on');

        $this->mockRedis();

        $this->redis->expects($this->exactly(2))
            ->method('get')
            ->with('credcase:ks:v1:rzp_test_1DP5mmOlF5G5ag')
            ->will($this->throwException(new Exception('failed to getv value from redis')));

        $this->ba->expects($this->once())
            ->method('sign')
            ->with(self::PAYLOAD_1, self::PUBLIC_KEY_1)
            ->willReturn(self::EXPECTED_SIGNATURE_1);

        $signature = (new CredcaseSigner)->sign(self::PAYLOAD_1, self::PUBLIC_KEY_1);

        $this->assertEquals(self::EXPECTED_SIGNATURE_1, $signature);
    }

    protected function mockBasicAuth()
    {
        $this->ba = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sign', 'getMerchantId', 'getMode'])
            ->getMock();
        $this->app->instance('basicauth', $this->ba);

        // Merchant id and mode is common expectation for doing razorx treatment.
        $this->ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn('10000000000000');
        $this->ba->expects($this->atLeastOnce())->method('getMode')->willReturn('test');
    }

    protected function mockRedis()
    {
        $this->redis = $this->getMockBuilder(Redis::class)
            ->setMethods(['get'])
            ->getMock();
        Redis::shouldReceive('connection')
            ->with('credcase_signer')
            ->andReturn($this->redis);
    }
}
