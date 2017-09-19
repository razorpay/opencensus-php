<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class EventTrackerTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $config;

    public function setUp()
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

        $signature = hash_hmac('sha1', $key, $secret);

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

        $signature = hash_hmac('sha1', $key, $secret);

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
}
