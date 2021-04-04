<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class EventTrackerTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $config;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/EventTrackerTestData.php';

        parent::setUp();

        $this->ba->basicAuth();

        $this->config = $this->app['config']->get('applications.lumberjack');
    }

    public function testEventTrackSuccess()
    {
        $config = $this->config;

        $key = $config['key'];

        $secret = $config['secret'];

        $signature = $this->getSignature($key, $secret);

        $headers = $this->testData[__FUNCTION__]['request']['server'];

        $headers = [
            'HTTP_content-type'  => 'application/json',
            'HTTP_x-signature'   =>  $signature,
            'HTTP_x-identifier'  =>  $config['identifier'],
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        // append keys
        $this->testData[__FUNCTION__]['request']['content']['key'] = $key;

        $this->startTest();
    }

    public function testEventTrackFailed()
    {
        $config = $this->config;

        $key = $config['key'];

        $secret = $config['secret'] . 'incorrect';

        $signature = $this->getSignature($key, $secret);

        $headers = $this->testData[__FUNCTION__]['request']['server'];

        $headers = [
            'HTTP_content-type'  => 'application/json',
            'HTTP_x-signature'   =>  $signature,
            'HTTP_x-identifier'  =>  $config['identifier'],
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        // append keys
        $this->testData[__FUNCTION__]['request']['content']['key'] = $key;

        $this->startTest();
    }

    protected function getSignature($message, $secret)
    {
        return base64_encode(hash_hmac('sha256', $message, $secret, true));
    }
}
