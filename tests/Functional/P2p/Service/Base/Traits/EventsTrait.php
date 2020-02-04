<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use Mockery;
use Http\Mock\Client;
use GuzzleHttp\Psr7\Request;
use RZP\Tests\Functional\Fixtures\Entity\Webhook;
use RZP\Tests\Functional\Helpers\WebhookTrait;

trait EventsTrait
{
    use WebhookTrait;

    /**
     * @var Client
     */
    protected $mockedWebhookClient;

    protected $mockedRavenRequest;

    protected $mockedRemindersRequest;

    protected function setEventsForMerchant()
    {
        (new Webhook())->create([
            'url'       => 'https://www.example.com',
            'secret'    => 'notsosecret',
        ]);

        $this->mockedWebhookClient = $this->setInfernoMockClient();
    }

    protected function assertWebhookContent(
        callable $contentHandler,
        callable $headersHandler = null,
        int $index = 0)
    {
        $request = $this->getWebhookMockedRequest($index);

        $content = $request->getBody()->getContents();

        $contentHandler(json_decode($content, true));

        if (is_callable($headersHandler))
        {
            $headers = $request->getHeaders();

            $headersHandler($headers);
        }

        return $request;
    }

    protected function mockRaven()
    {
        $raven = Mockery::mock('RZP\Services\Raven')->makePartial();

        $this->app->instance('raven', $raven);

        $raven->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                 $this->mockedRavenRequest = [$route, $method, $input];

                $response = [
                    'success' => true,
                ];

                return $response;
            });

        $this->app->instance('raven', $raven);
    }

    protected function mockReminder()
    {
        $reminders = Mockery::mock('RZP\Services\Reminders')->makePartial();

        $this->app->instance('reminders', $reminders);

        $reminders->shouldReceive('createReminder')
            ->with(Mockery::type('array'), Mockery::type('string'))
            ->andReturnUsing(function ($request, $merchantId){
                $this->mockedRemindersRequest = [$request, $merchantId];

                $response = [
                    'success'   => true
                ];

                return $response;
            });

        $this->app->instance('reminders', $reminders);
    }

    protected function assertMockReminder(callable $inputHandler)
    {
        $inputHandler($this->mockedRemindersRequest[0], $this->mockedRemindersRequest[1]);
    }

    protected function assertRavenRequest(callable $inputHandler, $method = 'post', $route = 'sms')
    {
        $this->assertSame($route, $this->mockedRavenRequest[0]);
        $this->assertSame($method, $this->mockedRavenRequest[1]);

        $inputHandler($this->mockedRavenRequest[2]);
    }

    /**
     * @param int $index
     * @return Request
     */
    protected function getWebhookMockedRequest(int $index)
    {
        return $this->mockedWebhookClient->getRequests()[$index];
    }
}
