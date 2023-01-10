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

        $uri = substr($uri, $ix + 2);

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

        if ($this->isResponseInstanceType($response, 'json') === true)
        {
            return $response;
        }

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
            '/payments/create/redirect',
            '/payments/create/json',
            '/payments/create/recurring',
            '/payments/create/upi',
            '/payments');

        return in_array($url, $urls, true);
    }

    protected function isOtpCallbackUrl($uri)
    {
        $pattern = '/payments\/pay_[\w]+\/otp_submit\/[\w]+/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isOtpCallbackUrlPrivate($uri)
    {
        $pattern = '/payments\/pay_[\w]+\/otp\/submit/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isRedirectToAuthorizeUrl($uri)
    {
        $pattern = '/payments\/[\w]+\/authenticate/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isRedirectToDCCInfoUrl($uri)
    {
        $pattern = '/payments\/[\w]+\/dcc_info/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isUpdateDCCAndRedirectToAuthorizeUrl($uri)
    {
        $pattern = '/payments\/[\w]+\/updateAndRedirect/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isRedirectToAddressCollectUrl($uri)
    {
        $pattern = '/payments\/[\w]+\/address_collect/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isOtpFallbackUrl($uri)
    {
        $pattern = '/payments\/pay_[\w]+\/authentication\/redirect\?key_id=rzp_[\w]+/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isOtpResendUrl($uri)
    {
        $pattern = '/payments\/pay_[\w]+\/otp_resend\?[\w]+/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isOtpResendUrlJson($uri)
    {
        $pattern = '/payments\/pay_[\w]+\/otp_resend\/json\?key_id=rzp_[\w]+/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isOtpGenerateUrlPublic($uri)
    {
        $pattern = '/payments\/pay_[\w]+\/otp_generate\?track_id=[\w]+\&key_id=rzp_[\w]+/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isOtpResendUrlPublic($uri)
    {
        $pattern = '/payments\/pay_[\w]+\/otp_resend\?key_id=rzp_[\w]+/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function getUri($url)
    {
        $ix = strpos($url, 'v1');

        return substr($url, $ix + 2);
    }

    protected function isOtpResendUrlPrivate($uri)
    {
        $pattern = '/payments\/pay_[\w]+\/otp\/resend/';

        return (preg_match($pattern, $uri) === 1);
    }

    protected function isOtpVerifyUrl($uri)
    {
        $pattern = '/otp\/verify/';

        return (preg_match($pattern, $uri) === 1);
    }


    protected function handlePaymentCreationFlow($response, $request, &$callback = null)
    {
        $content = $response->getContent();

        $gateway = null;

        if ($request['url'] === '/payments/create/checkout')
        {
            $this->assertTrue($this->isResponseInstanceType($response, 'http') or
                $this->isResponseInstanceType($response, 'redirect'));
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
            // Has to be either redirect or a html form post.
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
                    $targetUrl = $response->getTargetUrl();

                    $isAuthorizeRedirect = (($targetUrl !== null) and
                                            ($this->isRedirectToAuthorizeUrl($targetUrl) === true));

                    if (boolval($isAuthorizeRedirect) === true)
                    {
                        return $this->makeRedirectToAuthorize($targetUrl);
                    }

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
                            $targetUrl = $content['request']['url'];
                            if ($this->isRedirectToDCCInfoUrl($targetUrl) === true)
                            {
                                return $this->makeRedirectToDCCInfo($targetUrl);
                            }
                            else if ($this->isRedirectToAddressCollectUrl($targetUrl) === true)
                            {
                                return $this->makeRedirectToAddressCollect($targetUrl);
                            }
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
                        else if ($content['type'] === 'async')
                        {
                            return $response;
                        }
                        else if ($content['type'] === 'intent')
                        {
                            return $response;
                        }
                        else if($content['type'] === 'redirect'){
                            $content = $this->getJsonContentFromResponse($response);
                            return $this->makeRedirectToAuthorize($content['request']['url']);
                        }
                    }
                    else if(isset($request['content']) && isset($request['content']['upi'])
                        && $request['content']['upi']['mode'] == 'in_app') {
                        return $response;
                    }
                }
                else if (($request['url'] !== '/payments/create/json') or
                        ($request['url'] !== '/payments/create/redirect'))
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
                    else if ($content['type'] === 'async')
                    {
                        return $this->processAsyncPaymentForm($response);
                    }
                    else if ($content['type'] === 'intent')
                    {
                        return $this->processAsyncPaymentForm($response);
                    }
                    else if ($content['type'] === 'respawn')
                    {
                        $gateway = $content['gateway'];
                    }
                }
            }
        }

        if ($request['url'] === '/payments/create/redirect')
        {
            $targetUrl =$this->getMetaRefreshUrl($response);

            if ($this->isRedirectToAuthorizeUrl($targetUrl) === true)
            {
                return $this->makeRedirectToAuthorize($targetUrl);
            }
            else if ($this->isRedirectToDCCInfoUrl($targetUrl) === true)
            {
                return $this->makeRedirectToDCCInfo($targetUrl);
            }
            else if ($this->isRedirectToAddressCollectUrl($targetUrl) === true)
            {
                return $this->makeRedirectToAddressCollect($targetUrl);
            }
        }

        if ($this->isRedirectToDCCInfoUrl($request['url']) === true)
        {
            return $this->makeRedirectToUpdateAndAuthorize($response);
        }

        if ($this->isRedirectToAddressCollectUrl($request['url']) === true)
        {
            return $this->makeRedirectToUpdateAndAuthorizeForAddress($response);
        }

        if ($request['url'] === '/payments/create/json')
        {
            $content = $this->getJsonContentFromResponse($response);

            return $this->makeRedirectToAuthorize($content['url']);

        }

        return $this->runPaymentCallbackFlowForGateway($response, $gateway, $callback);
    }

    protected function makeRedirectToAuthorize($targetUrl)
    {
        $id = getTextBetweenStrings($targetUrl, '/payments/', '/authenticate');

        $this->redirectToAuthorize = true;

        $url = $this->getPaymentRedirectToAuthorizrUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $response = $this->makeRequestParent($request);

        $this->ba->publicAuth();

        return $this->handlePaymentCreationFlow($response, $request);
    }

    protected function makeRedirectToDCCInfo($targetUrl)
    {
        $id = getTextBetweenStrings($targetUrl, '/payments/', '/dcc_info');

        $this->redirectToDCCInfo = true;

        $url = $this->getPaymentRedirectToDCCInfoUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $response = $this->makeRequestParent($request);

        $this->ba->publicAuth();

        $this->resetSingletons();

        return $this->handlePaymentCreationFlow($response, $request);
    }

    protected function makeRedirectToUpdateAndAuthorize($response)
    {
        $content = $response->getContent();

        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $this->assertTrue($this->isUpdateDCCAndRedirectToAuthorizeUrl($url));
        $this->assertFalse(empty($content['currency_request_id']));
        $this->assertFalse(empty($content['dcc_currency']));
        $this->assertFalse(empty($content['amount']));
        $this->assertFalse(empty($content['forex_rate']));
        $this->assertFalse(empty($content['fee']));
        $this->assertFalse(empty($content['conversion_percentage']));

        unset($content['amount']);
        unset($content['forex_rate']);
        unset($content['fee']);
        unset($content['conversion_percentage']);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => $method,
            'content' => $content,
        ];

        $response = $this->makeRequestParent($request);

        $this->ba->publicAuth();

        $this->resetSingletons();

        return $this->handlePaymentCreationFlow($response, $request);
    }

    protected function makeRedirectToUpdateAndAuthorizeForAddress($response)
    {
        $content = $response->getContent();

        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $this->assertTrue($this->isUpdateDCCAndRedirectToAuthorizeUrl($url));

        $this->ba->directAuth();

        $content['billing_address'] = $this->getDefaultBillingAddressArray();
        $request = [
            'url'   => $url,
            'method' => $method,
            'content' => $content,
        ];

        $response = $this->makeRequestParent($request);

        $this->ba->publicAuth();

        $this->resetSingletons();

        return $this->handlePaymentCreationFlow($response, $request);
    }

    protected function makeRedirectToAddressCollect($targetUrl)
    {
        $id = getTextBetweenStrings($targetUrl, '/payments/', '/address_collect');

        $this->redirectToAddressCollect = true;

        $url = $this->getPaymentRedirectToAddressCollectUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $response = $this->makeRequestParent($request);

        $this->ba->publicAuth();

        $this->resetSingletons();

        return $this->handlePaymentCreationFlow($response, $request);
    }

    protected function handleWalletTopupFlow($response, $request, &$callback = null)
    {
        $content = $response->getContent();

        $gateway = null;

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
            else if (\Str::endsWith($request['url'], 'topup/ajax'))
            {
                $content = $response->getData(true);

                if (isset($content['type']) === true)
                {
                    if ($content['type'] === 'first')
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
            }
        }

        return $this->runPaymentCallbackFlowForGateway($response, $gateway, $callback);
    }

    protected function runPaymentCallbackFlowForGateway($response, $gateway, &$callback = null)
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
            $this->assertS2SCallback($response);

            $this->merchantCallbackFlow = true;

            $request = $this->getFormRequestFromResponse($response->getContent(), 'http://localhost');

            $this->assertEquals($request['url'], $this->getLocalMerchantCallbackUrl());

            $response = $this->makeRequestParent($request);

            $this->assertResponse('json', $response);

            return $response;
        }
    }

    protected function assertS2SCallback($response)
    {
        if ($this->ba->isPrivateAuth() === true)
        {
            $this->assertResponse('json', $response);
        }
    }

    protected function processAsyncPaymentForm($response)
    {
        $this->assertTrue($this->isResponseInstanceType($response, 'http'));
        $this->assertEquals($response->headers->get('content-type'), 'text/html; charset=UTF-8');

        $content = $response->getContent();

        $marker = '// Async Payment data //';

        if (strpos($content, $marker) !== false)
        {
            $start = 'var data = ';
            $end = '// Async Payment data //';

            $data = getTextBetweenStrings($content, $start, $end);

            // Remove ';' at the end to get proper json string
            $data = trim($data);
            $content = substr($data, 0, -1);

            $response->setContent($content);

            return $response;
        }
    }

    protected function processCardlessPaymentForm($response)
    {
        $this->assertTrue($this->isResponseInstanceType($response, 'http'));
        $this->assertEquals($response->headers->get('content-type'), 'text/html; charset=UTF-8');

        $content = $response->getContent();

        $marker = '// input data //';

        if (strpos($content, $marker) !== false)
        {
            $start = 'var data = ';
            $end = '// input data //';

            $data = getTextBetweenStrings($content, $start, $end);

            // Remove ';' at the end to get proper json string
            $data = trim($data);
            $content = substr($data, 0, -1);

            $response->setContent($content);

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
