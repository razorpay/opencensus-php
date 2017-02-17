<?php

use GuzzleHttp\Client;

use RZP\Tests\Functional\TestCase;

class EventTrackerTest extends TestCase
{
    protected $config;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/EventTrackerTestData.php';

        parent::setUp();

        $this->config = $this->app['config']->get('applications.lumberjack');
    }

    public function testIncorrectAuth()
    {
        $config = $this->config;

        $key = $config['key'];

        $secret = $config['secret'] . 'incorrect';

        $signature = hash_hmac('sha1', $key, $secret);

        $headers = [
            'content-type'  => 'application/json',
            'x-signature'   =>  $signature,
            'x-identifier'  =>  $config['identifier'],
        ];

        $url = $config['url'] . 'track';

        $options = ['json' => $this->testData['dummyPayload']];

        $client = new Client(['headers' => $headers, 'http_errors' => false]);

        $response = $client->request('POST', $url, $options);

        $this->assertEquals($this->testData['responseLjFailed'], $response->getBody()->getContents());
    }

    public function testIncorrectKey()
    {
        $config = $this->config;

        $key = $config['key'];

        $secret = $config['secret'];

        $signature = hash_hmac('sha1', $key, $secret);

        $headers = [
            'content-type'  => 'application/json',
            'x-signature'   =>  $signature,
            'x-identifier'  =>  $config['identifier'],
        ];

        $url = $config['url'] . 'track';

        $options = ['json' => $this->testData['dummyPayload']];

        $client = new Client(['headers' => $headers, 'http_errors' => false]);

        $response = $client->request('POST', $url, $options);

        $this->assertEquals($this->testData['responseLjFailed'], $response->getBody()->getContents());
    }

    public function testEventTrackSuccess()
    {
        $config = $this->config;

        $key = $config['key'];

        $secret = $config['secret'];

        $signature = hash_hmac('sha1', $key, $secret);

        $headers = [
            'content-type'  => 'application/json',
            'x-signature'   =>  $signature,
            'x-identifier'  =>  $config['identifier'],
        ];

        $url = $config['url'] . 'track';

        $data = $this->testData['dummyPayload'];

        unset($data['key']);

        $data['key'] = $key;

        $options = ['json' => $data];

        $client = new Client(['headers' => $headers, 'http_errors' => false]);

        $response = $client->request('POST', $url, $options);

        $this->assertEquals($this->testData['responseLjSuccess'], $response->getBody()->getContents());
    }
}
