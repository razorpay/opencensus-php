<?php

namespace RZP\Tests\Unit\Services;

use Exception;
use Illuminate\Redis\Connections\PredisConnection;

use RZP\Tests\TestCase;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Services\CredcaseSigner;

class CredcaseSignerTest extends TestCase
{
    use MocksRazorx;

    const PAYLOAD_1            = '{"order_id":"order_00000000000001","payment_id":"pay_00000000000001"}';
    const EXPECTED_SIGNATURE_1 = 'c58c146fce3b7d5a517c7e0b0319f5deabc2f5787ac9fabddc62bd9ee8313e5f';
    const KEYS = [
        'PUBLIC_KEY_1' => 'rzp_test_1DP5mmOlF5G5ag',
        'PARTNER_KEY_1' => 'rzp_test_partner_1DP5mmOlF5G5ag-acc_7XP5mmOlF8U5Bp',
        'OAUTH_KEY_1' => 'rzp_test_oauth_6ZJzxyLFWrGs74',
        'OAUTH_KEY_2' => 'rzp_test_oauth_6ZJzxyLFWrGs74someextrachars'
    ];

    const ENCRYPTED_SECRET_1 = 'd141cd18f3caff56f69795d78c9c30a871ca0cc20fd883ad10e1bb2fa35f26c5e72229cf8a3042fd764e63f5cd'; // Raw: thisissupersecret.

    /** @var mixed Mocked BasicAuth */
    protected $ba;

    /** @var mixed Mocked Redis connection */
    protected $redis;

    protected function setUp(): void
    {
        parent::setUp();

        // Disables mock so service actually calls mocked redis.
        $this->app['config']->set('services.credcase_signer.mock', false);

        $this->mockBasicAuth();
    }

    public function testSignWhenRazorxTreatmentIsOn()
    {
        $this->mockRedis();

        $this->redis->expects($this->once())
            ->method('get')
            ->with('credcase:ks:v1:rzp_test_1DP5mmOlF5G5ag')
            ->willReturn(self::ENCRYPTED_SECRET_1);

        $signature = (new CredcaseSigner($this->redis))->sign(self::PAYLOAD_1, self::KEYS['PUBLIC_KEY_1']);

        $this->assertEquals(self::EXPECTED_SIGNATURE_1, $signature);
    }

    public function testSignWhenRazorxTreatmentIsOnAndRedisFails()
    {
        $this->mockRedis();

        $this->redis->expects($this->exactly(2))
            ->method('get')
            ->with('credcase:ks:v1:rzp_test_1DP5mmOlF5G5ag')
            ->will($this->throwException(new Exception('failed to getv value from redis')));

        $this->ba->expects($this->once())
            ->method('sign')
            ->with(self::PAYLOAD_1, self::KEYS['PUBLIC_KEY_1'])
            ->willReturn(self::EXPECTED_SIGNATURE_1);

        $signature = (new CredcaseSigner($this->redis))->sign(self::PAYLOAD_1, self::KEYS['PUBLIC_KEY_1']);

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
        $this->redis = $this->getMockBuilder(PredisConnection::class)
            ->setConstructorArgs([null])
            ->setMethods(['get'])
            ->getMock();
    }

    public function testSignWhenPartnerKeyIsUsed()
    {
        $this->mockRedis();

        $this->redis->expects($this->once())
            ->method('get')
            ->with('credcase:ks:v1:rzp_test_partner_1DP5mmOlF5G5ag')
            ->willReturn(self::ENCRYPTED_SECRET_1);

        $signature = (new CredcaseSigner($this->redis))->sign(self::PAYLOAD_1, self::KEYS['PARTNER_KEY_1']);

        $this->assertEquals(self::EXPECTED_SIGNATURE_1, $signature);
    }

    public function testSignWhenPartnerKeyIsUsedAndRedisFails()
    {
        $this->mockRedis();

        $this->redis->expects($this->exactly(2))
            ->method('get')
            ->with('credcase:ks:v1:rzp_test_partner_1DP5mmOlF5G5ag')
            ->will($this->throwException(new Exception('failed to getv value from redis')));

        $this->ba->expects($this->once())
            ->method('sign')
            ->with(self::PAYLOAD_1, self::KEYS['PARTNER_KEY_1'])
            ->willReturn(self::EXPECTED_SIGNATURE_1);

        $signature = (new CredcaseSigner($this->redis))->sign(self::PAYLOAD_1, self::KEYS['PARTNER_KEY_1']);

        $this->assertEquals(self::EXPECTED_SIGNATURE_1, $signature);
    }

    public function testSignWhenOauthKeyIsUsed()
    {
        $this->mockRedis();

        $this->redis->expects($this->once())
            ->method('get')
            ->with('credcase:ks:v1:' . self::KEYS['OAUTH_KEY_1'])
            ->willReturn(self::ENCRYPTED_SECRET_1);

        $signature = (new CredcaseSigner($this->redis))->sign(self::PAYLOAD_1, self::KEYS['OAUTH_KEY_1']);

        $this->assertEquals(self::EXPECTED_SIGNATURE_1, $signature);
    }

    public function testSignWhenOauthKeyWithExtraCharsIsUsed()
    {
        $this->mockRedis();

        $this->redis->expects($this->once())
            ->method('get')
            ->with('credcase:ks:v1:' . self::KEYS['OAUTH_KEY_1'])
            ->willReturn(self::ENCRYPTED_SECRET_1);

        $signature = (new CredcaseSigner($this->redis))->sign(self::PAYLOAD_1, self::KEYS['OAUTH_KEY_2']);

        $this->assertEquals(self::EXPECTED_SIGNATURE_1, $signature);
    }

    public function testSignWhenOauthKeyIsUsedAndRedisFails()
    {
        $this->mockRedis();

        $this->redis->expects($this->exactly(2))
            ->method('get')
            ->with('credcase:ks:v1:' . self::KEYS['OAUTH_KEY_1'])
            ->will($this->throwException(new Exception('failed to getv value from redis')));

        $this->ba->expects($this->once())
            ->method('sign')
            ->with(self::PAYLOAD_1, self::KEYS['OAUTH_KEY_1'])
            ->willReturn(self::EXPECTED_SIGNATURE_1);

        $signature = (new CredcaseSigner($this->redis))->sign(self::PAYLOAD_1, self::KEYS['OAUTH_KEY_1']);

        $this->assertEquals(self::EXPECTED_SIGNATURE_1, $signature);
    }

}
