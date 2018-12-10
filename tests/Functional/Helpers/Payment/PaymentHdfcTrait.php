<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Requests;
use RZP\Exception\GatewayTimeoutException;
use Symfony\Component\DomCrawler\Crawler;

trait PaymentHdfcTrait
{
    protected function hdfcPaymentFailedDueToDeniedByRisk()
    {
        $this->mockServerContentFunction(function (& $content, $action)
        {
            $content['result'] = 'DENIED BY RISK';
        });
    }

    protected function hdfcPaymentMockResultCode($result, $expectedAction)
    {
        $this->mockServerContentFunction(
            function (& $content, $action) use ($result, $expectedAction)
            {
                if ($action === $expectedAction)
                {
                    $content['result'] = $result;
                }
            });
    }

    protected function runPaymentCallbackFlowHdfc($response, &$callback = null)
    {
        $tds = $this->is3dSecure($response, $callback);

        $content = $response->getContent();

        if ($tds)
        {
            return $this->run3dSecureFlow($response, $callback);
        }

        return $response;
    }

    protected function run3dSecureFlow($response, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $mock = $this->isGatewayMocked();

        if ($this->isOtpCallbackUrl($url) === true)
        {
            $this->callbackUrl = $url;

            $this->otpFlow = true;

            return $this->makeOtpCallback($url);
        }

        //
        // Card has 3d-secure enabled
        // In which case, run card 3dsecure flow
        //

        if ($mock === false)
        {
            $options = ['follow_redirects' => false];

            list($url, $method, $content) = $this->makeRequestAndGetFormData(
                                            $url, $method, [], $content, $options);
        }
        else
        {
            $request = compact('url', 'method', 'content');
            $response = $this->makeRequestParent($request);

            list($url, $method, $content) = $this->getFormDataFromResponse(
                                    $response->getContent(), 'https://localhost');
        }

        return $this->submitPaymentCallbackData($url, $method, $content);
    }

    protected function is3dSecure($response, $callback = null)
    {
        $content = $response->getContent();

        if ($callback === null)
        {
            $tds = ((json_decode($content) === null) and
                    (get_class($response->baseResponse) === 'Illuminate\Http\Response') and
                    ($response->headers->get('content-type') === 'text/html; charset=UTF-8') and
                    ($response->getStatusCode() === 200));
        }
        else
        {
            $tds = ((json_decode($content) === null) and
                    (get_class($response->baseResponse) === 'Illuminate\Http\JsonResponse') and
                    ($response->headers->get('content-type') === 'text/javascript; charset=UTF-8') and
                    ($response->getStatusCode() === 200));

            if ($tds)
            {
                $content = $this->getJsonContentFromResponse($response, $callback);

                $tds = ((isset($content['http_status_code'])) and
                        ($content['http_status_code'] === 200) and
                        (isset($content['request'])));
            }
        }

        return $tds;
    }

    protected function captureErrorReturnGW00176()
    {
        $server = $this->mockServer()
                        ->shouldReceive('content')
                        ->andReturnUsing(function (& $content)
                        {
                            $content = array(
                                'error_code_tag' => 'GW00176',
                                'error_text' => '',
                                'result' => '!ERROR!-GW00176-Failed Previous Captures check.',
                            );

                            return $content;
                        })->mock();

        $this->setMockServer($server);
    }

    // @codingStandardsIgnoreLine
    protected function captureErrorReturnCM90000()
    {
        $this->i = true;

        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (&$content)
            {
                if ($this->i === true)
                {
                    $content = array(
                        'error_code_tag' => 'CM90000',
                        'error_text' => '',
                        'result' => '!ERROR!-CM90000-Problem occured while updating payment log ip details.',
                    );
                    $this->i = false;
                }
                return $content;
            })->mock();

        $this->setMockServer($server);
    }

    protected function captureErrorReturnGatewayTimeout()
    {
        $server = $this->mockServer()
                        ->shouldReceive('content')
                        ->andReturnUsing(function (& $content)
                        {
                            throw new GatewayTimeoutException(
                                'cURL error 28: Operation timed out after ' .
                                '10001 milliseconds with 0 bytes received');
                        }, function (& $content)
                        {
                            return $content;
                        })->mock();

        $this->setMockServer($server);
    }
}
