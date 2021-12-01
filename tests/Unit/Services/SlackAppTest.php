<?php

namespace Unit\Services;

use ReflectionClass;

use RZP\Models\Payment\Downtime;
use RZP\Tests\Functional\TestCase;
use RZP\Services\RazorpayLabs\SlackApp;


class SlackAppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testGetPayload()
    {
        $sampleDowntimeEntity = new Downtime\Entity(['status' => 'started']);

        $slackApp = new ReflectionClass(SlackApp::class);
        $getPayload = $slackApp->getMethod('getDowntimeNotificationPayload');
        $getPayload->setAccessible(true);

        $actualPayload = $getPayload
            ->invokeArgs(
                new SlackApp($this->app),
                [$sampleDowntimeEntity, 'started']);

        $expectedPayload = '{"entity":"event","event":"payment.downtime.started","contains":["payment.downtime"],"payload":{"payment.downtime":{"entity":{"id":"down_","entity":"payment.downtime","status":"started","instrument":[],"instrument_schema":[]}}}}';
        $this->assertSame($expectedPayload, $actualPayload);
    }

    public function testGetUrl()
    {
        $slackApp = new ReflectionClass(SlackApp::class);
        $getUrl = $slackApp->getMethod('getDowntimeNotificationUrl');
        $getUrl->setAccessible(true);

        $actualUrl = $getUrl->invokeArgs(new SlackApp($this->app), []);
        $this->assertSame('https://sample-slack-url.something/broadcast/rzp_downtime', $actualUrl);

    }

    public function testGetRequestAuth()
    {
        $slackApp = new ReflectionClass(SlackApp::class);
        $getRequestAuth = $slackApp->getMethod('getRequestAuth');
        $getRequestAuth->setAccessible(true);

        $actualResult = $getRequestAuth->invokeArgs(new SlackApp($this->app), []);

        $expectedResult = ['api', 'RANDOM_SLACK_PASSWORD']; // from .env.testing
        $this->assertSame($expectedResult, $actualResult);
    }
}
