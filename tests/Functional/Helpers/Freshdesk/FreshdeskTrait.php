<?php

namespace RZP\Tests\Functional\Helpers\Freshdesk;

use Mockery;
use RZP\Models;

trait FreshdeskTrait
{
    protected $freshdeskClientMock;

    protected function expectFreshdeskRequestAndRespondWith($expectedPath, $expectedMethod, $expectedContent, $respondWith = [], $times = 1, $checkHtmlTag = false)
    {
        $expectedUrl2 = $this->app['config']->get('applications.freshdesk.url2') . '/' . $expectedPath;
        $expectedUrlInd = $this->app['config']->get('applications.freshdesk.urlind') . '/' . $expectedPath;
        $expectedUrlCap = $this->app['config']->get('applications.freshdesk.urlcap') . '/' . $expectedPath;
        $expectedUrlx = $this->app['config']->get('applications.freshdesk.urlx') . '/' . $expectedPath;

        $expectedUrls = [ $expectedUrl2, $expectedUrlInd, $expectedUrlCap, $expectedUrlx];

        $this->freshdeskClientMock
            ->shouldReceive('getResponse')
            ->times($times)
            ->with(Mockery::on(function ($request)  use ($expectedUrls, $expectedMethod, $expectedContent, $checkHtmlTag) {
                if (in_array($request['url'], $expectedUrls) === false)
                {
                    return false;
                }

                return $this->validateMethodAndContent($request, $expectedMethod, $expectedContent, $checkHtmlTag);
            }))
            ->andReturnUsing(function () use ($respondWith) {
                $response = new \WpOrg\Requests\Response;

                $response->body = json_encode($respondWith);

                return $response;
            });

    }

    protected function setUpFreshdeskClientMock(): void
    {
        $this->app['config']->set('applications.freshdesk.sandbox', false);

        $this->app['config']->set('applications.freshdesk.token', 'random token');

        $this->app['config']->set('applications.freshdesk.token2', 'random token 2');

        $this->app['config']->set('applications.freshdesk.tokenx', 'random token x');

        $this->app['config']->set('applications.freshdesk.tokenind', 'random token ind');

        $this->app['config']->set('applications.freshdesk.tokencap', 'random token capital');

        $this->freshdeskClientMock = Mockery::mock('RZP\Services\FreshdeskTicketClient', [$this->app])->makePartial();

        $this->freshdeskClientMock->shouldAllowMockingProtectedMethods();

        $this->app['freshdesk_client'] = $this->freshdeskClientMock;
    }

    protected function validateMethodAndContent($request, $expectedMethod, $expectedContent, $checkHtmlTag = false) : bool
    {
        if (strtolower($request['method']) !== strtolower($expectedMethod))
        {
            return false;
        }

        if (is_string($request['content']) === true)
        {
            $actualContent = json_decode($request['content'], true);

            if ($checkHtmlTag === true and str_contains($actualContent['description'], 'a href') === false)
            {
                return false;
            }
        }

        foreach ($expectedContent as $key => $value)
        {
            if (isset($actualContent[$key]) === false)
            {
                return false;
            }

            if ($expectedContent[$key] !== $actualContent[$key])
            {
                return false;
            }
        }

        return true;
    }

    public function getDefaultFreshdeskArray($merchantId="10000000000000")
        {
            $ticketDetails["fd_instance"] = "rzpind";

            return [
            'id'             => 'razorpayid0012',
            'ticket_id'      => '123',
            'merchant_id'    => $merchantId,
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
            'created_at'     => '1600000000',
            'updated_at'     => '1600000000',
            ];
        }
}
