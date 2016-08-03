<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Mockery;
use Requests;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use Symfony\Component\DomCrawler\Crawler;

trait PaymentCreationTrait
{
    protected function submitPaymentCallbackForm($form)
    {
        //
        // third request
        // submit callback form
        //

        $uri = $form->getUri();
        $ix = strpos($uri, 'v1');

        $uri = substr($uri, $ix+2);

        $request['method'] = 'POST';
        $request['content'] = $form->getValues();

        $request['url'] = $uri;

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function submitPaymentCallbackRedirect($url)
    {
        $request['method'] = 'GET';
        $request['url'] = $url;

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function submitPaymentCallbackData($url, $method, $values)
    {
        $request['method'] = 'POST';
        $request['url'] = $url;
        $request['content'] = $values;

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function submitPaymentCallbackRequest($request)
    {
        $this->ba->publicCallbackAuth();

        $response = $this->makeRequestParent($request);

        $content = $response->getContent();

        if ($this->isResponseInstanceType($response, 'http'))
        {
            $formData = $this->getSecondFormDataFromResponse($content, 'http://localhost');

            if ((isset($formData['type'])) and
                ($formData['type'] === 'return'))
            {
                return $this->processMerchantReturnCallbackForm($response);
            }
        }

        $this->ba->publicAuth();

        $content = $this->getPaymentJsonFromCallback($content);

        $response->setContent($content);

        return $response;
    }

    protected function isPaymentCreationUrl($url)
    {
        $urls = array(
            '/payments/create/jsonp',
            '/payments/create/ajax',
            '/payments/create/checkout',
            '/payments');

        return in_array($url, $urls);
    }

    protected function isOtpCallbackUrl($uri)
    {
        $pattern = '/payments\/pay_[\w]+\/otp_submit\/[\w]+/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function handlePaymentCreationFlow($response, $request, &$callback = null)
    {
        $content = $response->getContent();

        $gateway = null;

        if ($request['url'] === '/payments/create/checkout')
        {
            $this->assertTrue($this->isResponseInstanceType($response, 'http'));
            $this->assertEquals($response->headers->get('content-type'), 'text/html; charset=UTF-8');

            $marker = '// Callback data //';
            if (strpos($content, $marker) !== false)
            {
                $content = $this->getPaymentJsonFromCallback($content);

                $response->setContent($content);

                return $response;
            }
        }

        if ($callback)
        {
            // Should be the jsonp payment creation url
            $this->assertEquals($request['url'], '/payments/create/jsonp');

            $content = $this->getJsonContentFromResponse($response, $callback);

            // For no 2-auth payments, it could be a direct json response.
            if (isset($content['gateway']) === false)
            {
                return $response;
            }

            $gateway = $content['gateway'];

            if (isset($content['type']) === 'return')
            {
                // @note: This case isn't happening right now but it can in future
                $request = $content['request'];

                return $this->makeRequestParent($request);
            }
        }
        else
        {
            // Has to be either redirect or a html form post.o
            // First check for normal html form post.
            $ret = ((json_decode($content) === null) and
                    ($this->isResponseInstanceType($response, 'http')) and
                    ($response->headers->get('content-type') === 'text/html; charset=UTF-8') and
                    ($response->getStatusCode() === 200));

            if ($ret === false)
            {
                // Now check for redirect
                $redirect = (($this->isResponseInstanceType($response, 'redirect')) and
                        ($response->getStatusCode() === 302));

                if ($redirect === true)
                {
                    $gateway = $response->headers->get('X-gateway');
                }

                //
                // Fetch payment creation info from JsonResponse
                //
                else if ($request['url'] === '/payments/create/ajax')
                {
                    $content = $response->getData(true);

                    if (isset($content['type']) === true)
                    {
                        if ($content['type'] === 'first')
                        {
                            $gateway = $content['gateway'];
                        }
                        else if ($content['type'] === 'return')
                        {
                            return $this->processMerchantReturnCallbackForm($response);
                        }
                        else if ($content['type'] === 'otp')
                        {
                            $gateway = $content['gateway'];
                        }
                    }
                }
                else
                {
                    return $response;
                }
            }
            else
            {
                $gateway = $response->headers->get('X-gateway');

                //
                // When doing form posts relevant here, we put in a
                // second form which is not submitted but it contains gateway
                // field in encrypted form and 'type' field with value as 'first'
                // or 'return'. Otherwise, don't take an action here.
                //
                $content = $this->getSecondFormDataFromResponse($content, 'http://localhost');

                if (isset($content['type']) === true)
                {
                    if ($content['type'] === 'first')
                    {
                        $gateway = $content['gateway'];
                    }
                    else if ($content['type'] === 'return')
                    {
                        return $this->processMerchantReturnCallbackForm($response);
                    }
                    else if ($content['type'] === 'otp')
                    {
                        $gateway = $content['gateway'];
                    }
                }
            }
        }

        return $this->runPaymentCallbackFlowForGateway($response, $gateway, $callback);
    }

    protected function runPaymentCallbackFlowForGateway($response,  $gateway, &$callback = null)
    {
        $gateway = $this->decryptGatewayText($gateway);

        $func = $gateway;

        if (strpos($gateway, 'netbanking') !== false)
            $func = 'netbanking';

        $func = studly_case($func);

        $func = 'runPaymentCallbackFlow'.$func;

        return $this->$func($response, $callback, $gateway);
    }

    protected function processMerchantReturnCallbackForm($response)
    {
        $content = $response->getContent();

        $content = $this->getSecondFormDataFromResponse($content, 'http://localhost');

        if ($content['type'] === 'return')
        {
            $this->merchantCallbackFlow = true;

            $request = $this->getFormRequestFromResponse($response->getContent(), 'http://localhost');

            $this->assertEquals($request['url'], $this->getLocalMerchantCallbackUrl());

            $response = $this->makeRequestParent($request);

            $this->assertResponse('json', $response);

            return $response;
        }
    }

    protected function checkAndSetUrl(& $request)
    {
        if (isset($request['url']) === false)
        {
            $request['url'] = '/payments';
        }
    }

    protected function makeFirstGatewayPaymentMockRequest($url, $method = 'get', $content = array())
    {
        $request = array(
           'url' => $url,
           'method' => strtoupper($method),
           'content' => $content);

        $response = $this->makeRequestParent($request);

        $statusCode = (int) $response->getStatusCode();


        if ($statusCode === 302)
        {
            return $response->getTargetUrl();
        }
        else if ($statusCode === 200)
        {
            // Probably a form here.
            // Return url, method, content from that.

            return $this->getFormRequestFromResponse($response->getContent(), $url);
        }
    }
}
